<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\Department;
use App\Models\IncidentCategory;
use App\Repositories\IncidentRepository;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\IncidentReportExport;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    protected $incidentRepository;

    public function __construct(IncidentRepository $incidentRepository)
    {
        $this->incidentRepository = $incidentRepository;
        $this->middleware('auth');
    }

    public function index()
    {
        $this->authorize('view-reports');
        
        $departments = Department::active()->ordered()->get();
        $categories = IncidentCategory::active()->get();
        
        return view('reports.index', compact('departments', 'categories'));
    }

    public function kpiReport(Request $request)
    {
        $this->authorize('view-reports');
        
        $filters = $this->getDateRangeFilter($request);
        
        $kpiData = $this->incidentRepository->getStats($filters);
        $trendData = $this->getTrendData($filters);
        $severityData = $this->getSeverityDistribution($filters);
        $statusData = $this->getStatusDistribution($filters);
        $departmentData = $this->getDepartmentPerformance($filters);
        $categoryData = $this->getCategoryDistribution($filters);
        $slaData = $this->getSlaCompliance($filters);
        $hourlyDistribution = $this->getHourlyDistribution($filters);
        
        $departments = Department::active()->ordered()->get();
        $categoryStats = $this->getCategoryStats($filters);
        $departmentStats = $this->getDepartmentStats($filters);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'total_incidents' => $kpiData['total'],
                'open_incidents' => $kpiData['open'],
                'resolved_incidents' => $kpiData['resolved'],
                'escalated_incidents' => $kpiData['escalated'],
                'avg_response_time' => $kpiData['avg_response_time'],
                'avg_resolution_time' => $kpiData['avg_resolution_time'],
                'sla_compliance' => $this->calculateSlaCompliance($filters),
                'labels' => array_keys($trendData),
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

    public function departmentReport(Request $request)
    {
        $this->authorize('view-reports');
        
        $filters = $this->getDateRangeFilter($request);
        
        if ($request->department_id) {
            $filters['department_id'] = $request->department_id;
        }
        
        $departmentStats = $this->getDepartmentStats($filters);
        
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $departmentStats,
            ]);
        }
        
        $departments = Department::active()->ordered()->get();
        
        return view('reports.department', compact('departmentStats', 'departments'));
    }

    public function slaReport(Request $request)
    {
        $this->authorize('view-reports');
        
        $filters = $this->getDateRangeFilter($request);
        
        $slaData = $this->getSlaCompliance($filters);
        $slaBreaches = $this->getSlaBreaches($filters);
        
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'sla_compliance' => $slaData,
                'sla_breaches' => $slaBreaches,
            ]);
        }
        
        return view('reports.sla', compact('slaData', 'slaBreaches'));
    }

    public function export(Request $request, string $format)
    {
        $this->authorize('export-reports');
        
        $filters = $this->getDateRangeFilter($request);
        $reportType = $request->get('type', 'general');
        
        $data = $this->getReportData($reportType, $filters);
        
        return match($format) {
            'excel' => Excel::download(
                new IncidentReportExport($data, $reportType),
                "incident_report_{$reportType}_" . now()->format('Y-m-d_His') . '.xlsx'
            ),
            'pdf' => $this->exportPdf($data, $reportType),
            'csv' => response()->streamDownload(
                fn() => $this->generateCsv($data),
                "incident_report_{$reportType}_" . now()->format('Y-m-d_His') . '.csv'
            ),
            default => abort(400, 'Unsupported export format'),
        };
    }

    protected function getDateRangeFilter(Request $request): array
    {
        $filters = [];
        $period = $request->get('period', 'last7days');
        
        switch ($period) {
            case 'today':
                $filters['date_from'] = Carbon::today();
                $filters['date_to'] = Carbon::today()->endOfDay();
                break;
            case 'yesterday':
                $filters['date_from'] = Carbon::yesterday();
                $filters['date_to'] = Carbon::yesterday()->endOfDay();
                break;
            case 'last7days':
                $filters['date_from'] = Carbon::now()->subDays(7);
                $filters['date_to'] = Carbon::now();
                break;
            case 'last30days':
                $filters['date_from'] = Carbon::now()->subDays(30);
                $filters['date_to'] = Carbon::now();
                break;
            case 'thisMonth':
                $filters['date_from'] = Carbon::now()->startOfMonth();
                $filters['date_to'] = Carbon::now()->endOfMonth();
                break;
            case 'lastMonth':
                $filters['date_from'] = Carbon::now()->subMonth()->startOfMonth();
                $filters['date_to'] = Carbon::now()->subMonth()->endOfMonth();
                break;
            case 'custom':
                $filters['date_from'] = $request->get('date_from') ? Carbon::parse($request->date_from) : Carbon::now()->subDays(7);
                $filters['date_to'] = $request->get('date_to') ? Carbon::parse($request->date_to)->endOfDay() : Carbon::now();
                break;
        }

        if ($request->department_id) {
            $filters['department_id'] = $request->department_id;
        }

        return $filters;
    }

    protected function getTrendData(array $filters): array
    {
        $query = Incident::query();
        
        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }
        
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $total = $query->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $resolved = Incident::whereNotNull('resolved_at')
            ->when(!empty($filters['department_id']), fn($q) => $q->where('department_id', $filters['department_id']))
            ->when(!empty($filters['date_from']), fn($q) => $q->whereDate('resolved_at', '>=', $filters['date_from']))
            ->when(!empty($filters['date_to']), fn($q) => $q->whereDate('resolved_at', '<=', $filters['date_to']))
            ->selectRaw('DATE(resolved_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        return [
            'total' => $total,
            'resolved' => $resolved,
        ];
    }

    protected function getSeverityDistribution(array $filters): array
    {
        return Incident::when(!empty($filters), function ($query) use ($filters) {
            return $this->applyFilters($query, $filters);
        })
        ->selectRaw('severity, COUNT(*) as count')
        ->groupBy('severity')
        ->pluck('count', 'severity')
        ->toArray();
    }

    protected function getStatusDistribution(array $filters): array
    {
        return Incident::when(!empty($filters), function ($query) use ($filters) {
            return $this->applyFilters($query, $filters);
        })
        ->selectRaw('status, COUNT(*) as count')
        ->groupBy('status')
        ->pluck('count', 'status')
        ->toArray();
    }

    protected function getDepartmentPerformance(array $filters): array
    {
        return Department::active()
            ->withCount(['incidents as total' => function ($query) use ($filters) {
                $this->applyFilters($query, $filters);
            }])
            ->withCount(['incidents as active' => function ($query) use ($filters) {
                $this->applyFilters($query, $filters);
                $query->whereIn('status', ['open', 'acknowledged', 'in_progress', 'escalated']);
            }])
            ->withCount(['incidents as resolved' => function ($query) use ($filters) {
                $this->applyFilters($query, $filters);
                $query->whereIn('status', ['resolved', 'closed']);
            }])
            ->get()
            ->toArray();
    }

    protected function getCategoryDistribution(array $filters): array
    {
        return IncidentCategory::active()
            ->withCount(['incidents' => function ($query) use ($filters) {
                $this->applyFilters($query, $filters);
            }])
            ->pluck('incidents_count', 'name')
            ->toArray();
    }

    protected function getSlaCompliance(array $filters): array
    {
        $result = [];
        $departments = Department::active()->get();

        foreach ($departments as $department) {
            $query = Incident::where('department_id', $department->id);
            $this->applyFilters($query, $filters);

            $total = (clone $query)->count();
            $breached = (clone $query)->where('sla_breach_count', '>', 0)->count();

            $result[$department->name] = $total > 0 ? round((($total - $breached) / $total) * 100, 2) : 100;
        }

        return $result;
    }

    protected function calculateSlaCompliance(array $filters): float
    {
        $query = Incident::query();
        $this->applyFilters($query, $filters);

        $total = (clone $query)->count();
        $breached = (clone $query)->where('sla_breach_count', '>', 0)->count();

        return $total > 0 ? round((($total - $breached) / $total) * 100, 2) : 100;
    }

    protected function getHourlyDistribution(array $filters): array
    {
        $query = Incident::query();
        $this->applyFilters($query, $filters);

        return $query->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();
    }

    protected function getCategoryStats(array $filters): array
    {
        return IncidentCategory::active()->get()->map(function ($category) use ($filters) {
            $query = Incident::where('category_id', $category->id);
            $this->applyFilters($query, $filters);

            return [
                'name' => $category->name,
                'icon' => $category->icon,
                'color' => $category->color,
                'total' => (clone $query)->count(),
                'open' => (clone $query)->whereIn('status', ['open', 'acknowledged', 'in_progress'])->count(),
                'resolved' => (clone $query)->whereIn('status', ['resolved', 'closed'])->count(),
                'avg_response_time' => (clone $query)->whereNotNull('acknowledged_at')
                    ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, acknowledged_at)) as avg_time')
                    ->value('avg_time') ?? 0,
                'sla_compliance' => $this->calculateCategorySla($category->id, $filters),
            ];
        })->toArray();
    }

    protected function getDepartmentStats(array $filters): array
    {
        return Department::active()->get()->map(function ($department) use ($filters) {
            $query = Incident::where('department_id', $department->id);
            $this->applyFilters($query, $filters);

            $total = (clone $query)->count();
            $resolved = (clone $query)->whereIn('status', ['resolved', 'closed'])->count();

            return [
                'name' => $department->name,
                'color' => $department->color,
                'total' => $total,
                'active' => (clone $query)->whereIn('status', ['open', 'acknowledged', 'in_progress', 'escalated'])->count(),
                'resolved' => $resolved,
                'escalated' => (clone $query)->where('status', 'escalated')->count(),
                'performance' => $total > 0 ? round(($resolved / $total) * 100, 2) : 0,
            ];
        })->toArray();
    }

    protected function getSlaBreaches(array $filters): array
    {
        return Incident::where('sla_breach_count', '>', 0)
            ->when(!empty($filters), function ($query) use ($filters) {
                return $this->applyFilters($query, $filters);
            })
            ->with(['department', 'category'])
            ->orderBy('sla_breach_count', 'desc')
            ->limit(20)
            ->get()
            ->toArray();
    }

    protected function applyFilters($query, array $filters): void
    {
        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
    }

    protected function getReportData(string $type, array $filters): array
    {
        return match($type) {
            'general' => [
                'stats' => $this->incidentRepository->getStats($filters),
                'department' => $this->getDepartmentStats($filters),
                'category' => $this->getCategoryStats($filters),
            ],
            'sla' => [
                'compliance' => $this->getSlaCompliance($filters),
                'breaches' => $this->getSlaBreaches($filters),
            ],
            default => [],
        };
    }

    protected function exportPdf(array $data, string $type): \Illuminate\Http\Response
    {
        $pdf = Pdf::loadView("reports.exports.{$type}_pdf", [
            'data' => $data,
            'reportDate' => now()->format('d M Y, H:i'),
            'generatedBy' => auth()->user()->name,
        ]);
        
        $pdf->setPaper('a4', 'landscape');
        
        return $pdf->download("incident_{$type}_report_" . now()->format('Y-m-d_His') . '.pdf');
    }

    protected function generateCsv(array $data): void
    {
        $handle = fopen('php://output', 'w');
        
        // Headers
        fputcsv($handle, [
            'Incident ID', 'Title', 'Category', 'Department', 'Status',
            'Severity', 'Priority', 'Reported By', 'Assigned To', 'Created At', 'Resolved At'
        ]);
        
        // Data rows
        foreach ($data as $row) {
            fputcsv($handle, $row);
        }
        
        fclose($handle);
    }
}