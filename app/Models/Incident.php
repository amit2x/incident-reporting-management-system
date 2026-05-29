<?php

namespace App\Models;

use App\Events\IncidentCreated;
use App\Events\IncidentUpdated;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\DatabaseNotification;

class Incident extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'incident_id',
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

    public function acknowledge(): void
    {
        $this->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
        ]);
    }

    public function startProgress(): void
    {
        $this->update([
            'status' => 'in_progress',
            'in_progress_at' => now(),
        ]);
    }

    public function escalate(int $toUserId, string $reason): Escalation
    {
        $escalation = $this->escalations()->create([
            'escalated_by' => auth()->id(),
            'escalated_to' => $toUserId,
            'from_department_id' => $this->department_id,
            'to_department_id' => User::find($toUserId)->department_id,
            'level' => $this->escalations()->count() + 1,
            'reason' => $reason,
            'status' => 'pending',
        ]);

        $this->update([
            'status' => 'escalated',
            'escalated_to' => $toUserId,
            'escalated_at' => now(),
        ]);

        return $escalation;
    }

    public function resolve(string $notes): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolution_notes' => $notes,
        ]);
    }

    public function close(): void
    {
        $this->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);
    }

    public function reject(string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);
    }

    public function reopen(): void
    {
        $this->update([
            'status' => 'open',
            'resolved_at' => null,
            'closed_at' => null,
            'resolution_notes' => null,
        ]);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    public static function generateIncidentId(): string
    {
        $prefix = 'INC';
        $year = date('Y');
        $lastIncident = static::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastIncident ? intval(substr($lastIncident->incident_id, -4)) + 1 : 1;

        return sprintf('%s-%s-%04d', $prefix, $year, $sequence);
    }

    public function logActivity(string $action, ?array $oldValues, ?array $newValues): void
    {
        $this->logs()->create([
            'user_id' => auth()->id(),
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => $this->getActionDescription($action),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
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

    // app/Models/Incident.php

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
}
