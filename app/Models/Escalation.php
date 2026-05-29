<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Escalation extends Model
{
    use HasFactory;

    protected $fillable = [
        'incident_id',
        'escalated_by',
        'escalated_to',
        'from_department_id',
        'to_department_id',
        'level',
        'reason',
        'response',
        'status',
        'responded_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
        'level' => 'integer',
    ];

    protected $appends = [
        'status_color',
        'is_pending',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * The incident being escalated
     */
    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    /**
     * User who initiated the escalation
     */
    public function escalatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_by');
    }

    /**
     * User to whom the incident is escalated
     */
    public function escalatedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_to');
    }

    /**
     * Department from which escalated
     */
    public function fromDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'from_department_id');
    }

    /**
     * Department to which escalated
     */
    public function toDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'to_department_id');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Pending escalations
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Accepted escalations
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Rejected escalations
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * By escalation level
     */
    public function scopeByLevel($query, int $level)
    {
        return $query->where('level', $level);
    }

    /**
     * By department
     */
    public function scopeByDepartment($query, int $departmentId)
    {
        return $query->where(function ($q) use ($departmentId) {
            $q->where('from_department_id', $departmentId)
              ->orWhere('to_department_id', $departmentId);
        });
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    /**
     * Get status color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => '#F59E0B',
            'accepted' => '#10B981',
            'rejected' => '#EF4444',
            default => '#6B7280'
        };
    }

    /**
     * Check if escalation is pending
     */
    public function getIsPendingAttribute(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Get level label
     */
    public function getLevelLabelAttribute(): string
    {
        return match($this->level) {
            1 => 'Level 1 - Supervisor',
            2 => 'Level 2 - HOD',
            3 => 'Level 3 - Admin',
            4 => 'Level 4 - Director',
            default => "Level {$this->level}",
        };
    }

    /**
     * Get response time
     */
    public function getResponseTimeAttribute(): ?string
    {
        if ($this->responded_at && $this->created_at) {
            return $this->created_at->diffForHumans($this->responded_at, true);
        }
        return null;
    }

    // ==========================================
    // ACTION METHODS
    // ==========================================

    /**
     * Accept the escalation
     */
    // public function accept(?string $response = null): void
    // {
    //     $this->update([
    //         'status' => 'accepted',
    //         'response' => $response,
    //         'responded_at' => now(),
    //     ]);

    //     // Update incident if needed
    //     if ($this->incident->status === 'escalated') {
    //         $this->incident->startProgress();
    //     }
    // }

    /**
     * Reject the escalation
     */
    // public function reject(?string $response = null): void
    // {
    //     $this->update([
    //         'status' => 'rejected',
    //         'response' => $response,
    //         'responded_at' => now(),
    //     ]);
    // }

    /**
     * Check if response is overdue (more than 2 hours)
     */
    public function isResponseOverdue(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }
        return $this->created_at->diffInHours(now()) >= 2;
    }

    /**
     * Get escalation chain
     */
    public function getEscalationChain(): array
    {
        return $this->incident->escalations()
            ->with(['escalatedBy', 'escalatedTo', 'fromDepartment', 'toDepartment'])
            ->orderBy('level')
            ->get()
            ->toArray();
    }


    /**
     * Accept the escalation
     */
    public function accept(?string $response = null): void
    {
        $this->update([
            'status' => 'accepted',
            'response' => $response,
            'responded_by' => auth()->id(),
            'responded_at' => now(),
            'response_type' => 'accepted',
        ]);
    }

    /**
     * Reject the escalation
     */
    public function reject(?string $response = null): void
    {
        $this->update([
            'status' => 'rejected',
            'response' => $response,
            'responded_by' => auth()->id(),
            'responded_at' => now(),
            'response_type' => 'rejected',
        ]);
    }

    /**
     * Return escalation back to previous level
     */
    public function returnBack(?string $response = null): void
    {
        $this->update([
            'status' => 'rejected',
            'response' => $response ?? 'Returned back - not applicable',
            'responded_by' => auth()->id(),
            'responded_at' => now(),
            'response_type' => 'returned',
        ]);
    }

    /**
     * Check if escalation can be responded to by current user
     */
    public function canBeRespondedBy(User $user): bool
    {
        return $this->escalated_to === $user->id && $this->status === 'pending';
    }


}
