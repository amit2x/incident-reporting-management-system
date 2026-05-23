<?php
// app/Jobs/GenerateKpiReport.php

namespace App\Jobs;

use App\Models\Department;
use App\Models\Incident;
use App\Models\KpiReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GenerateKpiReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected string $reportType,
        protected ?Carbon $reportDate = null
    ) {
        $this->onQueue('reports');
        $this->reportDate = $reportDate ?? now();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $reportDate = $this->reportDate;
        $dateRange = $this->getDateRange($reportDate);

        Log::info("Generating {$this->reportType} KPI report", [
            'date' => $reportDate->format('Y-m-d'),
            'range' => $dateRange,
        ]);

        // Generate for each active department
        $departments = Department::active()->get();
        
        foreach ($departments as $department) {
            $this->generateDepartmentKpi($department, $dateRange, $reportDate);
        }
        
        // Generate overall report
        $this->generateOverallKpi($dateRange, $reportDate);

        Log::info("KPI report generation completed for {$this->reportType}");
    }

    /**
     * Get date range based on report type.
     */
    protected function getDateRange(Carbon $date): array
    {
        return match($this->reportType) {
            'daily' => [
                'start' => $date->copy()->startOfDay(),
                'end' => $date->copy()->endOfDay(),
            ],
            'weekly' => [
                'start' => $date->copy()->startOfWeek(),
                'end' => $date->copy()->endOfWeek(),
            ],
            'monthly' => [
                'start' => $date->copy()->startOfMonth(),
                'end' => $date->copy()->endOfMonth(),
            ],
            default => [
                'start' => $date->copy()->startOfDay(),
                'end' => $date->copy()->endOfDay(),
            ],
        };
    }

    /**
     * Generate KPI for a specific department.
     */
    protected function generateDepartmentKpi(Department $department, array $dateRange, Carbon $reportDate): void
    {
        $stats = $this->calculateIncidentStats($department->id, $dateRange);

        KpiReport::updateOrCreate(
            [
                'report_type' => $this->reportType,
                'report_date' => $reportDate->format('Y-m-d'),
                'department_id' => $department->id,
            ],
            $stats
        );
    }

    /**
     * Generate overall KPI (all departments).
     */
    protected function generateOverallKpi(array $dateRange, Carbon $reportDate): void
    {
        $stats = $this->calculateIncidentStats(null, $dateRange);

        KpiReport::updateOrCreate(
            [
                'report_type' => $this->reportType,
                'report_date' => $reportDate->format('Y-m-d'),
                'department_id' => null,
            ],
            $stats
        );
    }

    /**
     * Calculate incident statistics.
     */
    protected function calculateIncidentStats(?int $departmentId, array $dateRange): array
    {
        $query = Incident::whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        return [
            'total_incidents' => (clone $query)->count(),
            'open_incidents' => (clone $query)->whereIn('status', ['open', 'acknowledged', 'in_progress'])->count(),
            'resolved_incidents' => (clone $query)->where('status', 'resolved')->count(),
            'closed_incidents' => (clone $query)->where('status', 'closed')->count(),
            'escalated_incidents' => (clone $query)->where('status', 'escalated')->count(),
            'sla_breaches' => (clone $query)->where('sla_breach_count', '>', 0)->count(),
            'avg_response_time_minutes' => $this->calculateAvgResponseTime(clone $query),
            'avg_resolution_time_minutes' => $this->calculateAvgResolutionTime(clone $query),
            'sla_compliance_percentage' => $this->calculateSlaCompliance(clone $query),
            'severity_distribution' => $this->getDistribution(clone $query, 'severity'),
            'category_distribution' => $this->getCategoryDistribution(clone $query),
            'hourly_distribution' => $this->getHourlyDistribution(clone $query),
        ];
    }

    protected function calculateAvgResponseTime($query): float
    {
        return round(
            $query->whereNotNull('acknowledged_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, acknowledged_at)) as avg_time')
                ->value('avg_time') ?? 0,
            2
        );
    }

    protected function calculateAvgResolutionTime($query): float
    {
        return round(
            $query->whereNotNull('resolved_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_time')
                ->value('avg_time') ?? 0,
            2
        );
    }

    protected function calculateSlaCompliance($query): float
    {
        $total = $query->count();
        if ($total === 0) return 100.00;

        $breached = (clone $query)->where('sla_breach_count', '>', 0)->count();
        return round((($total - $breached) / $total) * 100, 2);
    }

    protected function getDistribution($query, string $column): array
    {
        return $query->selectRaw("{$column}, COUNT(*) as count")
            ->groupBy($column)
            ->pluck('count', $column)
            ->toArray();
    }

    protected function getCategoryDistribution($query): array
    {
        return $query->selectRaw('category_id, COUNT(*) as count')
            ->groupBy('category_id')
            ->with('category:id,name')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->category->name ?? 'Unknown' => $item->count];
            })
            ->toArray();
    }

    protected function getHourlyDistribution($query): array
    {
        return $query->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();
    }
}