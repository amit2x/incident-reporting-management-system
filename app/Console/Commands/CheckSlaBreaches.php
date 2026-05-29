<?php

namespace App\Console\Commands;

use App\Models\Escalation;
use App\Models\EscalationMatrix;
use App\Models\Incident;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckSlaBreaches extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'incidents:check-sla
                            {--debug : Show detailed debug output}
                            {--dry-run : Run without actually escalating (test mode)}';

    /**
     * The console command description.
     */
    protected $description = 'Check for SLA breaches and auto-escalate based on escalation matrix';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $isDebug = $this->option('debug');

        if ($isDryRun) {
            $this->warn('🧪 DRY RUN MODE - No actual changes will be made');
        }

        $this->info('🔍 Starting SLA breach check...');
        $startTime = microtime(true);

        // Get all breached incidents
        $breachedIncidents = Incident::where('sla_due_at', '<', now())
            ->whereIn('status', ['open', 'acknowledged', 'in_progress', 'escalated'])
            ->whereNotNull('sla_due_at')
            ->with(['department', 'category', 'assignedTo'])
            ->get();

        $totalBreached = $breachedIncidents->count();
        $escalatedCount = 0;
        $notifiedCount = 0;

        $this->info("📊 Found {$totalBreached} breached incident(s)");
        $this->newLine();

        if ($totalBreached === 0) {
            $this->info('✅ No SLA breaches to process.');
            Log::info('SLA Check: No breaches found');

            return 0;
        }

        $notificationService = app(NotificationService::class);

        foreach ($breachedIncidents as $incident) {
            $this->line('──────────────────────────────────────────');
            $this->info("📋 Processing: #{$incident->incident_id} - {$incident->title}");

            // Only increment breach count if not already notified recently (within last 5 minutes)
            $shouldIncrement = true;
            if ($incident->sla_breach_notified_at && $incident->sla_breach_notified_at->diffInMinutes(now()) < 5) {
                $shouldIncrement = false;
            }

            if ($shouldIncrement) {
                if (! $isDryRun) {
                    DB::table('incidents')
                        ->where('id', $incident->id)
                        ->update([
                            'sla_breach_count' => DB::raw('sla_breach_count + 1'),
                            'sla_breach_notified_at' => now(),
                            'updated_at' => now(),
                        ]);
                }
                $incident->refresh();
            }

            $breachCount = $incident->sla_breach_count;

            $this->line("   Status: {$incident->status} | SLA Due: {$incident->sla_due_at->format('d M H:i')} | Breach #: {$breachCount}");

            // Get escalation matrix info
            $currentLevel = $incident->escalations()->count();
            $maxLevel = EscalationMatrix::getMaxLevel($incident->department_id, $incident->category_id);

            if ($isDebug) {
                $this->line("   Current escalation level: {$currentLevel} / {$maxLevel}");
                $this->line("   Dept: {$incident->department?->name} | Category: {$incident->category?->name}");
            }

            // Check if we should auto-escalate (every 3 breaches)
            if ($breachCount % 3 === 0 && $currentLevel < $maxLevel) {
                $nextLevel = $currentLevel + 1;

                // Look up escalation matrix
                $escalationEntry = EscalationMatrix::getEscalationForIncident($incident, $nextLevel);

                if ($escalationEntry) {
                    $escalatedToUser = User::find($escalationEntry->escalate_to_user_id);

                    $this->warn("   ⬆️ Auto-escalating to Level {$nextLevel}: ".($escalatedToUser?->name ?? 'User #'.$escalationEntry->escalate_to_user_id));

                    if ($isDryRun) {
                        $this->line('   🧪 DRY RUN: Would escalate');
                        $escalatedCount++;
                    } else {
                        try {
                            DB::beginTransaction();

                            // Create escalation record
                            DB::table('escalations')->insert([
                                'incident_id' => $incident->id,
                                'escalated_by' => 1,
                                'escalated_to' => $escalationEntry->escalate_to_user_id,
                                'from_department_id' => $incident->department_id,
                                'to_department_id' => $escalationEntry->escalate_to_department_id,
                                'level' => $nextLevel,
                                'reason' => "Auto-escalated: SLA breach count reached {$breachCount} (Level {$nextLevel})",
                                'status' => 'pending',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            $escalationId = DB::getPdo()->lastInsertId();

                            // Update incident
                            DB::table('incidents')
                                ->where('id', $incident->id)
                                ->update([
                                    'status' => 'escalated',
                                    'escalated_to' => $escalationEntry->escalate_to_user_id,
                                    'escalated_at' => now(),
                                    'updated_at' => now(),
                                ]);

                            // Log activity
                            DB::table('incident_logs')->insert([
                                'incident_id' => $incident->id,
                                'user_id' => 1,
                                'action' => 'auto_escalated',
                                'description' => "System auto-escalated to Level {$nextLevel} after {$breachCount} SLA breaches",
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            DB::commit();

                            // Send notification asynchronously
                            dispatch(function () use ($incident, $escalationId) {
                                try {
                                    $freshIncident = Incident::find($incident->id);
                                    $escalation = Escalation::find($escalationId);
                                    if ($freshIncident && $escalation) {
                                        app(NotificationService::class)->notifyIncidentEscalated($freshIncident, $escalation);
                                    }
                                } catch (\Exception $e) {
                                    Log::error('Auto-escalation notification failed: '.$e->getMessage());
                                }
                            })->onQueue('notifications');

                            $escalatedCount++;
                            $this->info('   ✅ Auto-escalated successfully!');

                        } catch (\Exception $e) {
                            DB::rollBack();
                            Log::error('Auto-escalation failed: '.$e->getMessage(), [
                                'incident_id' => $incident->id,
                                'trace' => $e->getTraceAsString(),
                            ]);
                            $this->error('   ❌ Failed: '.$e->getMessage());
                        }
                    }
                } else {
                    $this->warn("   ⚠️ No escalation matrix for Level {$nextLevel}");
                    $notifiedCount++;

                    // Send SLA breach notification
                    dispatch(function () use ($incident) {
                        try {
                            $freshIncident = Incident::find($incident->id);
                            if ($freshIncident) {
                                app(NotificationService::class)->notifySlaBreach($freshIncident);
                            }
                        } catch (\Exception $e) {
                            Log::error('SLA notification failed: '.$e->getMessage());
                        }
                    })->onQueue('notifications');
                }
            } else {
                $notifiedCount++;

                // Send SLA breach warning
                dispatch(function () use ($incident) {
                    try {
                        $freshIncident = Incident::find($incident->id);
                        if ($freshIncident) {
                            app(NotificationService::class)->notifySlaBreach($freshIncident);
                        }
                    } catch (\Exception $e) {
                        Log::error('SLA notification failed: '.$e->getMessage());
                    }
                })->onQueue('notifications');
            }
        }

        $duration = round(microtime(true) - $startTime, 2);

        $this->newLine();
        $this->line('══════════════════════════════════════════');
        $this->info(' 📊 SLA CHECK SUMMARY');
        $this->line('══════════════════════════════════════════');
        $this->info(" Total breached: {$totalBreached}");
        $this->info(" Auto-escalated: {$escalatedCount}");
        $this->info(" Notifications: {$notifiedCount}");
        $this->info(" Duration: {$duration}s");
        if ($isDryRun) {
            $this->warn(' 🧪 DRY RUN - No changes made');
        }
        $this->line('══════════════════════════════════════════');

        Log::info('SLA Check completed', [
            'total' => $totalBreached,
            'escalated' => $escalatedCount,
            'notified' => $notifiedCount,
            'duration' => $duration,
        ]);

        return 0;
    }
}
