<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserActivityLog extends Model
{
    use HasFactory;

    protected $table = 'user_activity_logs';

    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'url',
        'method',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    protected $appends = [
        'action_label',
        'action_color',
        'action_icon',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * User who performed the action
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * By user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * By action type
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * By model
     */
    public function scopeByModel($query, string $modelType, ?int $modelId = null)
    {
        $query = $query->where('model_type', $modelType);

        if ($modelId) {
            $query->where('model_id', $modelId);
        }

        return $query;
    }

    /**
     * Recent logs
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * By date range
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * By IP address
     */
    public function scopeByIp($query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    /**
     * Get action label
     */
    public function getActionLabelAttribute(): string
    {
        return match($this->action) {
            'login' => 'User Login',
            'logout' => 'User Logout',
            'page_visit' => 'Page Visit',
            'api_request' => 'API Request',
            'created' => 'Record Created',
            'updated' => 'Record Updated',
            'deleted' => 'Record Deleted',
            'status_changed' => 'Status Changed',
            'assigned' => 'Incident Assigned',
            'escalated' => 'Incident Escalated',
            'resolved' => 'Incident Resolved',
            'comment_added' => 'Comment Added',
            'media_uploaded' => 'Media Uploaded',
            'profile_updated' => 'Profile Updated',
            'password_changed' => 'Password Changed',
            'settings_updated' => 'Settings Updated',
            'role_changed' => 'Role Changed',
            'permission_changed' => 'Permission Changed',
            default => ucfirst(str_replace('_', ' ', $this->action)),
        };
    }

    /**
     * Get action color
     */
    public function getActionColorAttribute(): string
    {
        return match($this->action) {
            'login', 'logout' => '#3B82F6',
            'created', 'media_uploaded' => '#10B981',
            'updated', 'profile_updated', 'settings_updated' => '#F59E0B',
            'deleted' => '#EF4444',
            'status_changed', 'role_changed', 'permission_changed' => '#8B5CF6',
            'assigned' => '#EC4899',
            'escalated' => '#F97316',
            'resolved', 'comment_added' => '#06B6D4',
            default => '#6B7280',
        };
    }

    /**
     * Get action icon
     */
    public function getActionIconAttribute(): string
    {
        return match($this->action) {
            'login' => 'fa-sign-in-alt',
            'logout' => 'fa-sign-out-alt',
            'page_visit' => 'fa-eye',
            'api_request' => 'fa-code',
            'created' => 'fa-plus-circle',
            'updated' => 'fa-edit',
            'deleted' => 'fa-trash',
            'status_changed' => 'fa-exchange-alt',
            'assigned' => 'fa-user-plus',
            'escalated' => 'fa-arrow-up',
            'resolved' => 'fa-check-circle',
            'comment_added' => 'fa-comment',
            'media_uploaded' => 'fa-upload',
            'profile_updated' => 'fa-user-edit',
            'password_changed' => 'fa-key',
            'settings_updated' => 'fa-cog',
            'role_changed' => 'fa-user-tag',
            'permission_changed' => 'fa-lock',
            default => 'fa-circle',
        };
    }

    /**
     * Get formatted changes
     */
    public function getChangesFormattedAttribute(): ?string
    {
        if (empty($this->new_values)) {
            return null;
        }

        $changes = [];

        foreach ($this->new_values as $key => $newValue) {
            $oldValue = $this->old_values[$key] ?? 'N/A';
            if ($oldValue !== $newValue) {
                $changes[] = "{$key}: {$oldValue} → {$newValue}";
            }
        }

        return implode(', ', $changes);
    }

    // ==========================================
    // STATIC HELPER METHODS
    // ==========================================

    /**
     * Log an action
     */
    public static function log(
        string $action,
        ?string $modelType = null,
        ?int $modelId = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): self {
        return static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
        ]);
    }

    /**
     * Clean old logs
     */
    public static function cleanOldLogs(int $days = 90): int
    {
        return static::where('created_at', '<', now()->subDays($days))->delete();
    }

    /**
     * Get action statistics
     */
    public static function getActionStats(string $period = 'daily'): array
    {
        $dateRange = match ($period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            'weekly' => [now()->startOfWeek(), now()->endOfWeek()],
            'monthly' => [now()->startOfMonth(), now()->endOfMonth()],
            default => [now()->subDays(7), now()],
        };

        return static::whereBetween('created_at', $dateRange)
            ->selectRaw('action, COUNT(*) as count')
            ->groupBy('action')
            ->orderByDesc('count')
            ->pluck('count', 'action')
            ->toArray();
    }
}
