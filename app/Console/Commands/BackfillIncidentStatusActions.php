<?php

namespace App\Console\Commands;

use App\Models\Incident;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BackfillIncidentStatusActions extends Command
{
    protected $signature = 'incidents:backfill-status-actions
                            {--dry-run : Run without actually inserting data}
                            {--chunk=100 : Number of incidents to process per chunk}
                            {--incident= : Process only a specific incident ID}';

    protected $description = 'Backfill incident_status_actions table from existing logs and incident data';

    public function handle(): int
    {
        $this->info('🔄 Starting backfill of incident_status_actions...');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        $chunkSize = (int) $this->option('chunk');
        $specificIncident = $this->option('incident');

        if ($dryRun) {
            $this->warn('🧪 DRY RUN MODE - No data will be inserted');
            $this->newLine();
        }

        $query = Incident::query();

        if ($specificIncident) {
            $query->where('id', $specificIncident);
        }

        $totalIncidents = $query->count();
        $this->info("📊 Found {$totalIncidents} incidents to process");

        if ($totalIncidents === 0) {
            $this->warn('No incidents found to process.');
            return 0;
        }

        $this->newLine();
        $bar = $this->output->createProgressBar($totalIncidents);

        $processed = 0;
        $inserted = 0;
        $skipped = 0;
        $errors = 0;

        // Get system user
        $systemUser = User::first();
        if (!$systemUser) {
            $systemUser = User::create([
                'name' => 'System',
                'email' => 'system@' . str_replace(['http://', 'https://'], '', config('app.url')),
                'password' => bcrypt('system_' . uniqid()),
            ]);
        }

        $query->chunk($chunkSize, function ($incidents) use (
            $dryRun,
            $bar,
            $systemUser,
            &$processed,
            &$inserted,
            &$skipped,
            &$errors
        ) {
            foreach ($incidents as $incident) {
                $processed++;
                $bar->advance();

                try {
                    $actions = $this->extractActionsFromIncidentDirect($incident, $systemUser);

                    if (empty($actions)) {
                        $skipped++;
                        continue;
                    }

                    if (!$dryRun) {
                        $insertCount = $this->insertActionsDirect($incident->id, $actions);
                        $inserted += $insertCount;
                    } else {
                        $inserted += count($actions);
                    }

                } catch (\Exception $e) {
                    $errors++;
                    Log::error('Failed to backfill incident', [
                        'incident_id' => $incident->id,
                        'error' => $e->getMessage(),
                    ]);

                    $this->error("\n❌ Failed for incident #{$incident->id}: " . $e->getMessage());
                }
            }
        });

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->line('══════════════════════════════════════════');
        $this->info('📊 BACKFILL SUMMARY');
        $this->line('══════════════════════════════════════════');
        $this->info(" Total incidents processed: {$processed}");
        $this->info(" Actions inserted: {$inserted}");
        $this->info(" Incidents skipped (no actions): {$skipped}");
        $this->info(" Errors: {$errors}");

        if ($dryRun) {
            $this->warn('🧪 DRY RUN - No data was inserted');
        }
        $this->line('══════════════════════════════════════════');

        return 0;
    }

    /**
     * Extract actions directly from database (no model dependency).
     */
    private function extractActionsFromIncidentDirect($incident, $systemUser): array
    {
        $actions = [];
        $incidentId = $incident->id;

        // Helper to convert to Carbon
        $toCarbon = function($value) {
            if ($value instanceof Carbon) {
                return $value;
            }
            if (is_string($value)) {
                return Carbon::parse($value);
            }
            return Carbon::now();
        };

        // 1. Created action
        $actions[] = [
            'action' => 'created',
            'user_id' => $incident->reported_by ?? $systemUser->id,
            'previous_status' => null,
            'new_status' => 'open',
            'reason' => 'Incident created',
            'metadata' => json_encode([
                'created_by' => $incident->reporter?->name ?? 'System',
            ]),
            'acted_at' => $toCarbon($incident->created_at),
        ];

        // 2. Acknowledged action
        if ($incident->acknowledged_at) {
            $userId = $this->findActorFromLogs($incidentId, 'acknowledged', 'acknowledged', $systemUser);
            $actions[] = [
                'action' => 'acknowledged',
                'user_id' => $userId,
                'previous_status' => 'open',
                'new_status' => 'acknowledged',
                'reason' => 'Incident acknowledged',
                'metadata' => json_encode([
                    'acknowledged_at' => $incident->acknowledged_at->toDateTimeString(),
                ]),
                'acted_at' => $toCarbon($incident->acknowledged_at),
            ];
        }

        // 3. In Progress action
        if ($incident->in_progress_at) {
            $actions[] = [
                'action' => 'in_progress',
                'user_id' => $incident->assigned_to ?? $systemUser->id,
                'previous_status' => $incident->status === 'in_progress' ? 'acknowledged' : 'open',
                'new_status' => 'in_progress',
                'reason' => 'Work started on incident',
                'metadata' => json_encode([
                    'in_progress_at' => $incident->in_progress_at->toDateTimeString(),
                ]),
                'acted_at' => $toCarbon($incident->in_progress_at),
            ];
        }

        // 4. Escalated actions - Direct DB query
        $escalations = DB::table('escalations')
            ->where('incident_id', $incidentId)
            ->get();

        foreach ($escalations as $escalation) {
            $actions[] = [
                'action' => 'escalated',
                'user_id' => $escalation->escalated_by ?? $systemUser->id,
                'previous_status' => 'in_progress',
                'new_status' => 'escalated',
                'reason' => $escalation->reason ?? 'Incident escalated',
                'metadata' => json_encode([
                    'escalated_to' => $escalation->escalated_to,
                    'escalation_level' => $escalation->level,
                    'escalation_id' => $escalation->id,
                ]),
                'acted_at' => $toCarbon($escalation->created_at),
            ];
        }

        // 5. Assigned actions - Direct DB query
        $assignments = DB::table('incident_assignments')
            ->where('incident_id', $incidentId)
            ->get();

        foreach ($assignments as $assignment) {
            $isActive = property_exists($assignment, 'is_active') ? $assignment->is_active : true;

            $metadata = [
                'assigned_to' => $assignment->assigned_to,
                'assignment_id' => $assignment->id,
                'is_active' => $isActive,
            ];

            if (property_exists($assignment, 'unassigned_at') && $assignment->unassigned_at) {
                $metadata['unassigned_at'] = $assignment->unassigned_at;
            }

            $actions[] = [
                'action' => 'assigned',
                'user_id' => $assignment->assigned_by ?? $systemUser->id,
                'previous_status' => null,
                'new_status' => null,
                'reason' => $assignment->notes ?? 'Incident assigned',
                'metadata' => json_encode($metadata),
                'acted_at' => $toCarbon($assignment->assigned_at ?? $assignment->created_at),
            ];
        }

        // 6. Resolved action
        if ($incident->resolved_at) {
            $userId = $this->findActorFromLogs($incidentId, 'resolved', 'resolved', $systemUser);
            $actions[] = [
                'action' => 'resolved',
                'user_id' => $userId,
                'previous_status' => $this->getPreviousStatusFromLogs($incidentId, 'resolved'),
                'new_status' => 'resolved',
                'reason' => $incident->resolution_notes ?? 'Incident resolved',
                'metadata' => json_encode([
                    'resolution_notes' => $incident->resolution_notes,
                    'resolved_at' => $incident->resolved_at->toDateTimeString(),
                ]),
                'acted_at' => $toCarbon($incident->resolved_at),
            ];
        }

        // 7. Closed action
        if ($incident->closed_at) {
            $userId = $this->findActorFromLogs($incidentId, 'closed', 'closed', $systemUser);
            $actions[] = [
                'action' => 'closed',
                'user_id' => $userId,
                'previous_status' => $this->getPreviousStatusFromLogs($incidentId, 'closed'),
                'new_status' => 'closed',
                'reason' => 'Incident closed',
                'metadata' => json_encode([
                    'closed_at' => $incident->closed_at->toDateTimeString(),
                ]),
                'acted_at' => $toCarbon($incident->closed_at),
            ];
        }

        // 8. Rejected action
        if ($incident->rejection_reason) {
            $userId = $this->findActorFromLogs($incidentId, 'rejected', 'rejected', $systemUser);
            $actions[] = [
                'action' => 'rejected',
                'user_id' => $userId,
                'previous_status' => $this->getPreviousStatusFromLogs($incidentId, 'rejected'),
                'new_status' => 'rejected',
                'reason' => $incident->rejection_reason,
                'metadata' => json_encode([
                    'rejection_reason' => $incident->rejection_reason,
                ]),
                'acted_at' => $toCarbon($incident->updated_at),
            ];
        }

        // 9. Reopened action - Direct DB query
        $reopenLog = DB::table('incident_logs')
            ->where('incident_id', $incidentId)
            ->where(function ($query) {
                $query->where('action', 'reopened')
                    ->orWhere(function ($q) {
                        $q->where('action', 'status_changed')
                            ->where('new_values', 'like', '%"open"%')
                            ->where('old_values', 'like', '%"resolved"%');
                    });
            })
            ->whereNotNull('user_id')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($reopenLog) {
            $userName = DB::table('users')
                ->where('id', $reopenLog->user_id)
                ->value('name') ?? 'System';

            $actions[] = [
                'action' => 'reopened',
                'user_id' => $reopenLog->user_id ?? $systemUser->id,
                'previous_status' => 'resolved',
                'new_status' => 'open',
                'reason' => $reopenLog->description ?? 'Incident reopened',
                'metadata' => json_encode([
                    'reopened_by' => $userName,
                ]),
                'acted_at' => $toCarbon($reopenLog->created_at),
            ];
        }

        // Remove duplicates and sort
        $actions = $this->removeDuplicates($actions);
        $actions = $this->sortActions($actions);

        return $actions;
    }

    /**
     * Find actor from logs using direct DB queries.
     */
    private function findActorFromLogs(int $incidentId, string $action, string $status, $systemUser): int
    {
        $log = DB::table('incident_logs')
            ->where('incident_id', $incidentId)
            ->where(function ($query) use ($action, $status) {
                $query->where('action', $action)
                    ->orWhere(function ($q) use ($status) {
                        $q->where('action', 'status_changed')
                            ->where('new_values', 'like', '%"' . $status . '"%');
                    });
            })
            ->whereNotNull('user_id')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($log && $log->user_id) {
            return $log->user_id;
        }

        // Get incident details for fallback
        $incident = DB::table('incidents')
            ->where('id', $incidentId)
            ->first();

        if ($incident && $incident->assigned_to) {
            return $incident->assigned_to;
        }

        if ($incident && $incident->reported_by) {
            return $incident->reported_by;
        }

        return $systemUser->id;
    }

    /**
     * Get previous status from logs.
     */
    private function getPreviousStatusFromLogs(int $incidentId, string $action): string
    {
        $log = DB::table('incident_logs')
            ->where('incident_id', $incidentId)
            ->where('action', 'status_changed')
            ->where('new_values', 'like', '%"' . $action . '"%')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($log && $log->old_values) {
            $oldValues = json_decode($log->old_values, true);
            if (isset($oldValues['status'])) {
                return $oldValues['status'];
            }
        }

        return match ($action) {
            'resolved' => 'in_progress',
            'closed' => 'resolved',
            'rejected' => 'open',
            default => 'open',
        };
    }

    /**
     * Remove duplicate actions.
     */
    private function removeDuplicates(array $actions): array
    {
        $unique = [];
        $seen = [];

        foreach ($actions as $action) {
            // Convert acted_at to string for comparison
            $actedAt = $action['acted_at'];
            if ($actedAt instanceof Carbon) {
                $actedAtStr = $actedAt->format('Y-m-d_H:i:s');
            } else {
                $actedAtStr = (string) $actedAt;
            }

            $key = $action['action'] . '_' . $actedAtStr;

            if (!in_array($key, $seen)) {
                $seen[] = $key;
                $unique[] = $action;
            }
        }

        return $unique;
    }

    /**
     * Sort actions by acted_at timestamp.
     */
    private function sortActions(array $actions): array
    {
        usort($actions, function ($a, $b) {
            $aTime = $a['acted_at'] instanceof Carbon ? $a['acted_at'] : Carbon::parse($a['acted_at']);
            $bTime = $b['acted_at'] instanceof Carbon ? $b['acted_at'] : Carbon::parse($b['acted_at']);
            return $aTime <=> $bTime;
        });

        return $actions;
    }

    /**
     * Insert actions directly (no model).
     */
    private function insertActionsDirect(int $incidentId, array $actions): int
    {
        $inserted = 0;

        foreach ($actions as $action) {
            try {
                // Format acted_at for database
                $actedAt = $action['acted_at'];
                if ($actedAt instanceof Carbon) {
                    $actedAtStr = $actedAt->toDateTimeString();
                } else {
                    $actedAtStr = Carbon::parse($actedAt)->toDateTimeString();
                }

                // Check if action already exists
                $exists = DB::table('incident_status_actions')
                    ->where('incident_id', $incidentId)
                    ->where('action', $action['action'])
                    ->where('acted_at', $actedAtStr)
                    ->exists();

                if (!$exists) {
                    DB::table('incident_status_actions')->insert([
                        'incident_id' => $incidentId,
                        'action' => $action['action'],
                        'user_id' => $action['user_id'],
                        'previous_status' => $action['previous_status'],
                        'new_status' => $action['new_status'],
                        'reason' => $action['reason'],
                        'metadata' => $action['metadata'],
                        'acted_at' => $actedAtStr,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $inserted++;
                }
            } catch (\Exception $e) {
                Log::warning('Failed to insert action', [
                    'incident_id' => $incidentId,
                    'action' => $action['action'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $inserted;
    }
}