<?php

namespace App\Models;

use App\Events\IncidentCreated;
use App\Events\IncidentUpdated;
use App\Traits\HasAirport;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;

class Incident extends Model
{
    use HasFactory, SoftDeletes, HasAirport;

    protected $fillable = [
        'incident_id',
        'airport_id',
        'title',
        'description',
        'category_id',
        'severity',
        'priority',
        'status',
        'reported_by',
        'assigned_to',
        'department_id',
        'escalated_to',
        'location',
        'latitude',
        'longitude',
        'acknowledged_at',
        'in_progress_at',
        'escalated_at',
        'resolved_at',
        'closed_at',
        'sla_due_at',
        'sla_breach_count',
        'sla_breach_notified_at',
        'resolution_notes',
        'rejection_reason',
        'tags',
        'metadata',
        'is_anonymous',
        'views_count',
        'likes_count',
        'comments_count',
    ];

    protected $casts = [
        'acknowledged_at' => 'datetime',
        'in_progress_at' => 'datetime',
        'escalated_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'sla_due_at' => 'datetime',
        'sla_breach_notified_at' => 'datetime',
        'tags' => 'array',
        'metadata' => 'array',
        'is_anonymous' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    protected $appends = [
        'status_color',
        'severity_color',
        'priority_color',
        'status_label',
        'is_overdue',
        'response_time',
        'resolution_time',
        'timeline',
    ];

    // ==========================================
    // BOOT METHOD
    // ==========================================

    // protected static function boot()
    // {
    //     parent::boot();

    //     static::creating(function ($incident) {
    //         if (empty($incident->incident_id)) {
    //             $incident->incident_id = static::generateIncidentId();
    //         }

    //         if (empty($incident->sla_due_at) && $incident->category_id) {
    //             $category = IncidentCategory::find($incident->category_id);
    //             if ($category) {
    //                 $incident->sla_due_at = Carbon::now()->addMinutes($category->sla_minutes);
    //             }
    //         }
    //     });

    //     static::created(function ($incident) {
    //         $incident->logActivity('created', null, $incident->toArray());
    //         event(new IncidentCreated($incident));
    //     });

    //     static::updated(function ($incident) {
    //         if ($incident->isDirty('status')) {
    //             $incident->handleStatusChange(
    //                 $incident->getOriginal('status'),
    //                 $incident->status
    //             );
    //         }
    //     });

    //     static::deleted(function ($incident) {
    //         // Clean up media files
    //         foreach ($incident->media as $media) {
    //             \Storage::disk('public')->delete($media->file_path);
    //             if ($media->thumbnail_path) {
    //                 \Storage::disk('public')->delete($media->thumbnail_path);
    //             }
    //         }
    //     });
    // }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($incident) {
            if (empty($incident->incident_id)) {
                $incident->incident_id = static::generateIncidentId();
            }

            if (empty($incident->sla_due_at) && $incident->category_id) {
                $category = IncidentCategory::find($incident->category_id);
                if ($category) {
                    $incident->sla_due_at = Carbon::now()->addMinutes($category->sla_minutes);
                }
            }
        });

        // Add this new event to handle duplicate exceptions
        static::saving(function ($incident) {
            // If incident_id already exists, regenerate
            $attempts = 0;
            while (static::where('incident_id', $incident->incident_id)
                ->where('id', '!=', $incident->id)
                ->exists() && $attempts < 5) {

                $incident->incident_id = static::generateIncidentId();
                $attempts++;

                if ($attempts >= 5) {
                    throw new \Exception('Unable to generate unique incident ID after '.$attempts.' attempts');
                }
            }
        });

        static::created(function ($incident) {
            $incident->logActivity('created', null, $incident->toArray());
            event(new IncidentCreated($incident));
        });

        static::updated(function ($incident) {
            if ($incident->isDirty('status')) {
                $incident->handleStatusChange(
                    $incident->getOriginal('status'),
                    $incident->status
                );
            }
        });

        static::deleted(function ($incident) {
            // Clean up media files
            foreach ($incident->media as $media) {
                \Storage::disk('public')->delete($media->file_path);
                if ($media->thumbnail_path) {
                    \Storage::disk('public')->delete($media->thumbnail_path);
                }
            }
        });
    }

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Get all status actions for this incident.
     */
    public function statusActions(): HasMany
    {
        return $this->hasMany(IncidentStatusAction::class)->orderBy('acted_at', 'desc');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(IncidentCategory::class);
    }

    public function escalatedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_to');
    }

    public function media(): HasMany
    {
        return $this->hasMany(IncidentMedia::class)->orderBy('sort_order');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(IncidentComment::class)
            ->whereNull('parent_id')
            ->with(['user', 'replies.user'])
            ->orderBy('created_at', 'desc');
    }

    public function allComments(): HasMany
    {
        return $this->hasMany(IncidentComment::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(IncidentLog::class)->orderBy('created_at', 'desc');
    }

    public function escalations(): HasMany
    {
        return $this->hasMany(Escalation::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(IncidentAssignment::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(DatabaseNotification::class, 'data->incident_id')
            ->where('data->incident_id', $this->id);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'acknowledged', 'in_progress']);
    }

    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeByStatus($query, $status)
    {
        if (is_array($status)) {
            return $query->whereIn('status', $status);
        }

        return $query->where('status', $status);
    }

    public function scopeBySeverity($query, $severity)
    {
        if (is_array($severity)) {
            return $query->whereIn('severity', $severity);
        }

        return $query->where('severity', $severity);
    }

    public function scopeByPriority($query, $priority)
    {
        if (is_array($priority)) {
            return $query->whereIn('priority', $priority);
        }

        return $query->where('priority', $priority);
    }

    public function scopeCritical($query)
    {
        return $query->whereIn('severity', ['critical', 'high']);
    }

    public function scopeOverdue($query)
    {
        return $query->where('sla_due_at', '<', now())
            ->whereIn('status', ['open', 'acknowledged', 'in_progress', 'escalated']);
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeReportedBy($query, $userId)
    {
        return $query->where('reported_by', $userId);
    }

    public function scopeResolved($query)
    {
        return $query->whereIn('status', ['resolved', 'closed']);
    }

    public function scopeSlaBreached($query)
    {
        return $query->where('sla_breach_count', '>', 0);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('incident_id', 'like', "%{$search}%")
                ->orWhere('location', 'like', "%{$search}%");
        });
    }

    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'open' => '#3B82F6',
            'acknowledged' => '#F59E0B',
            'in_progress' => '#8B5CF6',
            'escalated' => '#EF4444',
            'resolved' => '#10B981',
            'closed' => '#6B7280',
            'rejected' => '#DC2626',
            default => '#6B7280'
        };
    }

    public function getSeverityColorAttribute(): string
    {
        return match ($this->severity) {
            'critical' => '#DC2626',
            'high' => '#EF4444',
            'medium' => '#F59E0B',
            'low' => '#10B981',
            default => '#6B7280'
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'critical' => '#DC2626',
            'high' => '#EF4444',
            'medium' => '#F59E0B',
            'low' => '#10B981',
            default => '#6B7280'
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return str_replace('_', ' ', ucfirst($this->status));
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->sla_due_at &&
               $this->sla_due_at->isPast() &&
               in_array($this->status, ['open', 'acknowledged', 'in_progress', 'escalated']);
    }

    public function getResponseTimeAttribute(): ?string
    {
        if ($this->acknowledged_at && $this->created_at) {
            $minutes = $this->created_at->diffInMinutes($this->acknowledged_at);

            return $this->formatDuration($minutes);
        }

        return null;
    }

    public function getResolutionTimeAttribute(): ?string
    {
        if ($this->resolved_at && $this->created_at) {
            $minutes = $this->created_at->diffInMinutes($this->resolved_at);

            return $this->formatDuration($minutes);
        }

        return null;
    }

    public function getTimelineAttribute(): array
    {
        $timeline = [];

        $events = [
            ['action' => 'Created', 'time' => $this->created_at, 'user' => $this->reporter, 'icon' => 'fa-plus-circle', 'color' => '#3B82F6'],
            ['action' => 'Acknowledged', 'time' => $this->acknowledged_at, 'user' => $this->assignedTo, 'icon' => 'fa-check-circle', 'color' => '#F59E0B'],
            ['action' => 'In Progress', 'time' => $this->in_progress_at, 'user' => $this->assignedTo, 'icon' => 'fa-spinner', 'color' => '#8B5CF6'],
            ['action' => 'Escalated', 'time' => $this->escalated_at, 'user' => $this->escalatedTo, 'icon' => 'fa-arrow-up', 'color' => '#EF4444'],
            ['action' => 'Resolved', 'time' => $this->resolved_at, 'user' => $this->assignedTo, 'icon' => 'fa-check-double', 'color' => '#10B981'],
            ['action' => 'Closed', 'time' => $this->closed_at, 'user' => null, 'icon' => 'fa-lock', 'color' => '#6B7280'],
        ];

        foreach ($events as $event) {
            if ($event['time']) {
                $timeline[] = [
                    'action' => $event['action'],
                    'user_name' => $event['user']?->name ?? 'System',
                    'timestamp' => $event['time']->format('d M Y, H:i'),
                    'icon' => $event['icon'],
                    'color' => $event['color'],
                ];
            }
        }

        return $timeline;
    }

    // ==========================================
    // STATUS MANAGEMENT METHODS
    // ==========================================

    // public function acknowledge(): void
    // {
    //     $this->update([
    //         'status' => 'acknowledged',
    //         'acknowledged_at' => now(),
    //     ]);
    // }

    public function startProgress(): void
    {
        $this->update([
            'status' => 'in_progress',
            'in_progress_at' => now(),
        ]);
    }

    // public function escalate(int $toUserId, string $reason): Escalation
    // {
    //     $escalation = $this->escalations()->create([
    //         'escalated_by' => auth()->id(),
    //         'escalated_to' => $toUserId,
    //         'from_department_id' => $this->department_id,
    //         'to_department_id' => User::find($toUserId)->department_id,
    //         'level' => $this->escalations()->count() + 1,
    //         'reason' => $reason,
    //         'status' => 'pending',
    //     ]);

    //     $this->update([
    //         'status' => 'escalated',
    //         'escalated_to' => $toUserId,
    //         'escalated_at' => now(),
    //     ]);

    //     return $escalation;
    // }

    // public function resolve(string $notes): void
    // {
    //     $this->update([
    //         'status' => 'resolved',
    //         'resolved_at' => now(),
    //         'resolution_notes' => $notes,
    //     ]);
    // }

    // public function close(): void
    // {
    //     $this->update([
    //         'status' => 'closed',
    //         'closed_at' => now(),
    //     ]);
    // }

    // public function reject(string $reason): void
    // {
    //     $this->update([
    //         'status' => 'rejected',
    //         'rejection_reason' => $reason,
    //     ]);
    // }

    // public function reopen(): void
    // {
    //     $this->update([
    //         'status' => 'open',
    //         'resolved_at' => null,
    //         'closed_at' => null,
    //         'resolution_notes' => null,
    //     ]);
    // }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    // public static function generateIncidentId(): string
    // {
    //     $prefix = 'INC';
    //     $year = date('Y');
    //     $lastIncident = static::whereYear('created_at', $year)
    //         ->orderBy('id', 'desc')
    //         ->first();

    //     $sequence = $lastIncident ? intval(substr($lastIncident->incident_id, -4)) + 1 : 1;

    //     return sprintf('%s-%s-%04d', $prefix, $year, $sequence);
    // }
    //     public static function generateIncidentId(): string
    // {
    //     $prefix = 'INC';
    //     $year = date('Y');
    //     $pattern = $prefix . '-' . $year . '-%';
    //     $maxAttempts = 3;
    //     $attempt = 0;

    //     while ($attempt < $maxAttempts) {
    //         $result = DB::transaction(function () use ($prefix, $year, $pattern) {
    //             // Get the maximum sequence number with lock
    //             $lastIncident = static::where('incident_id', 'LIKE', $pattern)
    //                 ->orderBy('id', 'desc')
    //                 ->lockForUpdate()
    //                 ->first();

    //             $sequence = 1;
    //             if ($lastIncident) {
    //                 // Extract sequence number properly
    //                 $expectedPrefix = $prefix . '-' . $year . '-';
    //                 $sequenceStr = substr($lastIncident->incident_id, strlen($expectedPrefix));
    //                 $sequence = intval($sequenceStr) + 1;
    //             }

    //             $newId = sprintf('%s-%s-%04d', $prefix, $year, $sequence);

    //             // Double-check that this ID doesn't already exist (safety)
    //             $exists = static::where('incident_id', $newId)->exists();

    //             return [
    //                 'id' => $newId,
    //                 'exists' => $exists,
    //                 'sequence' => $sequence
    //             ];
    //         });

    //         if (!$result['exists']) {
    //             return $result['id'];
    //         }

    //         $attempt++;
    //         \Log::warning('Duplicate incident_id detected, retrying...', [
    //             'attempt' => $attempt,
    //             'id' => $result['id'],
    //             'sequence' => $result['sequence']
    //         ]);

    //         // Small delay before retry
    //         usleep(50000); // 50ms
    //     }

    //     // Fallback: Use timestamp + microtime as last resort
    //     $fallbackId = sprintf('%s-%s-%s', $prefix, $year, date('YmdHis') . rand(100, 999));
    //     \Log::error('Failed to generate unique incident_id after retries, using fallback', [
    //         'fallback_id' => $fallbackId
    //     ]);

    //     return $fallbackId;
    // }

    public static function generateIncidentId(): string
    {
        $prefix = 'INC';
        $year = date('Y');
        $pattern = $prefix.'-'.$year.'-%';

        return DB::transaction(function () use ($prefix, $year, $pattern) {
            // Get all existing sequence numbers for this year
            $existingSequences = DB::table('incidents')
                ->where('incident_id', 'LIKE', $pattern)
                ->lockForUpdate()
                ->get()
                ->map(function ($item) use ($prefix, $year) {
                    $patternFull = $prefix.'-'.$year.'-';
                    $numStr = substr($item->incident_id, strlen($patternFull));

                    return (int) $numStr;
                })
                ->sort()
                ->values()
                ->toArray();

            // Find the first gap in the sequence
            $expectedSeq = 1;
            foreach ($existingSequences as $seq) {
                if ($seq > $expectedSeq) {
                    // Found a gap
                    break;
                }
                $expectedSeq = $seq + 1;
            }

            $sequence = $expectedSeq;

            // Safety check - max 99999
            if ($sequence > 99999) {
                throw new \Exception('Incident ID sequence exhausted for year '.$year);
            }

            $newId = sprintf('%s-%s-%04d', $prefix, $year, $sequence);

            // Double-check the ID doesn't exist (shouldn't happen with gap detection)
            $exists = DB::table('incidents')->where('incident_id', $newId)->exists();
            if ($exists) {
                throw new \Exception('Generated ID already exists: '.$newId);
            }

            return $newId;
        });
    }

    // public function logActivity(string $action, ?array $oldValues, ?array $newValues): void
    // {
    //     $this->logs()->create([
    //         'user_id' => auth()->id(),
    //         'action' => $action,
    //         'old_values' => $oldValues,
    //         'new_values' => $newValues,
    //         'description' => $this->getActionDescription($action),
    //         'ip_address' => request()->ip(),
    //         'user_agent' => request()->userAgent(),
    //     ]);
    // }


    public function logActivity(string $action, ?array $oldValues, ?array $newValues): void
    {
        // 1. Existing log entry (keep this)
        $this->logs()->create([
            'user_id' => auth()->id(),
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => $this->getActionDescription($action),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // 2. NEW: Record in incident_status_actions for status changes
        $statusActions = ['assigned', 'acknowledged', 'in_progress', 'escalated', 'resolved', 'closed', 'rejected', 'reopened'];

        if (in_array($action, $statusActions)) {
            $this->recordStatusAction($action, $oldValues, $newValues);
        }
    }

    /**
     * Record action in incident_status_actions table
     */
    protected function recordStatusAction(string $action, ?array $oldValues, ?array $newValues): void
    {
        $userId = auth()->id() ?? 1; // Fallback to system user

        $previousStatus = $oldValues['status'] ?? null;
        $newStatus = $newValues['status'] ?? $this->status;

        $reason = match ($action) {
            'assigned' => $newValues['assigned_to'] ?? 'Incident assigned',
            'acknowledged' => 'Incident acknowledged',
            'in_progress' => 'Work started on incident',
            'escalated' => $newValues['reason'] ?? 'Incident escalated',
            'resolved' => $this->resolution_notes ?? 'Incident resolved',
            'closed' => 'Incident closed',
            'rejected' => $this->rejection_reason ?? 'Incident rejected',
            'reopened' => 'Incident reopened',
            default => $this->getActionDescription($action),
        };

        // Use the relationship if it exists, or direct DB insert
        if (method_exists($this, 'statusActions') && $this->statusActions()->getRelated() instanceof \App\Models\IncidentStatusAction) {
            $this->statusActions()->create([
                'action' => $action,
                'user_id' => $userId,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'reason' => $reason,
                'metadata' => json_encode([
                    'old_values' => $oldValues,
                    'new_values' => $newValues,
                    'log_description' => $this->getActionDescription($action),
                ]),
                'acted_at' => now(),
            ]);
        } else {
            // Direct DB insert as fallback
            DB::table('incident_status_actions')->insert([
                'incident_id' => $this->id,
                'action' => $action,
                'user_id' => $userId,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'reason' => $reason,
                'metadata' => json_encode([
                    'old_values' => $oldValues,
                    'new_values' => $newValues,
                ]),
                'acted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function handleStatusChange(string $oldStatus, string $newStatus): void
    {
        $this->logActivity('status_changed', ['status' => $oldStatus], ['status' => $newStatus]);

        if (in_array($newStatus, ['open', 'acknowledged', 'in_progress', 'escalated']) &&
            $this->sla_due_at && $this->sla_due_at->isPast()) {
            $this->increment('sla_breach_count');
        }

        event(new IncidentUpdated($this, [
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ]));
    }

    private function getActionDescription(string $action): string
    {
        return match ($action) {
            'created' => 'Incident was created',
            'updated' => 'Incident details were updated',
            'status_changed' => "Status changed from {$this->getOriginal('status')} to {$this->status}",
            'assigned' => 'Incident assigned to '.($this->assignedTo->name ?? 'Unknown'),
            'escalated' => 'Incident escalated to '.($this->escalatedTo->name ?? 'Unknown'),
            'resolved' => 'Incident resolved with notes',
            'closed' => 'Incident closed',
            'reopened' => 'Incident reopened',
            'comment_added' => 'New comment added',
            'media_uploaded' => 'Media files uploaded',
            default => ucfirst(str_replace('_', ' ', $action)),
        };
    }

    private function formatDuration(int $minutes): string
    {
        if ($minutes < 60) {
            return "{$minutes} min";
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($remainingMinutes === 0) {
            return "{$hours} hr".($hours > 1 ? 's' : '');
        }

        return "{$hours} hr {$remainingMinutes} min";
    }

    public function checkSlaBreach(): bool
    {
        if ($this->sla_due_at && $this->sla_due_at->isPast() &&
            in_array($this->status, ['open', 'acknowledged', 'in_progress', 'escalated'])) {
            $this->increment('sla_breach_count');
            $this->update(['sla_breach_notified_at' => now()]);

            return true;
        }

        return false;
    }

    /**
     * Get escalation level (count of escalations)
     */
    public function getEscalationLevel(): int
    {
        return $this->escalations()->count();
    }

    public function getCurrentAssignee(): ?User
    {
        return $this->assignedTo;
    }

    public function isResolved(): bool
    {
        return in_array($this->status, ['resolved', 'closed']);
    }

    /**
     * Users who liked this incident
     */
    public function likes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'incident_likes')
            ->withTimestamps();
    }

    /**
     * Check if incident is liked by a specific user
     */
    public function isLikedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->likes()->where('user_id', $user->id)->exists();
    }

    //  New methods and relationships for handling historical and reopen case

    /**
     * Get assignment history for tracking
     */
    public function assignmentHistory(): HasMany
    {
        return $this->hasMany(IncidentAssignment::class)
            ->with(['assignedBy', 'assignedTo'])
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get current active assignment
     */
    public function currentAssignment(): ?IncidentAssignment
    {
        return $this->assignments()
            ->where('is_active', true)
            ->with(['assignedBy', 'assignedTo'])
            ->first();
    }

    /**
     * Check if incident can be reopened
     */
    public function canBeReopened(): bool
    {
        return in_array($this->status, ['resolved', 'closed']);
    }

    /**
     * Check if incident can be rejected
     */
    public function canBeRejected(): bool
    {
        return in_array($this->status, ['open', 'acknowledged']);
    }

    /**
     * Check if incident can be reassigned
     */
    public function canBeReassigned(): bool
    {
        return in_array($this->status, ['open', 'acknowledged', 'in_progress', 'escalated']);
    }

    /**
     * Get share data for WhatsApp/web sharing
     */
    public function getShareData(): array
    {
        return [
            'title' => "Incident #{$this->incident_id}: {$this->title}",
            'description' => \Str::limit(strip_tags($this->description), 200),
            'status' => ucfirst(str_replace('_', ' ', $this->status)),
            'severity' => ucfirst($this->severity),
            'priority' => ucfirst($this->priority),
            'department' => $this->department?->name,
            'category' => $this->category?->name,
            'location' => $this->location,
            'reported_by' => $this->is_anonymous ? 'Anonymous' : $this->reporter?->name,
            'reported_at' => $this->created_at->format('d M Y, H:i'),
            'url' => route('incidents.show', $this->id),
        ];
    }

    /**
     * Get WhatsApp share URL
     */
    public function getWhatsAppShareUrl(): string
    {
        $text = "*Incident #{$this->incident_id}: {$this->title}*\n";
        $text .= 'Status: '.ucfirst(str_replace('_', ' ', $this->status))."\n";
        $text .= 'Severity: '.ucfirst($this->severity).' | Priority: '.ucfirst($this->priority)."\n";
        $text .= 'Department: '.($this->department?->name ?? 'N/A')."\n";
        if ($this->location) {
            $text .= "Location: {$this->location}\n";
        }
        $text .= "\nView Details: ".route('incidents.show', $this->id);

        return 'https://wa.me/?text='.urlencode($text);
    }

    /**
     * Check if user can take action on this incident
     */
    // public function canTakeAction(?User $user): bool
    // {
    //     if (!$user) return false;

    //     // Admin can always take action
    //     if ($user->isAdmin()) return true;

    //     // Assigned user can take action
    //     if ($this->assigned_to === $user->id) return true;

    //     // Currently escalated user can take action
    //     if ($this->escalated_to === $user->id && $this->status === 'escalated') return true;

    //     // Reporter can view but limited actions
    //     if ($this->reported_by === $user->id) return true;

    //     // HOD of the department can take action
    //     if ($user->isHOD() && $user->department_id === $this->department_id) return true;

    //     return false;
    // }

    /**
     * Check if user can take action on this incident
     */
    public function canTakeAction(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        // Admin can always take action
        if ($user->isAdmin()) {
            return true;
        }

        // Assigned user can take action
        if ($this->assigned_to === $user->id) {
            return true;
        }

        // Currently escalated user can take action
        if ($this->escalated_to === $user->id && $this->status === 'escalated') {
            return true;
        }

        // Reporter can take limited action
        if ($this->reported_by === $user->id && in_array($this->status, ['open', 'acknowledged'])) {
            return true;
        }

        // HOD of the incident's department can take action
        if ($user->isHOD() && $user->department_id === $this->department_id) {
            return true;
        }

        // Supervisor of the incident's department can take action
        if ($user->isSupervisor() && $user->department_id === $this->department_id) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can view this incident (broader access than canTakeAction)
     */
    public function canBeViewedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        // Admin can view all
        if ($user->isAdmin()) {
            return true;
        }

        // Anyone who can take action can view
        if ($this->canTakeAction($user)) {
            return true;
        }

        // Reporter can always view their own incidents
        if ($this->reported_by === $user->id) {
            return true;
        }

        // Anyone in the same department can view
        if ($user->department_id === $this->department_id) {
            return true;
        }

        // Anyone who was ever escalated to can view
        $wasEscalated = Escalation::where('incident_id', $this->id)
            ->where('escalated_to', $user->id)
            ->exists();
        if ($wasEscalated) {
            return true;
        }

        // Anyone who was ever assigned can view
        $wasAssigned = IncidentAssignment::where('incident_id', $this->id)
            ->where('assigned_to', $user->id)
            ->exists();
        if ($wasAssigned) {
            return true;
        }

        return false;
    }

    /**
     * Get pending escalations for a user
     */
    public static function pendingEscalationsForUser(int $userId)
    {
        return static::where('escalated_to', $userId)
            ->where('status', 'escalated')
            ->with(['department', 'category', 'reporter', 'assignedTo'])
            ->latest('escalated_at')
            ->get();
    }

    /**
     * Get incidents assigned to user
     */
    public static function assignedToUser(int $userId)
    {
        return static::where('assigned_to', $userId)
            ->whereIn('status', ['open', 'acknowledged', 'in_progress'])
            ->with(['department', 'category', 'reporter'])
            ->latest()
            ->get();
    }

    public function rejectionEscalation()
    {
        return $this->hasOne(Escalation::class)
            ->where('response_type', 'rejected')
            ->whereNotNull('responded_by')
            ->latest('responded_at');
    }

    public function rejectionResponder()
    {
        return $this->hasOneThrough(
            User::class,
            Escalation::class,
            'incident_id',    // Foreign key on escalations table
            'id',              // Foreign key on users table
            'id',              // Local key on incidents table
            'responded_by'     // Local key on escalations table
        )->where('escalations.response_type', 'rejected');
    }






     // ==========================================
    // STATUS MANAGEMENT METHODS WITH REASON
    // ==========================================

    /**
     * Acknowledge the incident with reason.--
     */
    public function acknowledge(?string $reason = null, ?User $actor = null): void
    {
        $actor = $actor ?? auth()->user();
        $oldStatus = $this->status;

        $this->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
        ]);

        $this->statusActions()->create([
            'action' => 'acknowledged',
            'user_id' => $actor?->id ?? 1,
            'previous_status' => $oldStatus,
            'new_status' => 'acknowledged',
            'reason' => $reason ?? 'Incident acknowledged',
            'metadata' => [
                'acknowledged_by' => $actor?->name,
                'acknowledged_at' => now()->toDateTimeString(),
            ],
            'acted_at' => now(),
        ]);

        $this->logActivity('acknowledged', null, [
            'acknowledged_by' => $actor?->name,
            'reason' => $reason,
        ]);
    }

    /**
     * Resolve the incident with resolution notes.--
     */
    public function resolve(string $notes, ?User $actor = null): void
    {
        $actor = $actor ?? auth()->user();
        $oldStatus = $this->status;

        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolution_notes' => $notes,
        ]);

        $this->statusActions()->create([
            'action' => 'resolved',
            'user_id' => $actor?->id ?? 1,
            'previous_status' => $oldStatus,
            'new_status' => 'resolved',
            'reason' => $notes,
            'metadata' => [
                'resolved_by' => $actor?->name,
                'resolved_at' => now()->toDateTimeString(),
            ],
            'acted_at' => now(),
        ]);

        $this->logActivity('resolved', null, [
            'resolved_by' => $actor?->name,
            'resolution_notes' => $notes,
        ]);
    }

    /**
     * Close the incident with closing remarks.--
     */
    public function close(?string $remarks = null, ?User $actor = null): void
    {
        $actor = $actor ?? auth()->user();
        $oldStatus = $this->status;

        $this->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        $this->statusActions()->create([
            'action' => 'closed',
            'user_id' => $actor?->id ?? 1,
            'previous_status' => $oldStatus,
            'new_status' => 'closed',
            'reason' => $remarks ?? 'Incident closed',
            'metadata' => [
                'closed_by' => $actor?->name,
                'closed_at' => now()->toDateTimeString(),
            ],
            'acted_at' => now(),
        ]);

        $this->logActivity('closed', null, [
            'closed_by' => $actor?->name,
            'remarks' => $remarks,
        ]);
    }

    /**
     * Reject the incident with rejection reason.--
     */
    public function reject(string $reason, ?User $actor = null): void
    {
        $actor = $actor ?? auth()->user();
        $oldStatus = $this->status;

        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);

        $this->statusActions()->create([
            'action' => 'rejected',
            'user_id' => $actor?->id ?? 1,
            'previous_status' => $oldStatus,
            'new_status' => 'rejected',
            'reason' => $reason,
            'metadata' => [
                'rejected_by' => $actor?->name,
                'rejected_at' => now()->toDateTimeString(),
            ],
            'acted_at' => now(),
        ]);

        $this->logActivity('rejected', null, [
            'rejected_by' => $actor?->name,
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Reopen the incident with reason.--
     */
    public function reopen(?string $reason = null, ?User $actor = null): void
    {
        $actor = $actor ?? auth()->user();
        $oldStatus = $this->status;

        $this->update([
            'status' => 'open',
            'resolved_at' => null,
            'closed_at' => null,
            'resolution_notes' => null,
        ]);

        $this->statusActions()->create([
            'action' => 'reopened',
            'user_id' => $actor?->id ?? 1,
            'previous_status' => $oldStatus,
            'new_status' => 'open',
            'reason' => $reason ?? 'Incident reopened',
            'metadata' => [
                'reopened_by' => $actor?->name,
                'reopened_at' => now()->toDateTimeString(),
                'previous_status' => $oldStatus,
            ],
            'acted_at' => now(),
        ]);

        $this->logActivity('reopened', null, [
            'reopened_by' => $actor?->name,
            'reason' => $reason,
        ]);
    }

    /**
     * Escalate the incident with reason (uses existing escalations table).--
     */
    public function escalate(int $toUserId, string $reason, ?User $actor = null): Escalation
    {
        $actor = $actor ?? auth()->user();
        $oldStatus = $this->status;

        $escalation = $this->escalations()->create([
            'escalated_by' => $actor?->id ?? 1,
            'escalated_to' => $toUserId,
            'from_department_id' => $this->department_id,
            'to_department_id' => User::find($toUserId)?->department_id,
            'level' => $this->escalations()->count() + 1,
            'reason' => $reason,
            'status' => 'pending',
        ]);

        $this->update([
            'status' => 'escalated',
            'escalated_to' => $toUserId,
            'escalated_at' => now(),
        ]);

        // Also record in status_actions for consistency
        $this->statusActions()->create([
            'action' => 'escalated',
            'user_id' => $actor?->id ?? 1,
            'previous_status' => $oldStatus,
            'new_status' => 'escalated',
            'reason' => $reason,
            'metadata' => [
                'escalated_by' => $actor?->name,
                'escalated_to' => User::find($toUserId)?->name,
                'escalation_id' => $escalation->id,
                'level' => $escalation->level,
            ],
            'acted_at' => now(),
        ]);

        $this->logActivity('escalated', null, [
            'escalated_by' => $actor?->name,
            'escalated_to' => User::find($toUserId)?->name,
            'reason' => $reason,
        ]);

        return $escalation;
    }

    /**
     * Assign the incident with notes.
     */
    public function assign(int $toUserId, ?string $notes = null, ?User $actor = null): IncidentAssignment
    {
        $actor = $actor ?? auth()->user();
        $oldStatus = $this->status;
        $oldAssigneeId = $this->assigned_to;

        // Deactivate current assignment
        $currentAssignment = $this->currentAssignment();
        if ($currentAssignment) {
            $currentAssignment->update([
                'is_active' => false,
                'unassigned_at' => now(),
            ]);
        }

        // Create new assignment
        $assignment = $this->assignments()->create([
            'assigned_by' => $actor?->id ?? 1,
            'assigned_to' => $toUserId,
            'notes' => $notes ?? ($this->assigned_to ? 'Reassigned' : 'Assigned'),
            'assigned_at' => now(),
            'is_active' => true,
        ]);

        $newStatus = $this->status === 'open' ? 'acknowledged' : $this->status;

        $this->update([
            'assigned_to' => $toUserId,
            'status' => $newStatus,
            'acknowledged_at' => $this->status === 'open' ? now() : $this->acknowledged_at,
        ]);

        // Also record in status_actions for consistency
        $this->statusActions()->create([
            'action' => 'assigned',
            'user_id' => $actor?->id ?? 1,
            'previous_status' => $oldStatus,
            'new_status' => $newStatus,
            'reason' => $notes ?? ($oldAssigneeId ? 'Reassigned' : 'Assigned'),
            'metadata' => [
                'assigned_by' => $actor?->name,
                'assigned_to' => User::find($toUserId)?->name,
                'assignment_id' => $assignment->id,
                'previous_assignee' => $oldAssigneeId ? User::find($oldAssigneeId)?->name : null,
            ],
            'acted_at' => now(),
        ]);

        $this->logActivity('assigned',
            ['assigned_to' => $oldAssigneeId],
            ['assigned_to' => User::find($toUserId)?->name, 'assigned_by' => $actor?->name]
        );

        return $assignment;
    }

    // ==========================================
    // GET ACTOR METHODS
    // ==========================================

    public function getResolvedByActor(): ?User
    {
        $action = $this->statusActions()
            ->where('action', 'resolved')
            ->latest('acted_at')
            ->first();

        return $action?->user;
    }

    public function getClosedByActor(): ?User
    {
        $action = $this->statusActions()
            ->where('action', 'closed')
            ->latest('acted_at')
            ->first();

        return $action?->user;
    }

    public function getRejectedByActor(): ?User
    {
        $action = $this->statusActions()
            ->where('action', 'rejected')
            ->latest('acted_at')
            ->first();

        return $action?->user;
    }

    public function getReopenedByActor(): ?User
    {
        $action = $this->statusActions()
            ->where('action', 'reopened')
            ->latest('acted_at')
            ->first();

        return $action?->user;
    }

    public function getAcknowledgedByActor(): ?User
    {
        $action = $this->statusActions()
            ->where('action', 'acknowledged')
            ->latest('acted_at')
            ->first();

        return $action?->user;
    }

    public function getEscalatedByActor(): ?User
    {
        $action = $this->statusActions()
            ->where('action', 'escalated')
            ->latest('acted_at')
            ->first();

        return $action?->user;
    }

    public function getAssignedByActor(): ?User
    {
        $action = $this->statusActions()
            ->where('action', 'assigned')
            ->latest('acted_at')
            ->first();

        return $action?->user;
    }

    // ==========================================
    // GET REASON METHODS
    // ==========================================

    public function getResolutionReason(): ?string
    {
        $action = $this->statusActions()
            ->where('action', 'resolved')
            ->latest('acted_at')
            ->first();

        return $action?->reason ?? $this->resolution_notes;
    }

    public function getClosureReason(): ?string
    {
        $action = $this->statusActions()
            ->where('action', 'closed')
            ->latest('acted_at')
            ->first();

        return $action?->reason;
    }

    public function getRejectionReason(): ?string
    {
        $action = $this->statusActions()
            ->where('action', 'rejected')
            ->latest('acted_at')
            ->first();

        return $action?->reason ?? $this->rejection_reason;
    }

    public function getReopenReason(): ?string
    {
        $action = $this->statusActions()
            ->where('action', 'reopened')
            ->latest('acted_at')
            ->first();

        return $action?->reason;
    }

    // ==========================================
    // COMPLETE ACTION HISTORY
    // ==========================================

    // public function getCompleteActionHistory(): array
    // {
    //     return $this->statusActions()
    //         ->with('user')
    //         ->latestFirst()
    //         ->get()
    //         ->map(function ($action) {
    //             return [
    //                 'id' => $action->id,
    //                 'action' => $action->action_label,
    //                 'icon' => $action->action_icon,
    //                 'color' => $action->action_color,
    //                 'user' => $action->user?->name ?? 'System',
    //                 'user_avatar' => $action->user?->avatar_url,
    //                 'from_status' => $action->previous_status ? ucfirst(str_replace('_', ' ', $action->previous_status)) : null,
    //                 'to_status' => $action->new_status ? ucfirst(str_replace('_', ' ', $action->new_status)) : null,
    //                 'reason' => $action->formatted_reason,
    //                 'metadata' => $action->metadata,
    //                 'acted_at' => $action->formatted_acted_at,
    //                 'acted_at_raw' => $action->acted_at->toDateTimeString(),
    //             ];
    //         })
    //         ->toArray();
    // }

    // In app/Models/Incident.php

public function getCompleteActionHistory(): array
{
    return $this->statusActions()
        ->with('user')
        ->latestFirst()
        ->get()
        ->map(function ($action) {
            // Check if this is a rejection action
            $isRejection = in_array($action->action, ['rejected', 'rejected_escalation']);

            return [
                'id' => $action->id,
                'action' => $action->action_label,
                'icon' => $action->action_icon,
                'color' => $action->action_color,
                'user' => $action->user?->name ?? 'System',
                'user_avatar' => $action->user?->avatar_url,
                'from_status' => $action->previous_status ? ucfirst(str_replace('_', ' ', $action->previous_status)) : null,
                'to_status' => $action->new_status ? ucfirst(str_replace('_', ' ', $action->new_status)) : null,
                'reason' => $action->formatted_reason,
                'metadata' => $action->metadata,
                'is_rejection' => $isRejection,
                'acted_at' => $action->formatted_acted_at,
                'acted_at_raw' => $action->acted_at->toDateTimeString(),
            ];
        })
        ->toArray();
}

    public function getActionSummary(): array
    {
        return [
            'total_actions' => $this->statusActions()->count(),
            'resolutions' => $this->statusActions()->where('action', 'resolved')->count(),
            'closures' => $this->statusActions()->where('action', 'closed')->count(),
            'rejections' => $this->statusActions()->where('action', 'rejected')->count(),
            'reopens' => $this->statusActions()->where('action', 'reopened')->count(),
            'acknowledgements' => $this->statusActions()->where('action', 'acknowledged')->count(),
            'escalations' => $this->statusActions()->where('action', 'escalated')->count(),
            'assignments' => $this->statusActions()->where('action', 'assigned')->count(),
        ];
    }

    public function getStatusChanges(): array
    {
        return $this->statusActions()
            ->whereIn('action', ['acknowledged', 'resolved', 'closed', 'rejected', 'reopened', 'escalated'])
            ->with('user')
            ->latestFirst()
            ->get()
            ->map(function ($action) {
                return [
                    'from' => $action->previous_status ? ucfirst(str_replace('_', ' ', $action->previous_status)) : 'Created',
                    'to' => ucfirst(str_replace('_', ' ', $action->new_status)),
                    'by' => $action->user?->name ?? 'System',
                    'reason' => $action->formatted_reason,
                    'at' => $action->formatted_acted_at,
                ];
            })
            ->toArray();
    }

    /**
     * Get actor for notification based on status.
     */
    public function getActorForNotification(string $notificationType): ?User
    {
        return match ($notificationType) {
            'assigned' => $this->getAssignedByActor(),
            'acknowledged' => $this->getAcknowledgedByActor(),
            'escalated' => $this->getEscalatedByActor(),
            'resolved' => $this->getResolvedByActor(),
            'closed' => $this->getClosedByActor(),
            'rejected' => $this->getRejectedByActor(),
            'reopened' => $this->getReopenedByActor(),
            default => $this->getPrimaryActor(),
        };
    }

    public function getPrimaryActor(): ?User
    {
        $latestAction = $this->statusActions()->latestFirst()->first();
        if ($latestAction) {
            return $latestAction->user;
        }

        return $this->reporter;
    }
}
