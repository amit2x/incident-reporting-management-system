<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\Department;
use App\Models\IncidentCategory;
use App\Models\KpiReport;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\IncidentReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Reports index page
     */
    public function index()
    {
        $this->authorize('view-reports');
        
        $departments = Department::active()->ordered()->get();
        $categories = IncidentCategory::active()->get();
        
        return view('reports.index', compact('departments', 'categories'));
    }

    /**
     * KPI Report
     */
    public function kpiReport(Request $request)
    {
        $this->authorize('view-reports');
        
        $filters = $this->getDateRangeFilter($request);
        
        $kpiData = $this->getKpiData($filters, $request->get('department_id'));
        $trendData = $this->getTrendData($filters, $request->get('department_id'));
        $severityData = $this->getSeverityDistribution($filters, $request->get('department_id'));
        $statusData = $this->getStatusDistribution($filters, $request->get('department_id'));
        $departmentData = $this->getDepartmentPerformance($filters);
        $categoryData = $this->getCategoryDistribution($filters, $request->get('department_id'));
        $slaData = $this->getSlaCompliance($filters);
        $hourlyDistribution = $this->getHourlyDistribution($filters, $request->get('department_id'));
        
        $departments = Department::active()->ordered()->get();
        $categoryStats = $this->getCategoryStats($filters, $request->get('department_id'));
        $departmentStats = $this->getDepartmentStats($filters);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'total_incidents' => $kpiData['total_incidents'] ?? 0,
                'open_incidents' => $kpiData['open_incidents'] ?? 0,
                'resolved_incidents' => $kpiData['resolved_incidents'] ?? 0,
                'escalated_incidents' => $kpiData['escalated_incidents'] ?? 0,
                'avg_response_time' => $kpiData['avg_response_time'] ?? 0,
                'avg_resolution_time' => $kpiData['avg_resolution_time'] ?? 0,
                'sla_compliance' => $kpiData['sla_compliance'] ?? 0,
                'labels' => array_keys($trendData['total'] ?? []),
                'total' => array_values($trendData['total'] ?? []),
                'resolved' => array_values($trendData['resolved'] ?? []),
            ]);
        }

        return view('reports.kpi', compact(
            'kpiData', 'trendData', 'severityData', 'statusData',
            'departmentData', 'categoryData', 'slaData', 'hourlyDistribution',
            'departments', 'categoryStats', 'departmentStats'
        ));
    }

    /**
     * Department Report
     */
    public function departmentReport(Request $request)
    {
        $this->authorize('view-reports');
        
        $filters = $this->getDateRangeFilter($request);
        $departmentStats = $this->getDepartmentStats($filters);
        
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $departmentStats,
            ]);
        }
        
        $departments = Department::active()->ordered()->get();
        
        return view('reports.department', compact('departmentStats', 'departments'));
    }

    /**
     * SLA Report
     */
    public function slaReport(Request $request)
    {
        $this->authorize('view-reports');
        
        $filters = $this->getDateRangeFilter($request);
        
        $slaData = $this->getSlaCompliance($filters);
        $slaBreaches = $this->getSlaBreaches($filters, $request->get('department_id'));
        
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'sla_compliance' => $slaData,
                'sla_breaches' => $slaBreaches,
            ]);
        }
        
        $departments = Department::active()->ordered()->get();
        
        return view('reports.sla', compact('slaData', 'slaBreaches', 'departments'));
    }

    /**
     * Export report
     */
    public function export(Request $request, string $format)
    {
        $this->authorize('export-reports');
        
        $filters = $this->getDateRangeFilter($request);
        $reportType = $request->get('type', 'general');
        
        $data = $this->getReportData($reportType, $filters, $request->get('department_id'));
        
        return match($format) {
            'excel' => Excel::download(
                new IncidentReportExport($data, $reportType),
                "incident_report_{$reportType}_" . now()->format('Y-m-d_His') . '.xlsx'
            ),
            'pdf' => $this->exportPdf($data, $reportType),
            'csv' => $this->exportCsv($data),
            default => abort(400, 'Unsupported export format'),
        };
    }

    // ==========================================
    // PRIVATE HELPER METHODS
    // ==========================================

    /**
     * Get date range filter from request
     */
    private function getDateRangeFilter(Request $request): array
    {
        $period = $request->get('period', 'last30days');

        return match ($period) {
            'today' => ['date_from' => Carbon::today(), 'date_to' => Carbon::today()->endOfDay()],
            'yesterday' => ['date_from' => Carbon::yesterday(), 'date_to' => Carbon::yesterday()->endOfDay()],
            'last7days' => ['date_from' => Carbon::now()->subDays(7), 'date_to' => Carbon::now()],
            'last30days' => ['date_from' => Carbon::now()->subDays(30), 'date_to' => Carbon::now()],
            'thisMonth' => ['date_from' => Carbon::now()->startOfMonth(), 'date_to' => Carbon::now()->endOfMonth()],
            'lastMonth' => ['date_from' => Carbon::now()->subMonth()->startOfMonth(), 'date_to' => Carbon::now()->subMonth()->endOfMonth()],
            'custom' => [
                'date_from' => $request->get('date_from') ? Carbon::parse($request->date_from) : Carbon::now()->subDays(30),
                'date_to' => $request->get('date_to') ? Carbon::parse($request->date_to)->endOfDay() : Carbon::now(),
            ],
            default => ['date_from' => Carbon::now()->subDays(30), 'date_to' => Carbon::now()],
        };
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($query, array $filters, ?int $departmentId = null): void
    {
        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
    }

    /**
     * Get KPI data
     */
    private function getKpiData(array $filters, ?int $departmentId = null): array
    {
        $query = Incident::query();
        $this->applyFilters($query, $filters, $departmentId);

        $total = (clone $query)->count();
        $breached = (clone $query)->where('sla_breach_count', '>', 0)->count();

        return [
            'total_incidents' => $total,
            'open_incidents' => (clone $query)->whereIn('status', ['open', 'acknowledged', 'in_progress'])->count(),
            'resolved_incidents' => (clone $query)->whereIn('status', ['resolved', 'closed'])->count(),
            'escalated_incidents' => (clone $query)->where('status', 'escalated')->count(),
            'sla_breaches' => $breached,
            'avg_response_time' => round((clone $query)->whereNotNull('acknowledged_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, acknowledged_at)) as avg_time')
                ->value('avg_time') ?? 0, 1),
            'avg_resolution_time' => round((clone $query)->whereNotNull('resolved_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_time')
                ->value('avg_time') ?? 0, 1),
            'sla_compliance' => $total > 0 ? round((($total - $breached) / $total) * 100, 2) : 100,
        ];
    }

    /**
     * Get trend data for charts
     */
    private function getTrendData(array $filters, ?int $departmentId = null): array
    {
        $totalQuery = Incident::query();
        $this->applyFilters($totalQuery, $filters, $departmentId);

        $resolvedQuery = Incident::query();
        $this->applyFilters($resolvedQuery, $filters, $departmentId);
        $resolvedQuery->whereNotNull('resolved_at');

        $total = $totalQuery->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $resolved = $resolvedQuery->selectRaw('DATE(resolved_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        return [
            'total' => $total,
            'resolved' => $resolved,
        ];
    }

    /**
     * Get severity distribution
     */
    private function getSeverityDistribution(array $filters, ?int $departmentId = null): array
    {
        $query = Incident::query();
        $this->applyFilters($query, $filters, $departmentId);

        return $query->selectRaw('severity, COUNT(*) as count')
            ->groupBy('severity')
            ->pluck('count', 'severity')
            ->toArray();
    }

    /**
     * Get status distribution
     */
    private function getStatusDistribution(array $filters, ?int $departmentId = null): array
    {
        $query = Incident::query();
        $this->applyFilters($query, $filters, $departmentId);

        return $query->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    /**
     * Get department performance
     */
    private function getDepartmentPerformance(array $filters): array
    {
        return Department::active()->get()->map(function ($department) use ($filters) {
            $query = Incident::where('department_id', $department->id);
            $this->applyFilters($query, $filters, $department->id);

            $total = (clone $query)->count();
            $resolved = (clone $query)->whereIn('status', ['resolved', 'closed'])->count();

            return [
                'name' => $department->name,
                'color' => $department->color,
                'total' => $total,
                'active' => (clone $query)->whereIn('status', ['open', 'acknowledged', 'in_progress', 'escalated'])->count(),
                'resolved' => $resolved,
                'performance' => $total > 0 ? round(($resolved / $total) * 100, 2) : 0,
            ];
        })->toArray();
    }

    /**
     * Get category distribution
     */
    private function getCategoryDistribution(array $filters, ?int $departmentId = null): array
    {
        $query = Incident::query();
        $this->applyFilters($query, $filters, $departmentId);

        return $query->selectRaw('category_id, COUNT(*) as count')
            ->groupBy('category_id')
            ->with('category:id,name')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->category->name ?? 'Unknown' => $item->count];
            })
            ->toArray();
    }

    /**
     * Get SLA compliance by department
     */
    private function getSlaCompliance(array $filters): array
    {
        $result = [];
        $departments = Department::active()->get();

        foreach ($departments as $department) {
            $query = Incident::where('department_id', $department->id);
            $this->applyFilters($query, $filters, $department->id);

            $total = (clone $query)->count();
            $breached = (clone $query)->where('sla_breach_count', '>', 0)->count();

            $result[$department->name] = $total > 0 ? round((($total - $breached) / $total) * 100, 2) : 100;
        }

        return $result;
    }

    /**
     * Get hourly distribution
     */
    private function getHourlyDistribution(array $filters, ?int $departmentId = null): array
    {
        $query = Incident::query();
        $this->applyFilters($query, $filters, $departmentId);

        return $query->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();
    }

    /**
     * Get category statistics
     */
    private function getCategoryStats(array $filters, ?int $departmentId = null): array
    {
        return IncidentCategory::active()->get()->map(function ($category) use ($filters, $departmentId) {
            $query = Incident::where('category_id', $category->id);
            $this->applyFilters($query, $filters, $departmentId);

            $total = (clone $query)->count();
            $breached = (clone $query)->where('sla_breach_count', '>', 0)->count();

            return [
                'name' => $category->name,
                'icon' => $category->icon,
                'color' => $category->color,
                'total' => $total,
                'open' => (clone $query)->whereIn('status', ['open', 'acknowledged', 'in_progress'])->count(),
                'resolved' => (clone $query)->whereIn('status', ['resolved', 'closed'])->count(),
                'avg_response_time' => round((clone $query)->whereNotNull('acknowledged_at')
                    ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, acknowledged_at)) as avg_time')
                    ->value('avg_time') ?? 0, 1),
                'sla_compliance' => $total > 0 ? round((($total - $breached) / $total) * 100, 2) : 100,
            ];
        })->toArray();
    }

    /**
     * Get department statistics
     */
    private function getDepartmentStats(array $filters): array
    {
        return Department::active()->get()->map(function ($department) use ($filters) {
            $query = Incident::where('department_id', $department->id);
            $this->applyFilters($query, $filters, $department->id);

            $total = (clone $query)->count();
            $resolved = (clone $query)->whereIn('status', ['resolved', 'closed'])->count();

            return [
                'name' => $department->name,
                'color' => $department->color,
                'code' => $department->code,
                'total' => $total,
                'active' => (clone $query)->whereIn('status', ['open', 'acknowledged', 'in_progress', 'escalated'])->count(),
                'resolved' => $resolved,
                'escalated' => (clone $query)->where('status', 'escalated')->count(),
                'performance' => $total > 0 ? round(($resolved / $total) * 100, 2) : 0,
            ];
        })->toArray();
    }

    /**
     * Get SLA breaches
     */
    private function getSlaBreaches(array $filters, ?int $departmentId = null): array
    {
        return Incident::where('sla_breach_count', '>', 0)
            ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
            ->when(!empty($filters['date_from']), fn($q) => $q->whereDate('created_at', '>=', $filters['date_from']))
            ->when(!empty($filters['date_to']), fn($q) => $q->whereDate('created_at', '<=', $filters['date_to']))
            ->with(['department', 'category'])
            ->orderBy('sla_breach_count', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($incident) {
                return [
                    'incident_id' => $incident->incident_id,
                    'title' => $incident->title,
                    'department' => $incident->department?->name,
                    'category' => $incident->category?->name,
                    'breach_count' => $incident->sla_breach_count,
                    'sla_due_at' => $incident->sla_due_at?->format('Y-m-d H:i'),
                    'status' => $incident->status,
                ];
            })
            ->toArray();
    }

    /**
     * Get report data for export
     */
    private function getReportData(string $type, array $filters, ?int $departmentId = null): array
    {
        return match($type) {
            'general' => [
                'stats' => $this->getKpiData($filters, $departmentId),
                'department' => $this->getDepartmentStats($filters),
                'category' => $this->getCategoryStats($filters, $departmentId),
                'generated_at' => now()->format('Y-m-d H:i:s'),
                'generated_by' => Auth::user()->name,
            ],
            'sla' => [
                'compliance' => $this->getSlaCompliance($filters),
                'breaches' => $this->getSlaBreaches($filters, $departmentId),
                'generated_at' => now()->format('Y-m-d H:i:s'),
                'generated_by' => Auth::user()->name,
            ],
            default => [],
        };
    }

    /**
     * Export as PDF
     */
    private function exportPdf(array $data, string $type)
    {
        $viewName = "reports.exports.{$type}_pdf";
        
        if (!view()->exists($viewName)) {
            $viewName = 'reports.exports.general_pdf';
        }
        
        $pdf = Pdf::loadView($viewName, [
            'data' => $data,
            'reportDate' => now()->format('d M Y, H:i'),
            'generatedBy' => Auth::user()->name,
        ]);

        $pdf->setPaper('a4', 'landscape');

        return $pdf->download("incident_{$type}_report_" . now()->format('Y-m-d_His') . '.pdf');
    }

    /**
     * Export as CSV
     */
    private function exportCsv(array $data)
    {
        $filename = "incident_report_" . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, ['Metric', 'Value']);
            
            // Flatten and write data
            $this->flattenArray($data, $file);
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Flatten array for CSV
     */
    private function flattenArray(array $data, $file, string $prefix = ''): void
    {
        foreach ($data as $key => $value) {
            $label = $prefix ? "{$prefix}.{$key}" : $key;
            
            if (is_array($value) && !empty($value)) {
                if (isset($value[0]) && is_array($value[0])) {
                    foreach ($value as $i => $item) {
                        $this->flattenArray($item, $file, "{$label}[{$i}]");
                    }
                } else {
                    foreach ($value as $k => $v) {
                        if (!is_array($v)) {
                            fputcsv($file, [ucwords(str_replace('_', ' ', "{$label}.{$k}")), $v]);
                        }
                    }
                }
            } elseif (!is_array($value)) {
                fputcsv($file, [ucwords(str_replace('_', ' ', $label)), $value]);
            }
        }
    }
}