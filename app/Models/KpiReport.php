<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiReport extends Model
{
    use HasFactory;

    protected $table = 'kpi_reports';

    protected $fillable = [
        'report_type',
        'report_date',
        'department_id',
        'total_incidents',
        'open_incidents',
        'resolved_incidents',
        'closed_incidents',
        'escalated_incidents',
        'sla_breaches',
        'avg_response_time_minutes',
        'avg_resolution_time_minutes',
        'sla_compliance_percentage',
        'severity_distribution',
        'category_distribution',
        'hourly_distribution',
        'additional_metrics',
    ];

    protected $casts = [
        'report_date' => 'date',
        'severity_distribution' => 'array',
        'category_distribution' => 'array',
        'hourly_distribution' => 'array',
        'additional_metrics' => 'array',
        'avg_response_time_minutes' => 'decimal:2',
        'avg_resolution_time_minutes' => 'decimal:2',
        'sla_compliance_percentage' => 'decimal:2',
    ];

    protected $appends = [
        'resolution_rate',
        'escalation_rate',
        'avg_response_time_formatted',
        'avg_resolution_time_formatted',
        'report_period_label',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Department this KPI report belongs to (null = overall)
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * By report type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('report_type', $type);
    }

    /**
     * By date
     */
    public function scopeByDate($query, $date)
    {
        return $query->whereDate('report_date', $date);
    }

    /**
     * By date range
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('report_date', [$from, $to]);
    }

    /**
     * By department
     */
    public function scopeByDepartment($query, ?int $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Overall reports (no department)
     */
    public function scopeOverall($query)
    {
        return $query->whereNull('department_id');
    }

    /**
     * Daily reports
     */
    public function scopeDaily($query)
    {
        return $query->where('report_type', 'daily');
    }

    /**
     * Weekly reports
     */
    public function scopeWeekly($query)
    {
        return $query->where('report_type', 'weekly');
    }

    /**
     * Monthly reports
     */
    public function scopeMonthly($query)
    {
        return $query->where('report_type', 'monthly');
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    /**
     * Get resolution rate
     */
    public function getResolutionRateAttribute(): float
    {
        if ($this->total_incidents === 0) {
            return 0;
        }
        return round(($this->resolved_incidents + $this->closed_incidents) / $this->total_incidents * 100, 2);
    }

    /**
     * Get escalation rate
     */
    public function getEscalationRateAttribute(): float
    {
        if ($this->total_incidents === 0) {
            return 0;
        }
        return round($this->escalated_incidents / $this->total_incidents * 100, 2);
    }

    /**
     * Get formatted average response time
     */
    public function getAvgResponseTimeFormattedAttribute(): string
    {
        return $this->formatMinutes($this->avg_response_time_minutes);
    }

    /**
     * Get formatted average resolution time
     */
    public function getAvgResolutionTimeFormattedAttribute(): string
    {
        return $this->formatMinutes($this->avg_resolution_time_minutes);
    }

    /**
     * Get report period label
     */
    public function getReportPeriodLabelAttribute(): string
    {
        return match($this->report_type) {
            'daily' => $this->report_date->format('d M Y'),
            'weekly' => 'Week ' . $this->report_date->weekOfYear . ', ' . $this->report_date->format('Y'),
            'monthly' => $this->report_date->format('M Y'),
            default => $this->report_date->format('d M Y'),
        };
    }

    /**
     * Get SLA compliance color
     */
    public function getSlaComplianceColorAttribute(): string
    {
        if ($this->sla_compliance_percentage >= 90) {
            return '#10B981'; // Green
        } elseif ($this->sla_compliance_percentage >= 75) {
            return '#F59E0B'; // Yellow
        }
        return '#EF4444'; // Red
    }

    // ==========================================
    // STATIC HELPER METHODS
    // ==========================================

    /**
     * Format minutes to human readable
     */
    private function formatMinutes(float $minutes): string
    {
        if ($minutes < 1) {
            return '< 1 min';
        }

        if ($minutes < 60) {
            return round($minutes) . ' min';
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = round($minutes % 60);

        if ($remainingMinutes === 0) {
            return "{$hours} hr" . ($hours > 1 ? 's' : '');
        }

        return "{$hours}h {$remainingMinutes}m";
    }

    /**
     * Get trend comparison with previous period
     */
    public function getPreviousPeriodReport(): ?self
    {
        $previousDate = match($this->report_type) {
            'daily' => $this->report_date->copy()->subDay(),
            'weekly' => $this->report_date->copy()->subWeek(),
            'monthly' => $this->report_date->copy()->subMonth(),
            default => $this->report_date->copy()->subDay(),
        };

        return static::where('report_type', $this->report_type)
            ->whereDate('report_date', $previousDate)
            ->where('department_id', $this->department_id)
            ->first();
    }

    /**
     * Get trend percentage
     */
    public function getTrend(string $metric): ?array
    {
        $previous = $this->getPreviousPeriodReport();

        if (!$previous || $previous->$metric == 0) {
            return null;
        }

        $current = $this->$metric;
        $change = (($current - $previous->$metric) / $previous->$metric) * 100;

        return [
            'current' => $current,
            'previous' => $previous->$metric,
            'change_percentage' => round($change, 1),
            'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable'),
            'is_positive' => in_array($metric, ['sla_compliance_percentage', 'resolved_incidents'])
                ? $change >= 0
                : $change <= 0,
        ];
    }

    /**
     * Get severity distribution as percentage
     */
    public function getSeverityPercentages(): array
    {
        if ($this->total_incidents === 0 || empty($this->severity_distribution)) {
            return [];
        }

        return array_map(function ($count) {
            return round(($count / $this->total_incidents) * 100, 1);
        }, $this->severity_distribution);
    }

    /**
     * Generate report data for charts
     */
    public function getChartData(): array
    {
        return [
            'severity' => [
                'labels' => array_keys($this->severity_distribution ?? []),
                'data' => array_values($this->severity_distribution ?? []),
                'colors' => ['#DC2626', '#EF4444', '#F59E0B', '#10B981'],
            ],
            'categories' => [
                'labels' => array_keys($this->category_distribution ?? []),
                'data' => array_values($this->category_distribution ?? []),
            ],
            'hourly' => [
                'labels' => array_keys($this->hourly_distribution ?? []),
                'data' => array_values($this->hourly_distribution ?? []),
            ],
            'summary' => [
                'total' => $this->total_incidents,
                'open' => $this->open_incidents,
                'resolved' => $this->resolved_incidents,
                'closed' => $this->closed_incidents,
                'escalated' => $this->escalated_incidents,
                'sla_breaches' => $this->sla_breaches,
                'sla_compliance' => $this->sla_compliance_percentage,
                'resolution_rate' => $this->resolution_rate,
            ],
        ];
    }
}
