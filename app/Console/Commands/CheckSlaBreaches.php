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
                        {--dry-run : Run without actually escalating (test mode)}
                        {--critical-only : Check only critical priority incidents}
                        {--priority= : Check only specific priority (low, medium, high, critical)}
                        {--force : Force re-check even if recently notified}';

    /**
     * The console command description.
     */
    protected $description = 'Check for SLA breaches and auto-escalate based on escalation matrix';

    // SLA breach intervals for different priorities (in minutes)
    protected const SLA_BREACH_INTERVALS = [
        'critical' => 5,    // Every 5 minutes for critical
        'high' => 15,       // Every 15 minutes for high
        'medium' => 30,     // Every 30 minutes for medium
        'low' => 60,        // Every 60 minutes for low
    ];

    // Escalation triggers (number of SLA breaches before escalation)
    protected const ESCALATION_TRIGGERS = [
        'critical' => 2,    // Escalate after 2 breaches for critical
        'high' => 3,        // Escalate after 3 breaches for high
        'medium' => 4,      // Escalate after 4 breaches for medium
        'low' => 5,         // Escalate after 5 breaches for low
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $isDebug = $this->option('debug');
        $forceRecheck = $this->option('force');

        if ($isDryRun) {
            $this->warn('🧪 DRY RUN MODE - No actual changes will be made');
        }

        $this->info('🔍 Starting SLA breach check...');
        $startTime = microtime(true);

        // Get all breached incidents with proper SLA validation
        $breachedIncidents = $this->getBreachedIncidents();

        $totalBreached = $breachedIncidents->count();
        $escalatedCount = 0;
        $notifiedCount = 0;
        $skippedCount = 0;

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

            // Validate if escalation is still needed
            if (! $this->shouldProcessIncident($incident)) {
                $this->line("   ⏭️ Skipping: Incident no longer requires escalation (Status: {$incident->status})");
                $skippedCount++;

                continue;
            }

            // Check if we should increment breach count
            $shouldIncrement = $this->shouldIncrementBreachCount($incident, $forceRecheck);

            if ($shouldIncrement) {
                if (! $isDryRun) {
                    $this->incrementBreachCount($incident);
                }
                $incident->refresh();
                $this->line("   ⚠️ SLA Breach #{$incident->sla_breach_count} recorded");
            }

            $breachCount = $incident->sla_breach_count;
            $currentLevel = $this->getCurrentEscalationLevel($incident);
            $maxLevel = EscalationMatrix::getMaxLevel($incident->department_id, $incident->category_id);

            $shouldEscalate = $this->shouldEscalate($incident, $breachCount, $currentLevel, $maxLevel);

            if ($isDebug) {
                $this->displayDebugInfo($incident, $breachCount, $currentLevel, $maxLevel, $shouldEscalate);
            }

            // Handle escalation
            if ($shouldEscalate) {
                $result = $this->handleEscalation($incident, $breachCount, $currentLevel, $maxLevel, $isDryRun);

                if ($result['escalated']) {
                    $escalatedCount++;
                }

                if ($result['notified']) {
                    $notifiedCount++;
                }
            } else {
                // Send SLA breach notification without escalation
                $this->sendSlaNotification($incident, $breachCount, $isDryRun);
                $notifiedCount++;
            }
        }

        $duration = round(microtime(true) - $startTime, 2);

        $this->displaySummary($totalBreached, $escalatedCount, $notifiedCount, $skippedCount, $duration, $isDryRun);

        Log::info('SLA Check completed', [
            'total' => $totalBreached,
            'escalated' => $escalatedCount,
            'notified' => $notifiedCount,
            'skipped' => $skippedCount,
            'duration' => $duration,
        ]);

        return 0;
    }

    /**
     * Get breached incidents based on filters
     */
    protected function getBreachedIncidents()
    {
        $priority = $this->option('priority');
        $criticalOnly = $this->option('critical-only');

        $query = Incident::where('sla_due_at', '<', now())
            ->whereIn('status', ['open', 'acknowledged', 'in_progress', 'escalated'])
            ->whereNotNull('sla_due_at')
            ->where('sla_breach_count', '<', 10) // Limit to prevent infinite escalation
            ->with(['department', 'category', 'assignedTo']);

        if ($criticalOnly) {
            $query->where('priority', 'critical');
        } elseif ($priority) {
            $query->where('priority', $priority);
        }

        return $query->get();
    }

    /**
     * Check if incident should still be processed
     */
    protected function shouldProcessIncident(Incident $incident): bool
    {
        // Don't process if incident is already resolved or closed
        if (in_array($incident->status, ['resolved', 'closed', 'cancelled'])) {
            return false;
        }

        // Don't process if assigned user is on leave or unavailable
        if ($incident->assignedTo && $incident->assignedTo->is_on_leave ?? false) {
            return false;
        }

        return true;
    }

    /**
     * Check if we should increment the breach count
     */
    protected function shouldIncrementBreachCount(Incident $incident, bool $forceRecheck): bool
    {
        if ($forceRecheck) {
            return true;
        }

        $interval = $this->getBreachNotificationInterval($incident->priority);

        // Only increment if not notified recently based on priority
        if ($incident->sla_breach_notified_at &&
            $incident->sla_breach_notified_at->diffInMinutes(now()) < $interval) {
            return false;
        }

        return true;
    }

    /**
     * Get breach notification interval based on priority
     */
    protected function getBreachNotificationInterval(string $priority): int
    {
        return self::SLA_BREACH_INTERVALS[$priority] ?? 30;
    }

    /**
     * Increment breach count in database
     */
    protected function incrementBreachCount(Incident $incident): void
    {
        DB::table('incidents')
            ->where('id', $incident->id)
            ->update([
                'sla_breach_count' => DB::raw('sla_breach_count + 1'),
                'sla_breach_notified_at' => now(),
                'updated_at' => now(),
            ]);
    }

    /**
     * Get current escalation level
     */
    protected function getCurrentEscalationLevel(Incident $incident): int
    {
        return $incident->escalations()->count();
    }

    /**
     * Determine if incident should be escalated
     */
    protected function shouldEscalate(Incident $incident, int $breachCount, int $currentLevel, int $maxLevel): bool
    {
        // Check if we've reached max escalation level
        if ($currentLevel >= $maxLevel) {
            return false;
        }

        // Get escalation trigger for this priority
        $triggerCount = self::ESCALATION_TRIGGERS[$incident->priority] ?? 3;

        // Escalate when breach count reaches trigger threshold
        if ($breachCount >= $triggerCount && ($breachCount - $triggerCount) % $triggerCount === 0) {
            return true;
        }

        return false;
    }

    /**
     * Handle escalation process
     */
    protected function handleEscalation(Incident $incident, int $breachCount, int $currentLevel, int $maxLevel, bool $isDryRun): array
    {
        $nextLevel = min($currentLevel + 1, $maxLevel);
        $result = ['escalated' => false, 'notified' => false];

        $escalationEntry = EscalationMatrix::getEscalationForIncident($incident, $nextLevel);

        if (! $escalationEntry) {
            $this->warn("   ⚠️ No escalation matrix found for Level {$nextLevel}");
            $result['notified'] = $this->sendSlaNotification($incident, $breachCount, $isDryRun);

            return $result;
        }

        $escalatedToUser = User::find($escalationEntry->escalate_to_user_id);
        $this->warn("   ⬆️ Auto-escalating to Level {$nextLevel}: ".
                   ($escalatedToUser?->name ?? 'User #'.$escalationEntry->escalate_to_user_id));

        if ($isDryRun) {
            $this->line('   🧪 DRY RUN: Would escalate');
            $result['escalated'] = true;
            $result['notified'] = true;

            return $result;
        }

        try {
            DB::beginTransaction();

            // Create escalation record
            $escalationId = $this->createEscalationRecord($incident, $escalationEntry, $nextLevel, $breachCount);

            // Update incident
            $this->updateIncidentForEscalation($incident, $escalationEntry);

            // Log activity
            $this->logEscalationActivity($incident, $nextLevel, $breachCount);

            DB::commit();

            // Send notification asynchronously
            $this->sendEscalationNotification($incident, $escalationId);

            $this->info('   ✅ Auto-escalated successfully!');
            $result['escalated'] = true;
            $result['notified'] = true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Auto-escalation failed: '.$e->getMessage(), [
                'incident_id' => $incident->id,
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error('   ❌ Failed: '.$e->getMessage());
        }

        return $result;
    }

    /**
     * Create escalation record
     */
    protected function createEscalationRecord(Incident $incident, $escalationEntry, int $level, int $breachCount): int
    {
        DB::table('escalations')->insert([
            'incident_id' => $incident->id,
            'escalated_by' => 1, // System user
            'escalated_to' => $escalationEntry->escalate_to_user_id,
            'from_department_id' => $incident->department_id,
            'to_department_id' => $escalationEntry->escalate_to_department_id,
            'level' => $level,
            'reason' => "Auto-escalated: SLA breach count reached {$breachCount} (Level {$level})",
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::getPdo()->lastInsertId();
    }

    /**
     * Update incident for escalation
     */
    protected function updateIncidentForEscalation(Incident $incident, $escalationEntry): void
    {
        DB::table('incidents')
            ->where('id', $incident->id)
            ->update([
                'status' => 'escalated',
                'escalated_to' => $escalationEntry->escalate_to_user_id,
                'escalated_at' => now(),
                'updated_at' => now(),
            ]);
    }

    /**
     * Log escalation activity
     */
    protected function logEscalationActivity(Incident $incident, int $level, int $breachCount): void
    {
        DB::table('incident_logs')->insert([
            'incident_id' => $incident->id,
            'user_id' => 1,
            'action' => 'auto_escalated',
            'description' => "System auto-escalated to Level {$level} after {$breachCount} SLA breaches",
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Send SLA notification
     */
    protected function sendSlaNotification(Incident $incident, int $breachCount, bool $isDryRun): bool
    {
        if ($isDryRun) {
            $this->line("   🔔 DRY RUN: Would send SLA breach notification (Breach #{$breachCount})");

            return true;
        }

        try {
            dispatch(function () use ($incident, $breachCount) {
                $freshIncident = Incident::find($incident->id);
                if ($freshIncident) {
                    app(NotificationService::class)->notifySlaBreach($freshIncident);
                    Log::info('SLA breach notification sent', [
                        'incident_id' => $incident->id,
                        'breach_count' => $breachCount,
                    ]);
                }
            })->onQueue('notifications');

            $this->line("   🔔 SLA breach notification queued (Breach #{$breachCount})");

            return true;
        } catch (\Exception $e) {
            Log::error('SLA notification failed: '.$e->getMessage());
            $this->error('   ❌ Notification failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Send escalation notification
     */
    protected function sendEscalationNotification(Incident $incident, int $escalationId): void
    {
        try {
            dispatch(function () use ($incident, $escalationId) {
                $freshIncident = Incident::find($incident->id);
                $escalation = Escalation::find($escalationId);
                if ($freshIncident && $escalation) {
                    app(NotificationService::class)->notifyIncidentEscalated($freshIncident, $escalation);
                }
            })->onQueue('notifications');
        } catch (\Exception $e) {
            Log::error('Auto-escalation notification failed: '.$e->getMessage());
        }
    }

    /**
     * Display debug information
     */
    protected function displayDebugInfo(Incident $incident, int $breachCount, int $currentLevel, int $maxLevel, bool $shouldEscalate): void
    {
        $this->line("   Priority: {$incident->priority}");
        $this->line("   Status: {$incident->status}");
        $this->line("   SLA Due: {$incident->sla_due_at->format('Y-m-d H:i:s')}");
        $this->line("   Breach Count: {$breachCount}");
        $this->line("   Escalation Level: {$currentLevel} / {$maxLevel}");
        $this->line("   Department: {$incident->department?->name}");
        $this->line("   Category: {$incident->category?->name}");
        $this->line('   Should Escalate: '.($shouldEscalate ? 'Yes' : 'No'));
        $this->line('   Trigger Threshold: '.(self::ESCALATION_TRIGGERS[$incident->priority] ?? 3));
    }

    /**
     * Display summary
     */
    protected function displaySummary(int $total, int $escalated, int $notified, int $skipped, float $duration, bool $isDryRun): void
    {
        $this->newLine();
        $this->line('══════════════════════════════════════════');
        $this->info(' 📊 SLA CHECK SUMMARY');
        $this->line('══════════════════════════════════════════');
        $this->info(" Total breached: {$total}");
        $this->info(" Auto-escalated: {$escalated}");
        $this->info(" Notifications: {$notified}");
        $this->info(" Skipped: {$skipped}");
        $this->info(" Duration: {$duration}s");

        if ($isDryRun) {
            $this->warn(' 🧪 DRY RUN - No changes made');
        }

        $this->line('══════════════════════════════════════════');
    }
}
