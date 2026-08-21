<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentStatusAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'incident_id',
        'action',
        'user_id',
        'previous_status',
        'new_status',
        'reason',
        'metadata',
        'acted_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'acted_at' => 'datetime',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeForIncident($query, $incidentId)
    {
        return $query->where('incident_id', $incidentId);
    }

    public function scopeLatestFirst($query)
    {
        return $query->orderBy('acted_at', 'desc');
    }

    public function scopeResolved($query)
    {
        return $query->where('action', 'resolved');
    }

    public function scopeClosed($query)
    {
        return $query->where('action', 'closed');
    }

    public function scopeRejected($query)
    {
        return $query->where('action', 'rejected');
    }

    public function scopeReopened($query)
    {
        return $query->where('action', 'reopened');
    }

    public function scopeAcknowledged($query)
    {
        return $query->where('action', 'acknowledged');
    }

    public function scopeEscalated($query)
    {
        return $query->where('action', 'escalated');
    }

    public function scopeAssigned($query)
    {
        return $query->where('action', 'assigned');
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'acknowledged' => 'Acknowledged',
            'resolved' => 'Resolved',
            'closed' => 'Closed',
            'rejected' => 'Rejected',
            'reopened' => 'Reopened',
            'escalated' => 'Escalated',
            'assigned' => 'Assigned',
            default => ucfirst($this->action),
        };
    }

    public function getActionIconAttribute(): string
    {
        return match ($this->action) {
            'acknowledged' => 'fa-check-circle',
            'resolved' => 'fa-check-double',
            'closed' => 'fa-lock',
            'rejected' => 'fa-times-circle',
            'reopened' => 'fa-redo',
            'escalated' => 'fa-arrow-up',
            'assigned' => 'fa-user-plus',
            default => 'fa-circle',
        };
    }

    public function getActionColorAttribute(): string
    {
        return match ($this->action) {
            'acknowledged' => '#F59E0B',
            'resolved' => '#10B981',
            'closed' => '#6B7280',
            'rejected' => '#DC2626',
            'reopened' => '#3B82F6',
            'escalated' => '#EF4444',
            'assigned' => '#8B5CF6',
            default => '#6B7280',
        };
    }

    public function getFormattedActedAtAttribute(): string
    {
        return $this->acted_at->format('d M Y, H:i');
    }

    public function getFormattedReasonAttribute(): ?string
    {
        if ($this->reason) {
            return $this->reason;
        }

        // If no reason, provide default based on action
        return match ($this->action) {
            'acknowledged' => 'Incident was acknowledged',
            'resolved' => 'Incident was resolved',
            'closed' => 'Incident was closed',
            'rejected' => 'Incident was rejected',
            'reopened' => 'Incident was reopened',
            'escalated' => 'Incident was escalated',
            'assigned' => 'Incident was assigned',
            default => null,
        };
    }
}