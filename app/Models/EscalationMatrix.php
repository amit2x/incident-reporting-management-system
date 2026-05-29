<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EscalationMatrix extends Model
{
    use HasFactory;

    protected $table = 'escalation_matrices';

    protected $fillable = [
        'department_id',
        'category_id',
        'level',
        'timeout_minutes',
        'escalate_to_user_id',
        'escalate_to_department_id',
        'notify_via_email',
        'notify_via_push',
        'is_active',
    ];

    protected $casts = [
        'notify_via_email' => 'boolean',
        'notify_via_push' => 'boolean',
        'is_active' => 'boolean',
        'level' => 'integer',
        'timeout_minutes' => 'integer',
    ];

    protected $appends = [
        'level_label',
        'timeout_formatted',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Department this matrix belongs to
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Category this matrix applies to (null = all categories)
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(IncidentCategory::class);
    }

    /**
     * User to escalate to
     */
    public function escalateToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalate_to_user_id');
    }

    /**
     * Department to escalate to
     */
    public function escalateToDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'escalate_to_department_id');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Active matrices only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * By escalation level
     */
    public function scopeByLevel($query, int $level)
    {
        return $query->where('level', $level);
    }

    /**
     * For a specific department
     */
    public function scopeForDepartment($query, int $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * For a specific category
     */
    public function scopeForCategory($query, ?int $categoryId)
    {
        return $query->where(function ($q) use ($categoryId) {
            $q->where('category_id', $categoryId)
                ->orWhereNull('category_id');
        });
    }

    /**
     * Ordered by level
     */
    public function scopeOrderedByLevel($query)
    {
        return $query->orderBy('level');
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    /**
     * Get level label
     */
    public function getLevelLabelAttribute(): string
    {
        return match ($this->level) {
            1 => 'Level 1 - Supervisor',
            2 => 'Level 2 - HOD',
            3 => 'Level 3 - Admin',
            4 => 'Level 4 - Director',
            default => "Level {$this->level}",
        };
    }

    /**
     * Get formatted timeout
     */
    public function getTimeoutFormattedAttribute(): string
    {
        if ($this->timeout_minutes < 60) {
            return "{$this->timeout_minutes} min";
        }

        $hours = floor($this->timeout_minutes / 60);
        $minutes = $this->timeout_minutes % 60;

        if ($minutes === 0) {
            return "{$hours} hr".($hours > 1 ? 's' : '');
        }

        return "{$hours}h {$minutes}m";
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    /**
     * Check if this is the default matrix (no category)
     */
    public function isDefault(): bool
    {
        return is_null($this->category_id);
    }

    /**
     * Get escalation target
     */
    public function getEscalationTarget(): array
    {
        return [
            'user' => $this->escalateToUser,
            'department' => $this->escalateToDepartment,
            'notify_via_email' => $this->notify_via_email,
            'notify_via_push' => $this->notify_via_push,
        ];
    }

    /**
     * Static method to get escalation chain for an incident
     */
    public static function getEscalationChainForIncident(Incident $incident): array
    {
        $matrix = static::where('department_id', $incident->department_id)
            ->where(function ($query) use ($incident) {
                $query->where('category_id', $incident->category_id)
                    ->orWhereNull('category_id');
            })
            ->active()
            ->orderedByLevel()
            ->get();

        // If no specific matrix found, get default
        if ($matrix->isEmpty()) {
            $matrix = static::where('department_id', $incident->department_id)
                ->whereNull('category_id')
                ->active()
                ->orderedByLevel()
                ->get();
        }

        return $matrix->toArray();
    }

    /**
     * Get next escalation level
     */
    public static function getNextEscalationTarget(Incident $incident, int $currentLevel): ?self
    {
        return static::where('department_id', $incident->department_id)
            ->where(function ($query) use ($incident) {
                $query->where('category_id', $incident->category_id)
                    ->orWhereNull('category_id');
            })
            ->where('level', $currentLevel + 1)
            ->active()
            ->first();
    }

    // ==========================================
    // STATIC HELPER METHODS
    // ==========================================

    /**
     * Get the escalation entry for a specific incident at a given level.
     * First tries exact department+category match, then falls back to department default.
     */
    public static function getEscalationForIncident(Incident $incident, int $level): ?self
    {
        // First try: Exact department + category match
        $entry = static::where('department_id', $incident->department_id)
            ->where('category_id', $incident->category_id)
            ->where('level', $level)
            ->active()
            ->first();

        if ($entry) {
            return $entry;
        }

        // Second try: Department default (category_id = NULL means all categories)
        $entry = static::where('department_id', $incident->department_id)
            ->whereNull('category_id')
            ->where('level', $level)
            ->active()
            ->first();

        return $entry;
    }

    /**
     * Get the maximum escalation level for a department + category combination.
     */
    public static function getMaxLevel(int $departmentId, ?int $categoryId): int
    {
        return (int) static::where('department_id', $departmentId)
            ->where(function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId)
                    ->orWhereNull('category_id');
            })
            ->active()
            ->max('level') ?? 0;
    }

    /**
     * Get all escalation levels for a department + category combination.
     */
    public static function getEscalationLevels(int $departmentId, ?int $categoryId): array
    {
        return static::where('department_id', $departmentId)
            ->where(function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId)
                    ->orWhereNull('category_id');
            })
            ->active()
            ->orderBy('level')
            ->get()
            ->toArray();
    }

    /**
     * Check if escalation matrix exists for a department.
     */
    public static function hasMatrix(int $departmentId, ?int $categoryId = null): bool
    {
        return static::where('department_id', $departmentId)
            ->where(function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId)
                    ->orWhereNull('category_id');
            })
            ->active()
            ->exists();
    }

    /**
     * Get escalation chain for an incident (all levels).
     */
    public static function getEscalationChain(int $departmentId, ?int $categoryId): array
    {
        return static::with(['escalateToUser', 'escalateToDepartment'])
            ->where('department_id', $departmentId)
            ->where(function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId)
                    ->orWhereNull('category_id');
            })
            ->active()
            ->orderBy('level')
            ->get()
            ->map(function ($entry) {
                return [
                    'level' => $entry->level,
                    'timeout_minutes' => $entry->timeout_minutes,
                    'escalate_to_user' => $entry->escalateToUser?->name,
                    'escalate_to_department' => $entry->escalateToDepartment?->name,
                    'notify_via_email' => $entry->notify_via_email,
                    'notify_via_push' => $entry->notify_via_push,
                ];
            })
            ->toArray();
    }
}
