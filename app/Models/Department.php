<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Cache;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'color',
        'icon',
        'email',
        'phone',
        'location',
        'is_active',
        'sort_order',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Users in this department
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Incidents for this department
     */
    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    /**
     * Escalation matrices for this department
     */
    public function escalationMatrices(): HasMany
    {
        return $this->hasMany(EscalationMatrix::class);
    }

    /**
     * Escalations from this department
     */
    public function escalationsFrom(): HasMany
    {
        return $this->hasMany(Escalation::class, 'from_department_id');
    }

    /**
     * Escalations to this department
     */
    public function escalationsTo(): HasMany
    {
        return $this->hasMany(Escalation::class, 'to_department_id');
    }

    /**
     * KPI reports for this department
     */
    public function kpiReports(): HasMany
    {
        return $this->hasMany(KpiReport::class);
    }

    /**
     * Escalation matrix entries targeting this department
     */
    public function escalationTargetEntries(): HasMany
    {
        return $this->hasMany(EscalationMatrix::class, 'escalate_to_department_id');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Active departments only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Ordered by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    /**
     * Get active incidents count
     */
    public function getActiveIncidentsCountAttribute(): int
    {
        return Cache::remember("dept_{$this->id}_active_incidents", 300, function () {
            return $this->incidents()
                ->whereIn('status', ['open', 'acknowledged', 'in_progress', 'escalated'])
                ->count();
        });
    }

    /**
     * Get resolved incidents count
     */
    public function getResolvedIncidentsCountAttribute(): int
    {
        return Cache::remember("dept_{$this->id}_resolved_incidents", 300, function () {
            return $this->incidents()
                ->whereIn('status', ['resolved', 'closed'])
                ->count();
        });
    }

    /**
     * Get staff count
     */
    public function getStaffCountAttribute(): int
    {
        return Cache::remember("dept_{$this->id}_staff_count", 300, function () {
            return $this->users()->active()->count();
        });
    }

    /**
     * Get total incidents count
     */
    public function getTotalIncidentsCountAttribute(): int
    {
        return Cache::remember("dept_{$this->id}_total_incidents", 300, function () {
            return $this->incidents()->count();
        });
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    /**
     * Get head of department
     */
    public function getHeadOfDepartment(): ?User
    {
        return $this->users()
            ->whereHas('roles', function ($query) {
                $query->where('name', 'hod');
            })
            ->active()
            ->first();
    }

    /**
     * Get all supervisors
     */
    public function getSupervisors()
    {
        return $this->users()
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['supervisor', 'hod']);
            })
            ->active()
            ->get();
    }

    /**
     * Get department statistics
     */
    public function getDepartmentStats(string $period = 'monthly'): array
    {
        $dateRange = $this->getDateRange($period);

        $incidents = $this->incidents()
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);

        return [
            'name' => $this->name,
            'code' => $this->code,
            'color' => $this->color,
            'total_incidents' => (clone $incidents)->count(),
            'active_incidents' => (clone $incidents)->whereIn('status', ['open', 'acknowledged', 'in_progress'])->count(),
            'resolved_incidents' => (clone $incidents)->whereIn('status', ['resolved', 'closed'])->count(),
            'escalated_incidents' => (clone $incidents)->where('status', 'escalated')->count(),
            'sla_breaches' => (clone $incidents)->where('sla_breach_count', '>', 0)->count(),
            'avg_response_time' => $this->calculateAvgResponseTime($dateRange),
            'avg_resolution_time' => $this->calculateAvgResolutionTime($dateRange),
            'staff_count' => $this->staff_count,
        ];
    }

    /**
     * Get date range for statistics
     */
    private function getDateRange(string $period): array
    {
        return match ($period) {
            'today' => ['start' => today()->startOfDay(), 'end' => today()->endOfDay()],
            'yesterday' => ['start' => yesterday()->startOfDay(), 'end' => yesterday()->endOfDay()],
            'weekly' => ['start' => now()->startOfWeek(), 'end' => now()->endOfWeek()],
            'monthly' => ['start' => now()->startOfMonth(), 'end' => now()->endOfMonth()],
            'yearly' => ['start' => now()->startOfYear(), 'end' => now()->endOfYear()],
            default => ['start' => now()->subDays(30), 'end' => now()],
        };
    }

    /**
     * Calculate average response time
     */
    private function calculateAvgResponseTime(array $dateRange): float
    {
        return round($this->incidents()
            ->whereNotNull('acknowledged_at')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, acknowledged_at)) as avg_time')
            ->value('avg_time') ?? 0, 1);
    }

    /**
     * Calculate average resolution time
     */
    private function calculateAvgResolutionTime(array $dateRange): float
    {
        return round($this->incidents()
            ->whereNotNull('resolved_at')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_time')
            ->value('avg_time') ?? 0, 1);
    }

    /**
     * Clear department cache
     */
    public function clearCache(): void
    {
        Cache::forget("dept_{$this->id}_active_incidents");
        Cache::forget("dept_{$this->id}_resolved_incidents");
        Cache::forget("dept_{$this->id}_staff_count");
        Cache::forget("dept_{$this->id}_total_incidents");
    }
}
