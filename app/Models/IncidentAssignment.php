<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'incident_id',
        'assigned_by',
        'assigned_to',
        'notes',
        'assigned_at',
        'unassigned_at',
        'is_active',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'unassigned_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'duration',
        'assignment_duration_formatted',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * The incident being assigned
     */
    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    /**
     * User who made the assignment
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * User assigned to the incident
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Active assignments only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Completed/inactive assignments
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Assignments for a specific user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Assignments made by a specific user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('assigned_by', $userId);
    }

    /**
     * Assignments for a specific incident
     */
    public function scopeForIncident($query, int $incidentId)
    {
        return $query->where('incident_id', $incidentId);
    }

    /**
     * Recent assignments
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    /**
     * Get assignment duration
     */
    public function getDurationAttribute(): ?int
    {
        if (!$this->assigned_at) {
            return null;
        }

        $endTime = $this->unassigned_at ?? now();
        return $this->assigned_at->diffInMinutes($endTime);
    }

    /**
     * Get formatted assignment duration
     */
    public function getAssignmentDurationFormattedAttribute(): ?string
    {
        $duration = $this->duration;

        if ($duration === null) {
            return null;
        }

        if ($duration < 60) {
            return "{$duration} min";
        }

        $hours = floor($duration / 60);
        $minutes = $duration % 60;

        if ($minutes === 0) {
            return "{$hours} hr" . ($hours > 1 ? 's' : '');
        }

        return "{$hours}h {$minutes}m";
    }

    /**
     * Check if assignment is still active
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->is_active && is_null($this->unassigned_at);
    }

    // ==========================================
    // ACTION METHODS
    // ==========================================

    /**
     * Unassign the incident
     */
    public function unassign(?string $notes = null): void
    {
        $this->update([
            'is_active' => false,
            'unassigned_at' => now(),
            'notes' => $notes ? $this->notes . "\nUnassignment note: " . $notes : $this->notes,
        ]);

        // Also update incident if this is the current assignment
        if ($this->incident->assigned_to === $this->assigned_to) {
            $this->incident->update(['assigned_to' => null]);
        }
    }

    /**
     * Reassign to another user
     */
    public function reassign(int $newUserId, ?string $notes = null): IncidentAssignment
    {
        // Deactivate current assignment
        $this->unassign($notes ? "Reassigned: " . $notes : "Reassigned to another user");

        // Create new assignment
        return static::create([
            'incident_id' => $this->incident_id,
            'assigned_by' => auth()->id(),
            'assigned_to' => $newUserId,
            'notes' => $notes ?? 'Reassigned from previous assignee',
            'assigned_at' => now(),
            'is_active' => true,
        ]);
    }

    /**
     * Get assignment history for an incident
     */
    public static function getAssignmentHistory(int $incidentId): array
    {
        return static::where('incident_id', $incidentId)
            ->with(['assignedBy', 'assignedTo'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get current active assignment for an incident
     */
    public static function getCurrentAssignment(int $incidentId): ?self
    {
        return static::where('incident_id', $incidentId)
            ->active()
            ->with(['assignedBy', 'assignedTo'])
            ->first();
    }

    /**
     * Get user's current workload
     */
    public static function getUserWorkload(int $userId): int
    {
        return static::where('assigned_to', $userId)
            ->active()
            ->count();
    }
}
