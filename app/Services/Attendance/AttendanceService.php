<?php

// app/Services/Attendance/AttendanceService.php

namespace App\Services\Attendance;

use App\Models\Airport;
use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function __construct(private GeofenceService $geofence) {}

    /**
     * User marks own attendance.
     */
    public function markSelf(User $user, float $lat, float $lng, string $timezone): Attendance
    {
        $airport = $user->airport;
        $settings = $airport->attendanceSettings();

        // ── 1. Self-marking enabled?
        if (! $settings['allow_self_mark']) {
            throw ValidationException::withMessages([
                'attendance' => 'Self-marking is currently disabled for your airport.',
            ]);
        }

        // ── 2. Time window check
        $now = Carbon::now($settings['timezone']);
        $start = Carbon::createFromTimeString($settings['self_mark_start_time'], $settings['timezone']);
        $end = Carbon::createFromTimeString($settings['self_mark_end_time'], $settings['timezone']);

        if ($now->lt($start) || $now->gt($end)) {
            throw ValidationException::withMessages([
                'attendance' => sprintf(
                    'Self-marking is only allowed between %s and %s.',
                    $settings['self_mark_start_time'],
                    $settings['self_mark_end_time']
                ),
            ]);
        }

        // ── 3. Geofence
        if ($settings['location_check_required']) {
            $check = $this->geofence->check($airport, $lat, $lng);

            if (! $check['is_within']) {
                throw ValidationException::withMessages([
                    'location' => sprintf(
                        'You are %dm away from the designated location (max %dm allowed).',
                        $check['distance'],
                        $check['radius']
                    ),
                ]);
            }
        }

        // ── 4. Already marked?
        $existing = Attendance::forUser($user->id)
            ->forDate($now->toDateString())
            ->first();

        if ($existing) {
            if ($existing->marked_by_role !== 'self') {
                throw ValidationException::withMessages([
                    'attendance' => sprintf(
                        'Attendance already marked by %s at %s.',
                        $existing->marked_by_label,
                        $existing->marked_at->format('h:i A')
                    ),
                ]);
            }

            throw ValidationException::withMessages([
                'attendance' => 'You have already marked your attendance today.',
            ]);
        }

        // ── 5. Create
        return DB::transaction(function () use ($user, $lat, $lng, $now, $check) {
            $attendance = Attendance::create([
                'airport_id' => $user->airport_id,
                'user_id' => $user->id,
                'attendance_date' => $now->toDateString(),
                'status' => 'present',
                'marked_by' => $user->id,
                'marked_by_role' => 'self',
                'marked_at' => now(),
                'latitude' => $lat,
                'longitude' => $lng,
                'location_verified' => $check['is_within'] ?? false,
                'distance_from_geofence' => $check['distance'] ?? null,
            ]);

            $this->log($attendance, 'created', null, $attendance->toArray(), Auth::id(), 'Self-marked');

            return $attendance;
        });
    }

    /**
     * Nodal Officer marks attendance (bulk or individual).
     *
     * @param  string  $date  YYYY-MM-DD
     * @return array ['created' => [], 'skipped' => [], 'updated' => []]
     */
    public function markByNodal(
        User $nodal,
        array $userIds,
        string $date,
        string $status = 'present',
        ?string $remarks = null
    ): array {
        $dateObj = Carbon::parse($date);
        $today = Carbon::today($nodal->airport->timezone);

        // ── 1. Backfill check
        $isBackfilled = $dateObj->lt($today);
        if ($isBackfilled && $dateObj->lt($today->copy()->subDays(7))) {
            throw ValidationException::withMessages([
                'date' => 'You can only backfill attendance within the last 7 days.',
            ]);
        }
        if ($dateObj->gt($today)) {
            throw ValidationException::withMessages([
                'date' => 'Cannot mark attendance for a future date.',
            ]);
        }

        // ── 2. Validate all users belong to same airport
        $validUsers = User::whereIn('id', $userIds)
            ->where('airport_id', $nodal->airport_id)
            ->active()
            ->pluck('id')
            ->toArray();

        $invalidIds = array_diff($userIds, $validUsers);
        if (! empty($invalidIds)) {
            throw ValidationException::withMessages([
                'users' => 'Some users do not belong to your airport or are inactive.',
            ]);
        }

        $results = ['created' => [], 'skipped' => [], 'updated' => []];

        DB::transaction(function () use (
            $validUsers, $nodal, $dateObj, $status, $remarks,
            $isBackfilled, &$results
        ) {
            foreach ($validUsers as $uid) {
                $existing = Attendance::forUser($uid)
                    ->forDate($dateObj->toDateString())
                    ->first();

                if ($existing) {
                    if ($existing->status === $status) {
                        // Same status — skip (avoid spurious log entries)
                        $results['skipped'][] = $uid;

                        continue;
                    }

                    // Different status — override
                    $old = $existing->toArray();
                    $existing->update([
                        'status' => $status,
                        'marked_by' => $nodal->id,
                        'marked_by_role' => 'nodal_officer',
                        'marked_at' => now(),
                        'is_modified' => true,
                        'modified_by' => $nodal->id,
                        'modified_at' => now(),
                        'modification_reason' => $remarks ?? 'Overridden by Nodal Officer',
                        'remarks' => $remarks,
                    ]);

                    $this->log($existing, 'overridden', $old, $existing->fresh()->toArray(),
                        $nodal->id, $remarks ?? 'Overridden by Nodal Officer');

                    $results['updated'][] = $uid;

                    continue;
                }

                // Create new
                $attendance = Attendance::create([
                    'airport_id' => $nodal->airport_id,
                    'user_id' => $uid,
                    'attendance_date' => $dateObj->toDateString(),
                    'status' => $status,
                    'marked_by' => $nodal->id,
                    'marked_by_role' => 'nodal_officer',
                    'marked_at' => now(),
                    'is_backfilled' => $isBackfilled,
                    'remarks' => $remarks,
                ]);

                $this->log($attendance, 'created', null, $attendance->toArray(),
                    $nodal->id, $remarks ?? 'Marked by Nodal Officer');

                $results['created'][] = $uid;
            }
        });

        return $results;
    }

    /**
     * Nodal Officer overrides an existing attendance (e.g., rejected self-mark).
     */
    public function override(User $nodal, Attendance $attendance, string $status, string $reason): Attendance
    {
        if ($attendance->airport_id !== $nodal->airport_id) {
            throw ValidationException::withMessages([
                'attendance' => 'You cannot modify attendance from another airport.',
            ]);
        }

        $old = $attendance->toArray();

        $attendance->update([
            'status' => $status,
            'is_modified' => true,
            'modified_by' => $nodal->id,
            'modified_at' => now(),
            'modification_reason' => $reason,
        ]);

        $this->log($attendance, 'overridden', $old, $attendance->fresh()->toArray(),
            $nodal->id, $reason);

        return $attendance->fresh();
    }

    /**
     * Write an audit log entry.
     */
    private function log(
        Attendance $attendance,
        string $action,
        ?array $old,
        ?array $new,
        ?int $performedBy,
        ?string $reason
    ): void {
        AttendanceLog::create([
            'attendance_id' => $attendance->id,
            'airport_id' => $attendance->airport_id,
            'action' => $action,
            'old_values' => $old,
            'new_values' => $new,
            'reason' => $reason,
            'performed_by' => $performedBy,
            'ip_address' => request()->ip(),
        ]);
    }
}
