<?php

namespace App\Http\Controllers;

use App\Exports\CategoryReportExport;
use App\Exports\CustomReportExport;
use App\Exports\DepartmentReportExport;
use App\Exports\KpiReportExport;
use App\Exports\SlaReportExport;
use App\Models\Department;
use App\Models\Escalation;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Get user's department filter - automatically applied for non-admin users
     */
    private function getDepartmentFilter(): ?int
    {
        $user = Auth::user();
        
        // Admin and Super Admin can see all departments
        if ($user->isAdmin() || $user->isSuperAdmin()) {
            return null;
        }
        
        // Other users can only see their own department
        return $user->department_id;
    }

    /**
     * Check if user has full access (Admin or Super Admin)
     */
    private function hasFullAccess(): bool
    {
        $user = Auth::user();
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    /**
     * Apply automatic department filter to queries based on user role
     */
    private function applyDepartmentFilter($query, ?int $departmentId = null)
    {
        $autoFilter = $this->getDepartmentFilter();
        
        // If user has full access, use explicit department filter if provided
        if ($this->hasFullAccess()) {
            if ($departmentId) {
                return $query->where('department_id', $departmentId);
            }
            return $query;
        }
        
        // Non-admin users: always filter by their department
        // Override any explicit department filter with their own
        return $query->where('department_id', $autoFilter);
    }

    /**
     * Apply department filter to a collection (for pre-fetched data)
     */
    private function filterCollectionByDepartment($collection)
    {
        $autoFilter = $this->getDepartmentFilter();
        
        // Admin/Super Admin can see all
        if ($this->hasFullAccess() || !$autoFilter) {
            return $collection;
        }
        
        // Filter by department_id
        return $collection->filter(function ($item) use ($autoFilter) {
            // For Department models
            if (isset($item['id']) && $item instanceof Department) {
                return $item->id == $autoFilter;
            }
            // For arrays with department_id
            if (isset($item['department_id'])) {
                return $item['department_id'] == $autoFilter;
            }
            // For objects with department_id
            if (isset($item->department_id)) {
                return $item->department_id == $autoFilter;
            }
            // For incidents directly
            if (isset($item->id) && $item instanceof Incident) {
                return $item->department_id == $autoFilter;
            }
            return true;
        });
    }

    /**
     * Reports index page
     */
    public function index()
    {
        $this->authorize('view-reports');

        return view('reports.index');
    }

    /**
     * KPI Report - Main dashboard with charts and stats
     */
    public function kpiReport(Request $request)
    {
        $this->authorize('view-reports');

        // Get filter parameters
        $period = $request->get('period', 'last30days');
        $departmentId = $request->get('department_id');
        $categoryId = $request->get('category_id');

        // AUTO FILTER: Apply department restriction for non-admin users
        $autoDepartmentId = $this->getDepartmentFilter();
        if ($autoDepartmentId && !$this->hasFullAccess()) {
            $departmentId = $autoDepartmentId;
        }

        // Calculate date range
        $dateRange = $this->getDateRange($period, $request);

        // Get all data with filters
        $kpiData = $this->getKpiMetrics($dateRange, $departmentId, $categoryId);
        $trendData = $this->getTrendData($dateRange, $departmentId, $categoryId);
        $severityDistribution = $this->getSeverityDistribution($dateRange, $departmentId, $categoryId);
        $statusDistribution = $this->getStatusDistribution($dateRange, $departmentId, $categoryId);
        $departmentPerformance = $this->getDepartmentPerformance($dateRange, $departmentId);
        $categoryBreakdown = $this->getCategoryBreakdown($dateRange, $departmentId);
        $slaCompliance = $this->getSlaComplianceByDepartment($dateRange, $departmentId);
        $hourlyDistribution = $this->getHourlyDistribution($dateRange, $departmentId);
        $resolutionTimeTrend = $this->getResolutionTimeTrend($dateRange, $departmentId);

        // Get filter dropdowns - restrict for non-admin users
        if ($this->hasFullAccess()) {
            $departments = Department::active()->ordered()->get();
        } else {
            // Non-admin users only see their department
            $departments = Department::where('id', $this->getDepartmentFilter())->active()->ordered()->get();
        }
        $categories = IncidentCategory::active()->get();

        // AJAX response for dynamic updates
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'kpi' => $kpiData,
                    'trend' => $trendData,
                    'severity' => $severityDistribution,
                    'status' => $statusDistribution,
                    'department_performance' => $departmentPerformance,
                    'category_breakdown' => $categoryBreakdown,
                    'sla_compliance' => $slaCompliance,
                    'hourly' => $hourlyDistribution,
                    'resolution_trend' => $resolutionTimeTrend,
                ],
            ]);
        }

        return view('reports.kpi', compact(
            'kpiData',
            'trendData',
            'severityDistribution',
            'statusDistribution',
            'departmentPerformance',
            'categoryBreakdown',
            'slaCompliance',
            'hourlyDistribution',
            'resolutionTimeTrend',
            'departments',
            'categories',
            'period',
            'departmentId',
            'categoryId'
        ));
    }

    /**
     * Export KPI Report
     */
    public function exportKpi(Request $request, string $format)
    {
        $this->authorize('export-reports');

        $period = $request->get('period', 'last30days');
        $departmentId = $request->get('department_id');
        $categoryId = $request->get('category_id');

        // AUTO FILTER: Apply department restriction for non-admin users
        $autoDepartmentId = $this->getDepartmentFilter();
        if ($autoDepartmentId && !$this->hasFullAccess()) {
            $departmentId = $autoDepartmentId;
        }

        $dateRange = $this->getDateRange($period, $request);

        $reportData = [
            'kpi' => $this->getKpiMetrics($dateRange, $departmentId, $categoryId),
            'department_performance' => $this->getDepartmentPerformance($dateRange, $departmentId),
            'category_breakdown' => $this->getCategoryBreakdown($dateRange, $departmentId),
            'sla_compliance' => $this->getSlaComplianceByDepartment($dateRange, $departmentId),
            'severity_distribution' => $this->getSeverityDistribution($dateRange, $departmentId, $categoryId),
            'status_distribution' => $this->getStatusDistribution($dateRange, $departmentId, $categoryId),
            'period' => $period,
            'date_from' => $dateRange['start']->format('d M Y'),
            'date_to' => $dateRange['end']->format('d M Y'),
            'generated_at' => now()->format('d M Y, H:i'),
            'generated_by' => Auth::user()->name,
            'department_name' => $departmentId ? Department::find($departmentId)?->name : 'All Departments',
            'is_restricted' => !$this->hasFullAccess(),
        ];

        $filename = 'KPI_Report_'.now()->format('Y-m-d_His');

        return match ($format) {
            'excel' => Excel::download(new KpiReportExport($reportData), $filename.'.xlsx'),
            'pdf' => $this->exportKpiPdf($reportData, $filename),
            'csv' => $this->exportKpiCsv($reportData, $filename),
            default => abort(400, 'Unsupported format'),
        };
    }

    // ==========================================
    // DATA FETCHING METHODS
    // ==========================================

    /**
     * Get date range from period
     */
    private function getDateRange(string $period, Request $request): array
    {
        return match ($period) {
            'today' => ['start' => Carbon::today(), 'end' => Carbon::today()->endOfDay()],
            'yesterday' => ['start' => Carbon::yesterday(), 'end' => Carbon::yesterday()->endOfDay()],
            'last7days' => ['start' => Carbon::now()->subDays(7)->startOfDay(), 'end' => Carbon::now()->endOfDay()],
            'last30days' => ['start' => Carbon::now()->subDays(30)->startOfDay(), 'end' => Carbon::now()->endOfDay()],
            'thisMonth' => ['start' => Carbon::now()->startOfMonth(), 'end' => Carbon::now()->endOfDay()],
            'lastMonth' => ['start' => Carbon::now()->subMonth()->startOfMonth(), 'end' => Carbon::now()->subMonth()->endOfMonth()],
            'thisYear' => ['start' => Carbon::now()->startOfYear(), 'end' => Carbon::now()->endOfDay()],
            'custom' => [
                'start' => $request->date_from ? Carbon::parse($request->date_from)->startOfDay() : Carbon::now()->subDays(30),
                'end' => $request->date_to ? Carbon::parse($request->date_to)->endOfDay() : Carbon::now()->endOfDay(),
            ],
            default => ['start' => Carbon::now()->subDays(30), 'end' => Carbon::now()->endOfDay()],
        };
    }

    /**
     * Build base query with filters - AUTOMATIC DEPARTMENT FILTER
     */
    private function buildQuery(array $dateRange, ?int $departmentId = null, ?int $categoryId = null)
    {
        $query = Incident::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId));

        // Apply automatic department filter
        return $this->applyDepartmentFilter($query, $departmentId);
    }

    /**
     * Get KPI metrics
     */
    private function getKpiMetrics(array $dateRange, ?int $departmentId = null, ?int $categoryId = null): array
    {
        $query = $this->buildQuery($dateRange, $departmentId, $categoryId);
        $total = (clone $query)->count();

        if ($total === 0) {
            return [
                'total_incidents' => 0, 'open_incidents' => 0, 'in_progress_incidents' => 0,
                'resolved_incidents' => 0, 'closed_incidents' => 0, 'escalated_incidents' => 0,
                'rejected_incidents' => 0, 'sla_breaches' => 0,
                'avg_response_time' => 0, 'avg_resolution_time' => 0,
                'sla_compliance' => 100, 'resolution_rate' => 0, 'escalation_rate' => 0,
            ];
        }

        $breached = (clone $query)->where('sla_breach_count', '>', 0)->count();

        // Use DB facade for aggregate queries to avoid ordering conflicts
        $avgResponseTime = DB::table('incidents')
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->whereNotNull('acknowledged_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, acknowledged_at)) as avg_time')
            ->value('avg_time') ?? 0;

        $avgResolutionTime = DB::table('incidents')
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->whereNotNull('resolved_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_time')
            ->value('avg_time') ?? 0;

        // Apply auto department filter for DB queries
        $autoFilter = $this->getDepartmentFilter();
        if ($autoFilter && !$this->hasFullAccess()) {
            // The when clauses above already handle this, but ensure consistency
        }

        return [
            'total_incidents' => $total,
            'open_incidents' => (clone $query)->whereIn('status', ['open', 'acknowledged'])->count(),
            'in_progress_incidents' => (clone $query)->where('status', 'in_progress')->count(),
            'resolved_incidents' => (clone $query)->where('status', 'resolved')->count(),
            'closed_incidents' => (clone $query)->where('status', 'closed')->count(),
            'escalated_incidents' => (clone $query)->where('status', 'escalated')->count(),
            'rejected_incidents' => (clone $query)->where('status', 'rejected')->count(),
            'sla_breaches' => $breached,
            'avg_response_time' => round($avgResponseTime, 1),
            'avg_resolution_time' => round($avgResolutionTime, 1),
            'sla_compliance' => round((($total - $breached) / $total) * 100, 1),
            'resolution_rate' => round(((clone $query)->whereIn('status', ['resolved', 'closed'])->count() / $total) * 100, 1),
            'escalation_rate' => round(((clone $query)->where('status', 'escalated')->count() / $total) * 100, 1),
        ];
    }

    /**
     * Get trend data for line chart
     */
    private function getTrendData(array $dateRange, ?int $departmentId = null, ?int $categoryId = null): array
    {
        $days = $dateRange['start']->diffInDays($dateRange['end']) + 1;

        // Initialize all dates with 0
        $labels = [];
        $createdData = [];
        $resolvedData = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $dateRange['start']->copy()->addDays($i)->format('Y-m-d');
            $labels[] = $date;
            $createdData[$date] = 0;
            $resolvedData[$date] = 0;
        }

        // Get created counts
        $created = $this->buildQuery($dateRange, $departmentId, $categoryId)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Get resolved counts
        $resolved = $this->buildQuery($dateRange, $departmentId, $categoryId)
            ->whereNotNull('resolved_at')
            ->selectRaw('DATE(resolved_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        return [
            'labels' => $labels,
            'created' => array_values(array_merge($createdData, $created)),
            'resolved' => array_values(array_merge($resolvedData, $resolved)),
        ];
    }

    /**
     * Get severity distribution
     */
    private function getSeverityDistribution(array $dateRange, ?int $departmentId = null, ?int $categoryId = null): array
    {
        return $this->buildQuery($dateRange, $departmentId, $categoryId)
            ->selectRaw('severity, COUNT(*) as count')
            ->groupBy('severity')
            ->pluck('count', 'severity')
            ->toArray();
    }

    /**
     * Get status distribution
     */
    private function getStatusDistribution(array $dateRange, ?int $departmentId = null, ?int $categoryId = null): array
    {
        return $this->buildQuery($dateRange, $departmentId, $categoryId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    /**
     * Get department performance - FIXED with auto filter
     */
    private function getDepartmentPerformance(array $dateRange, ?int $departmentId = null): array
    {
        // If user is not admin, only show their department
        $autoFilter = $this->getDepartmentFilter();
        $departments = Department::active()->get();
        
        // If non-admin, filter departments
        if ($autoFilter && !$this->hasFullAccess()) {
            $departments = $departments->where('id', $autoFilter);
        }

        return $departments->map(function ($dept) use ($dateRange) {
            $query = Incident::where('department_id', $dept->id)
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);

            $total = (clone $query)->count();
            $resolved = (clone $query)->whereIn('status', ['resolved', 'closed'])->count();
            $breached = (clone $query)->where('sla_breach_count', '>', 0)->count();

            return [
                'name' => $dept->name,
                'code' => $dept->code,
                'color' => $dept->color,
                'total' => $total,
                'active' => (clone $query)->whereIn('status', ['open', 'acknowledged', 'in_progress', 'escalated'])->count(),
                'resolved' => $resolved,
                'escalated' => (clone $query)->where('status', 'escalated')->count(),
                'sla_compliance' => $total > 0 ? round((($total - $breached) / $total) * 100, 1) : 100,
                'avg_response_time' => round((clone $query)->whereNotNull('acknowledged_at')
                    ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, acknowledged_at)) as avg_time')
                    ->value('avg_time') ?? 0, 1),
                'performance' => $total > 0 ? round(($resolved / $total) * 100, 1) : 0,
            ];
        })->toArray();
    }

    /**
     * Get category breakdown
     */
    private function getCategoryBreakdown(array $dateRange, ?int $departmentId = null): array
    {
        $categories = IncidentCategory::active()->get();
        
        return $categories->map(function ($cat) use ($dateRange, $departmentId) {
            $query = Incident::where('category_id', $cat->id)
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
            
            // Apply department filter
            $query = $this->applyDepartmentFilter($query, $departmentId);

            $total = (clone $query)->count();
            $breached = (clone $query)->where('sla_breach_count', '>', 0)->count();

            return [
                'name' => $cat->name,
                'icon' => $cat->icon,
                'color' => $cat->color,
                'total' => $total,
                'open' => (clone $query)->whereIn('status', ['open', 'acknowledged', 'in_progress'])->count(),
                'resolved' => (clone $query)->whereIn('status', ['resolved', 'closed'])->count(),
                'sla_compliance' => $total > 0 ? round((($total - $breached) / $total) * 100, 1) : 100,
            ];
        })->filter(fn ($c) => $c['total'] > 0)->values()->toArray();
    }

    /**
     * Get SLA compliance by department
     */
    private function getSlaComplianceByDepartment(array $dateRange, ?int $departmentId = null): array
    {
        $departments = Department::active()->get();
        
        // If non-admin, only show their department
        $autoFilter = $this->getDepartmentFilter();
        if ($autoFilter && !$this->hasFullAccess()) {
            $departments = $departments->where('id', $autoFilter);
        }

        return $departments->mapWithKeys(function ($dept) use ($dateRange) {
            $query = Incident::where('department_id', $dept->id)
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);

            $total = (clone $query)->count();
            $breached = (clone $query)->where('sla_breach_count', '>', 0)->count();

            return [$dept->name => $total > 0 ? round((($total - $breached) / $total) * 100, 1) : 100];
        })->toArray();
    }

    /**
     * Get hourly distribution
     */
    private function getHourlyDistribution(array $dateRange, ?int $departmentId = null): array
    {
        $data = $this->buildQuery($dateRange, $departmentId)
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        // Fill missing hours
        $result = [];
        for ($h = 0; $h < 24; $h++) {
            $result[$h] = $data[$h] ?? 0;
        }

        return $result;
    }

    /**
     * Get resolution time trend
     */
    private function getResolutionTimeTrend(array $dateRange, ?int $departmentId = null): array
    {
        return $this->buildQuery($dateRange, $departmentId)
            ->whereNotNull('resolved_at')
            ->selectRaw('DATE(resolved_at) as date, AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_time')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('avg_time', 'date')
            ->map(fn ($v) => round($v, 1))
            ->toArray();
    }

    // ==========================================
    // EXPORT METHODS
    // ==========================================

    /**
     * Export KPI as PDF
     */
    private function exportKpiPdf(array $data, string $filename)
    {
        $pdf = Pdf::loadView('reports.exports.kpi-pdf', $data);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download($filename.'.pdf');
    }

    /**
     * Export KPI as CSV
     */
    private function exportKpiCsv(array $data, string $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'.csv"',
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');

            // Header
            fputcsv($file, ['IRMSystem - KPI Report']);
            fputcsv($file, ['Period', $data['date_from'].' to '.$data['date_to']]);
            fputcsv($file, ['Generated', $data['generated_at']]);
            fputcsv($file, ['Generated By', $data['generated_by']]);
            if (isset($data['is_restricted']) && $data['is_restricted']) {
                fputcsv($file, ['Note', 'This report is restricted to your department only']);
            }
            fputcsv($file, ['']);

            // KPI Metrics
            fputcsv($file, ['KPI METRICS']);
            fputcsv($file, ['Metric', 'Value']);
            foreach ($data['kpi'] as $key => $value) {
                fputcsv($file, [ucwords(str_replace('_', ' ', $key)), $value]);
            }
            fputcsv($file, ['']);

            // Department Performance
            fputcsv($file, ['DEPARTMENT PERFORMANCE']);
            fputcsv($file, ['Department', 'Total', 'Active', 'Resolved', 'Escalated', 'SLA %', 'Avg Response (min)']);
            foreach ($data['department_performance'] as $dept) {
                fputcsv($file, [$dept['name'], $dept['total'], $dept['active'], $dept['resolved'], $dept['escalated'], $dept['sla_compliance'], $dept['avg_response_time']]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Department Report - Overview
     */
    public function departmentReport(Request $request)
    {
        $this->authorize('view-reports');

        // AUTO FILTER: If user is not admin and trying to view another department, redirect
        $requestedDeptId = $request->get('department_id');
        $autoFilter = $this->getDepartmentFilter();
        
        if ($autoFilter && !$this->hasFullAccess()) {
            // Force the department filter to user's department
            if ($requestedDeptId && $requestedDeptId != $autoFilter) {
                return redirect()->route('reports.department', ['department_id' => $autoFilter])
                    ->with('warning', 'You can only view your own department data.');
            }
            $departmentId = $autoFilter;
        } else {
            $departmentId = $requestedDeptId;
        }

        // If department_id is provided, show detail view
        if ($departmentId) {
            return $this->departmentDetail($request, $departmentId);
        }

        // Get date range
        // $period = $request->get('period', 'last30days');
        $period = $request->get('period', 'thisYear');
        $dateRange = $this->getDateRange($period, $request);

        // Get all departments with stats
        $departments = Department::active()->ordered()
            ->withCount(['users', 'incidents'])
            ->get();

        // If non-admin, only show their department
        if ($autoFilter && !$this->hasFullAccess()) {
            $departments = $departments->where('id', $autoFilter);
        }

        // Get performance stats for each department
        $departmentStats = [];
        foreach ($departments as $dept) {
            $query = Incident::where('department_id', $dept->id)
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);

            $total = (clone $query)->count();
            $resolved = (clone $query)->whereIn('status', ['resolved', 'closed'])->count();
            $breached = (clone $query)->where('sla_breach_count', '>', 0)->count();

            $departmentStats[] = [
                'id' => $dept->id,
                'name' => $dept->name,
                'code' => $dept->code,
                'color' => $dept->color,
                'icon' => $dept->icon,
                'total' => $total,
                'active' => (clone $query)->whereIn('status', ['open', 'acknowledged', 'in_progress', 'escalated'])->count(),
                'resolved' => $resolved,
                'escalated' => (clone $query)->where('status', 'escalated')->count(),
                'sla_compliance' => $total > 0 ? round((($total - $breached) / $total) * 100, 1) : 100,
                'avg_response_time' => round((clone $query)->whereNotNull('acknowledged_at')
                    ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, acknowledged_at)) as avg_time')
                    ->value('avg_time') ?? 0, 1),
                'performance' => $total > 0 ? round(($resolved / $total) * 100, 1) : 0,
            ];
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'data' => $departmentStats]);
        }

        return view('reports.department', compact('departments', 'departmentStats'));
    }

    /**
     * Department Detail View - With Filters
     */
    private function departmentDetail(Request $request, int $deptId)
    {
        $department = Department::withCount(['users', 'incidents'])->findOrFail($deptId);

        // Get filter parameters
        $period = $request->get('period', 'last30days');
        $dateRange = $this->getDateRange($period, $request);
        $statusFilter = $request->get('status');
        $categoryFilter = $request->get('category_id');
        $severityFilter = $request->get('severity');
        $searchFilter = $request->get('search');

        // Base query for this department
        $baseQuery = Incident::where('department_id', $deptId)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);

        // Apply filters
        if ($statusFilter) {
            $baseQuery->where('status', $statusFilter);
        }
        if ($categoryFilter) {
            $baseQuery->where('category_id', $categoryFilter);
        }
        if ($severityFilter) {
            $baseQuery->where('severity', $severityFilter);
        }
        if ($searchFilter) {
            $baseQuery->where(function ($q) use ($searchFilter) {
                $q->where('title', 'like', "%{$searchFilter}%")
                    ->orWhere('incident_id', 'like', "%{$searchFilter}%")
                    ->orWhere('description', 'like', "%{$searchFilter}%");
            });
        }

        // KPI Data - based on filters
        $deptKpiData = $this->getKpiMetricsForQuery(clone $baseQuery);

        // Users
        $deptUsers = User::where('department_id', $deptId)->active()->get();

        // Categories
        $deptCategories = IncidentCategory::whereHas('incidents', function ($q) use ($deptId, $dateRange, $statusFilter, $categoryFilter, $severityFilter) {
            $q->where('department_id', $deptId)
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
            if ($statusFilter) {
                $q->where('status', $statusFilter);
            }
            if ($categoryFilter) {
                $q->where('category_id', $categoryFilter);
            }
            if ($severityFilter) {
                $q->where('severity', $severityFilter);
            }
        })->withCount(['incidents' => function ($q) use ($deptId, $dateRange, $statusFilter, $categoryFilter, $severityFilter) {
            $q->where('department_id', $deptId)
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
            if ($statusFilter) {
                $q->where('status', $statusFilter);
            }
            if ($categoryFilter) {
                $q->where('category_id', $categoryFilter);
            }
            if ($severityFilter) {
                $q->where('severity', $severityFilter);
            }
        }])->get();

        // Recent Incidents - based on filters
        $deptIncidents = (clone $baseQuery)
            ->with(['category', 'assignedTo', 'reporter'])
            ->latest()
            ->paginate(10)
            ->appends($request->query());

        // Trend Data - based on filters
        $trendQuery = Incident::where('department_id', $deptId)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        if ($statusFilter) {
            $trendQuery->where('status', $statusFilter);
        }
        if ($categoryFilter) {
            $trendQuery->where('category_id', $categoryFilter);
        }
        if ($severityFilter) {
            $trendQuery->where('severity', $severityFilter);
        }

        $deptTrendData = $trendQuery
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Get filter dropdowns
        $allCategories = IncidentCategory::active()->get();
        $allDepartments = Department::active()->ordered()->get();

        // Get stats for the department
        $total = (clone $baseQuery)->count();
        $resolved = (clone $baseQuery)->whereIn('status', ['resolved', 'closed'])->count();

        $departmentStats = [[
            'total' => $total,
            'active' => (clone $baseQuery)->whereIn('status', ['open', 'acknowledged', 'in_progress', 'escalated'])->count(),
            'resolved' => $resolved,
            'escalated' => (clone $baseQuery)->where('status', 'escalated')->count(),
            'performance' => $total > 0 ? round(($resolved / $total) * 100, 1) : 0,
        ]];

        return view('reports.department', compact(
            'department', 'deptKpiData', 'deptUsers', 'deptCategories',
            'deptIncidents', 'deptTrendData', 'allDepartments', 'allCategories',
            'departmentStats', 'period', 'statusFilter', 'categoryFilter',
            'severityFilter', 'searchFilter'
        ));
    }

    /**
     * Get KPI metrics from a pre-built query
     */
    private function getKpiMetricsForQuery($query): array
    {
        $total = (clone $query)->count();

        if ($total === 0) {
            return [
                'total_incidents' => 0, 'open_incidents' => 0, 'resolved_incidents' => 0,
                'closed_incidents' => 0, 'escalated_incidents' => 0, 'sla_breaches' => 0,
                'avg_response_time' => 0, 'avg_resolution_time' => 0,
                'sla_compliance' => 100, 'resolution_rate' => 0,
            ];
        }

        $breached = (clone $query)->where('sla_breach_count', '>', 0)->count();

        $avgResponseTime = DB::table('incidents')
            ->whereNull('deleted_at')
            ->whereNotNull('acknowledged_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, acknowledged_at)) as avg_time')
            ->value('avg_time') ?? 0;

        $avgResolutionTime = DB::table('incidents')
            ->whereNull('deleted_at')
            ->whereNotNull('resolved_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_time')
            ->value('avg_time') ?? 0;

        return [
            'total_incidents' => $total,
            'open_incidents' => (clone $query)->whereIn('status', ['open', 'acknowledged'])->count(),
            'resolved_incidents' => (clone $query)->where('status', 'resolved')->count(),
            'closed_incidents' => (clone $query)->where('status', 'closed')->count(),
            'escalated_incidents' => (clone $query)->where('status', 'escalated')->count(),
            'sla_breaches' => $breached,
            'avg_response_time' => round($avgResponseTime, 1),
            'avg_resolution_time' => round($avgResolutionTime, 1),
            'sla_compliance' => round((($total - $breached) / $total) * 100, 1),
            'resolution_rate' => round(((clone $query)->whereIn('status', ['resolved', 'closed'])->count() / $total) * 100, 1),
        ];
    }

    /**
     * Export Department Report - With Filters
     */
    public function exportDepartment(Request $request, string $format)
    {
        $this->authorize('export-reports');

        $deptId = $request->get('department_id');
        
        // AUTO FILTER: Apply department restriction for non-admin users
        $autoFilter = $this->getDepartmentFilter();
        if ($autoFilter && !$this->hasFullAccess()) {
            if ($deptId && $deptId != $autoFilter) {
                abort(403, 'You are not authorized to export data from other departments.');
            }
            $deptId = $autoFilter;
        }

        $period = $request->get('period', 'last30days');
        $dateRange = $this->getDateRange($period, $request);
        $statusFilter = $request->get('status');
        $categoryFilter = $request->get('category_id');
        $severityFilter = $request->get('severity');
        $searchFilter = $request->get('search');

        if ($deptId) {
            $department = Department::findOrFail($deptId);

            // Build query with filters
            $incidentQuery = Incident::where('department_id', $deptId)
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);

            if ($statusFilter) {
                $incidentQuery->where('status', $statusFilter);
            }
            if ($categoryFilter) {
                $incidentQuery->where('category_id', $categoryFilter);
            }
            if ($severityFilter) {
                $incidentQuery->where('severity', $severityFilter);
            }
            if ($searchFilter) {
                $incidentQuery->where(function ($q) use ($searchFilter) {
                    $q->where('title', 'like', "%{$searchFilter}%")
                        ->orWhere('incident_id', 'like', "%{$searchFilter}%");
                });
            }

            $data = [
                'department' => $department,
                'kpi' => $this->getKpiMetricsForQuery(clone $incidentQuery),
                'users' => User::where('department_id', $deptId)->active()->get(),
                'incidents' => $incidentQuery->with(['category', 'assignedTo', 'reporter'])->latest()->get(),
                'period' => $period,
                'filters' => [
                    'status' => $statusFilter,
                    'category' => $categoryFilter,
                    'severity' => $severityFilter,
                    'search' => $searchFilter,
                ],
                'generated_at' => now()->format('d M Y, H:i'),
                'generated_by' => Auth::user()->name,
                'is_restricted' => !$this->hasFullAccess(),
            ];
            $filename = "Department_Report_{$department->code}_".now()->format('Y-m-d_His');
        } else {
            // If no department ID, get all departments (or only user's if restricted)
            $departments = Department::active()->get();
            if ($autoFilter && !$this->hasFullAccess()) {
                $departments = $departments->where('id', $autoFilter);
            }

            $data = [
                'departments' => $departments,
                'stats' => $this->getDepartmentPerformance($dateRange),
                'period' => $period,
                'generated_at' => now()->format('d M Y, H:i'),
                'generated_by' => Auth::user()->name,
                'is_restricted' => !$this->hasFullAccess(),
            ];
            $filename = 'All_Departments_Report_'.now()->format('Y-m-d_His');
        }

        return match ($format) {
            'excel' => Excel::download(new DepartmentReportExport($data), $filename.'.xlsx'),
            'pdf' => Pdf::loadView('reports.exports.department-pdf', $data)
                ->setPaper('a4', 'landscape')
                ->download($filename.'.pdf'),
            default => abort(400),
        };
    }

    /**
     * SLA Compliance Report
     */
    public function slaReport(Request $request)
    {
        $this->authorize('view-reports');

        // Get filter parameters
        $period = $request->get('period', 'last30days');
        $dateRange = $this->getDateRange($period, $request);
        $departmentId = $request->get('department_id');
        $categoryId = $request->get('category_id');
        $statusFilter = $request->get('status');

        // AUTO FILTER: Apply department restriction for non-admin users
        $autoDepartmentId = $this->getDepartmentFilter();
        if ($autoDepartmentId && !$this->hasFullAccess()) {
            $departmentId = $autoDepartmentId;
        }

        // Build base query
        $baseQuery = Incident::whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        if ($departmentId) {
            $baseQuery->where('department_id', $departmentId);
        }
        if ($categoryId) {
            $baseQuery->where('category_id', $categoryId);
        }
        if ($statusFilter) {
            $baseQuery->where('status', $statusFilter);
        }

        // Overall SLA Compliance
        $totalIncidents = (clone $baseQuery)->count();
        $breachedIncidents = (clone $baseQuery)->where('sla_breach_count', '>', 0)->count();
        $overallSla = $totalIncidents > 0 ? round((($totalIncidents - $breachedIncidents) / $totalIncidents) * 100, 2) : 100;

        // SLA by Department
        $slaData = [];
        $departments = Department::active()->ordered()->get();
        
        // If non-admin, only show their department
        if ($autoDepartmentId && !$this->hasFullAccess()) {
            $departments = $departments->where('id', $autoDepartmentId);
        }

        foreach ($departments as $dept) {
            $deptQuery = Incident::where('department_id', $dept->id)
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
            if ($categoryId) {
                $deptQuery->where('category_id', $categoryId);
            }
            if ($statusFilter) {
                $deptQuery->where('status', $statusFilter);
            }

            $deptTotal = (clone $deptQuery)->count();
            $deptBreached = (clone $deptQuery)->where('sla_breach_count', '>', 0)->count();

            $slaData[$dept->name] = $deptTotal > 0 ? round((($deptTotal - $deptBreached) / $deptTotal) * 100, 2) : 100;
        }

        // SLA by Category
        $slaByCategory = [];
        $categories = IncidentCategory::active()->get();
        foreach ($categories as $cat) {
            $catQuery = Incident::where('category_id', $cat->id)
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
            if ($departmentId) {
                $catQuery->where('department_id', $departmentId);
            }
            if ($statusFilter) {
                $catQuery->where('status', $statusFilter);
            }

            $catTotal = (clone $catQuery)->count();
            $catBreached = (clone $catQuery)->where('sla_breach_count', '>', 0)->count();

            if ($catTotal > 0) {
                $slaByCategory[$cat->name] = [
                    'total' => $catTotal,
                    'breached' => $catBreached,
                    'compliance' => round((($catTotal - $catBreached) / $catTotal) * 100, 2),
                    'color' => $cat->color,
                    'icon' => $cat->icon,
                ];
            }
        }

        // SLA Breaches List
        $slaBreaches = Incident::where('sla_breach_count', '>', 0)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->when($statusFilter, fn ($q) => $q->where('status', $statusFilter))
            ->with(['department', 'category', 'assignedTo'])
            ->orderBy('sla_breach_count', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($incident) {
                return [
                    'id' => $incident->id,
                    'incident_id' => $incident->incident_id,
                    'title' => $incident->title,
                    'department' => $incident->department?->name ?? 'N/A',
                    'department_color' => $incident->department?->color,
                    'category' => $incident->category?->name ?? 'N/A',
                    'category_color' => $incident->category?->color,
                    'breach_count' => $incident->sla_breach_count,
                    'sla_due_at' => $incident->sla_due_at?->format('Y-m-d H:i') ?? 'N/A',
                    'status' => $incident->status,
                    'assigned_to' => $incident->assignedTo?->name,
                    'created_at' => $incident->created_at->format('d M Y'),
                ];
            });

        // Average response & resolution time
        $avgResponseTime = round((clone $baseQuery)->whereNotNull('acknowledged_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, acknowledged_at)) as avg_time')
            ->value('avg_time') ?? 0, 1);

        $avgResolutionTime = round((clone $baseQuery)->whereNotNull('resolved_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_time')
            ->value('avg_time') ?? 0, 1);

        // SLA trend over time (daily compliance)
        $slaTrend = [];
        $days = $dateRange['start']->diffInDays($dateRange['end']) + 1;
        for ($i = 0; $i < $days; $i++) {
            $date = $dateRange['start']->copy()->addDays($i);
            $dayQuery = Incident::whereDate('created_at', $date);
            if ($departmentId) {
                $dayQuery->where('department_id', $departmentId);
            }
            if ($categoryId) {
                $dayQuery->where('category_id', $categoryId);
            }

            $dayTotal = (clone $dayQuery)->count();
            $dayBreached = (clone $dayQuery)->where('sla_breach_count', '>', 0)->count();
            $slaTrend[$date->format('Y-m-d')] = $dayTotal > 0 ? round((($dayTotal - $dayBreached) / $dayTotal) * 100, 1) : 100;
        }

        // Get filter dropdowns
        if ($this->hasFullAccess()) {
            $allDepartments = Department::active()->ordered()->get();
        } else {
            $allDepartments = Department::where('id', $this->getDepartmentFilter())->active()->ordered()->get();
        }
        $allCategories = IncidentCategory::active()->get();

        // AJAX response
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'overall_sla' => $overallSla,
                    'sla_by_department' => $slaData,
                    'sla_by_category' => $slaByCategory,
                    'sla_breaches' => $slaBreaches,
                    'sla_trend' => $slaTrend,
                    'avg_response_time' => $avgResponseTime,
                    'avg_resolution_time' => $avgResolutionTime,
                    'total_incidents' => $totalIncidents,
                    'breached_incidents' => $breachedIncidents,
                ],
            ]);
        }

        return view('reports.sla', compact(
            'overallSla', 'slaData', 'slaByCategory', 'slaBreaches', 'slaTrend',
            'avgResponseTime', 'avgResolutionTime', 'totalIncidents', 'breachedIncidents',
            'allDepartments', 'allCategories', 'period', 'departmentId', 'categoryId'
        ));
    }

    /**
     * Export SLA Report
     */
    public function exportSla(Request $request, string $format)
    {
        $this->authorize('export-reports');

        $period = $request->get('period', 'last30days');
        $dateRange = $this->getDateRange($period, $request);
        $departmentId = $request->get('department_id');
        $categoryId = $request->get('category_id');

        // AUTO FILTER: Apply department restriction for non-admin users
        $autoDepartmentId = $this->getDepartmentFilter();
        if ($autoDepartmentId && !$this->hasFullAccess()) {
            $departmentId = $autoDepartmentId;
        }

        // Build base query
        $baseQuery = Incident::whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        if ($departmentId) {
            $baseQuery->where('department_id', $departmentId);
        }
        if ($categoryId) {
            $baseQuery->where('category_id', $categoryId);
        }

        // Calculate totals
        $totalIncidents = (clone $baseQuery)->count();
        $breachedIncidents = (clone $baseQuery)->where('sla_breach_count', '>', 0)->count();
        $overallSla = $totalIncidents > 0 ? round((($totalIncidents - $breachedIncidents) / $totalIncidents) * 100, 2) : 100;

        // SLA by Department
        $slaByDepartment = [];
        $departments = Department::active()->ordered()->get();
        
        if ($autoDepartmentId && !$this->hasFullAccess()) {
            $departments = $departments->where('id', $autoDepartmentId);
        }

        foreach ($departments as $dept) {
            $deptQuery = Incident::where('department_id', $dept->id)
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
            if ($categoryId) {
                $deptQuery->where('category_id', $categoryId);
            }

            $deptTotal = (clone $deptQuery)->count();
            $deptBreached = (clone $deptQuery)->where('sla_breach_count', '>', 0)->count();
            $slaByDepartment[$dept->name] = $deptTotal > 0 ? round((($deptTotal - $deptBreached) / $deptTotal) * 100, 2) : 100;
        }

        // SLA Breaches list
        $slaBreaches = Incident::where('sla_breach_count', '>', 0)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->with(['department', 'category', 'assignedTo'])
            ->orderBy('sla_breach_count', 'desc')
            ->get();

        $data = [
            'overall_sla' => $overallSla,
            'total_incidents' => $totalIncidents,
            'breached_incidents' => $breachedIncidents,
            'sla_by_department' => $slaByDepartment,
            'sla_breaches' => $slaBreaches,
            'period' => $period,
            'date_from' => $dateRange['start']->format('d M Y'),
            'date_to' => $dateRange['end']->format('d M Y'),
            'generated_at' => now()->format('d M Y, H:i'),
            'generated_by' => Auth::user()->name,
            'is_restricted' => !$this->hasFullAccess(),
        ];

        $filename = 'SLA_Report_'.now()->format('Y-m-d_His');

        return match ($format) {
            'excel' => Excel::download(new SlaReportExport($data), $filename.'.xlsx'),
            'pdf' => Pdf::loadView('reports.exports.sla-pdf', $data)
                ->setPaper('a4', 'landscape')
                ->download($filename.'.pdf'),
            default => abort(400),
        };
    }

    /**
     * Category Analysis Report
     */
    public function categoryReport(Request $request)
    {
        $this->authorize('view-reports');

        $period = $request->get('period', 'last30days');
        $dateRange = $this->getDateRange($period, $request);
        $departmentId = $request->get('department_id');
        $severityFilter = $request->get('severity');

        // AUTO FILTER: Apply department restriction for non-admin users
        $autoDepartmentId = $this->getDepartmentFilter();
        if ($autoDepartmentId && !$this->hasFullAccess()) {
            $departmentId = $autoDepartmentId;
        }

        // Build query
        $baseQuery = Incident::whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        if ($departmentId) {
            $baseQuery->where('department_id', $departmentId);
        }
        if ($severityFilter) {
            $baseQuery->where('severity', $severityFilter);
        }

        // Category-wise stats
        $categoryStats = IncidentCategory::active()->get()->map(function ($cat) use ($dateRange, $departmentId, $severityFilter) {
            $query = Incident::where('category_id', $cat->id)
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
            if ($departmentId) {
                $query->where('department_id', $departmentId);
            }
            if ($severityFilter) {
                $query->where('severity', $severityFilter);
            }

            $total = (clone $query)->count();
            $resolved = (clone $query)->whereIn('status', ['resolved', 'closed'])->count();
            $breached = (clone $query)->where('sla_breach_count', '>', 0)->count();
            $escalated = (clone $query)->where('status', 'escalated')->count();

            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'icon' => $cat->icon,
                'color' => $cat->color,
                'total' => $total,
                'resolved' => $resolved,
                'open' => (clone $query)->whereIn('status', ['open', 'acknowledged', 'in_progress'])->count(),
                'escalated' => $escalated,
                'breached' => $breached,
                'sla_compliance' => $total > 0 ? round((($total - $breached) / $total) * 100, 1) : 100,
                'resolution_rate' => $total > 0 ? round(($resolved / $total) * 100, 1) : 0,
                'avg_response_time' => round((clone $query)->whereNotNull('acknowledged_at')
                    ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, acknowledged_at)) as avg_time')
                    ->value('avg_time') ?? 0, 1),
            ];
        })->filter(fn ($c) => $c['total'] > 0)->sortByDesc('total')->values();

        // Category trend data
        $categoryTrend = [];
        $days = min($dateRange['start']->diffInDays($dateRange['end']), 30);
        $topCategories = $categoryStats->take(5);

        foreach ($topCategories as $cat) {
            $trendData = Incident::where('category_id', $cat['id'])
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('count', 'date')
                ->toArray();
            $categoryTrend[$cat['name']] = $trendData;
        }

        // Filters
        if ($this->hasFullAccess()) {
            $departments = Department::active()->ordered()->get();
        } else {
            $departments = Department::where('id', $this->getDepartmentFilter())->active()->ordered()->get();
        }
        $categories = IncidentCategory::active()->get();

        return view('reports.category', compact(
            'categoryStats', 'categoryTrend', 'topCategories',
            'departments', 'categories', 'period', 'departmentId', 'severityFilter'
        ));
    }

    /**
     * Export Category Report
     */
    public function exportCategory(Request $request, string $format)
    {
        $this->authorize('export-reports');

        $period = $request->get('period', 'last30days');
        $dateRange = $this->getDateRange($period, $request);
        $departmentId = $request->get('department_id');

        // AUTO FILTER: Apply department restriction for non-admin users
        $autoDepartmentId = $this->getDepartmentFilter();
        if ($autoDepartmentId && !$this->hasFullAccess()) {
            $departmentId = $autoDepartmentId;
        }

        $categoryStats = IncidentCategory::active()->get()->map(function ($cat) use ($dateRange, $departmentId) {
            $query = Incident::where('category_id', $cat->id)
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
            if ($departmentId) {
                $query->where('department_id', $departmentId);
            }

            $total = (clone $query)->count();

            return [
                'name' => $cat->name,
                'total' => $total,
                'resolved' => (clone $query)->whereIn('status', ['resolved', 'closed'])->count(),
                'breached' => (clone $query)->where('sla_breach_count', '>', 0)->count(),
            ];
        })->filter(fn ($c) => $c['total'] > 0);

        $filename = 'Category_Report_'.now()->format('Y-m-d_His');

        return match ($format) {
            'excel' => Excel::download(new CategoryReportExport($categoryStats->toArray()), $filename.'.xlsx'),
            'pdf' => Pdf::loadView('reports.exports.category-pdf', [
                'categories' => $categoryStats, 
                'period' => $period, 
                'generated_at' => now()->format('d M Y, H:i'), 
                'generated_by' => Auth::user()->name,
                'is_restricted' => !$this->hasFullAccess(),
            ])
                ->setPaper('a4', 'landscape')
                ->download($filename.'.pdf'),
            default => abort(400),
        };
    }

    /**
     * User Performance Report
     */
    public function userPerformanceReport(Request $request)
    {
        $this->authorize('view-reports');

        $period = $request->get('period', 'last30days');
        $dateRange = $this->getDateRange($period, $request);
        $departmentId = $request->get('department_id');
        $roleFilter = $request->get('role');

        // AUTO FILTER: Apply department restriction for non-admin users
        $autoDepartmentId = $this->getDepartmentFilter();
        if ($autoDepartmentId && !$this->hasFullAccess()) {
            $departmentId = $autoDepartmentId;
        }

        $query = User::active()
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->when($roleFilter, fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', $roleFilter)));

        // If non-admin, only show users in their department
        if ($autoDepartmentId && !$this->hasFullAccess()) {
            $query->where('department_id', $autoDepartmentId);
        }

        $userStats = $query->get()->map(function ($user) use ($dateRange) {
            $reported = Incident::where('reported_by', $user->id)
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count();

            $assigned = Incident::where('assigned_to', $user->id)
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count();

            $resolved = Incident::where('assigned_to', $user->id)
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                ->whereIn('status', ['resolved', 'closed'])->count();

            $avgResponse = DB::table('incidents')
                ->whereNull('deleted_at')
                ->where('assigned_to', $user->id)
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                ->whereNotNull('acknowledged_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, acknowledged_at)) as avg_time')
                ->value('avg_time') ?? 0;

            $avgResolution = DB::table('incidents')
                ->whereNull('deleted_at')
                ->where('assigned_to', $user->id)
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                ->whereNotNull('resolved_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_time')
                ->value('avg_time') ?? 0;

            return [
                'id' => $user->id,
                'name' => $user->name,
                'avatar_url' => $user->avatar_url,
                'department' => $user->department?->name,
                'role' => $user->getFirstRoleName(),
                'reported' => $reported,
                'assigned' => $assigned,
                'resolved' => $resolved,
                'avg_response' => round($avgResponse, 1),
                'avg_resolution' => round($avgResolution, 1),
            ];
        })->filter(fn ($u) => $u['reported'] > 0 || $u['assigned'] > 0)->sortByDesc('resolved');

        if ($this->hasFullAccess()) {
            $departments = Department::active()->ordered()->get();
        } else {
            $departments = Department::where('id', $this->getDepartmentFilter())->active()->ordered()->get();
        }
        $roles = Role::all();

        return view('reports.user-performance', compact('userStats', 'departments', 'roles', 'period', 'departmentId', 'roleFilter'));
    }

    /**
     * Escalation Analysis Report
     */
    public function escalationReport(Request $request)
    {
        $this->authorize('view-reports');

        $period = $request->get('period', 'last30days');
        $dateRange = $this->getDateRange($period, $request);
        $departmentId = $request->get('department_id');

        // AUTO FILTER: Apply department restriction for non-admin users
        $autoDepartmentId = $this->getDepartmentFilter();
        if ($autoDepartmentId && !$this->hasFullAccess()) {
            $departmentId = $autoDepartmentId;
        }

        // Escalation stats
        $escalatedIncidents = Incident::where('status', 'escalated')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->with(['department', 'category', 'escalatedTo'])
            ->latest()
            ->get();

        $totalEscalated = $escalatedIncidents->count();

        // Escalation by department
        $escalationByDept = $escalatedIncidents->groupBy('department.name')->map->count();

        // Escalation by level
        $escalationLevels = Escalation::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->when($departmentId, fn ($q) => $q->whereHas('incident', fn ($i) => $i->where('department_id', $departmentId)))
            ->selectRaw('level, COUNT(*) as count')
            ->groupBy('level')
            ->pluck('count', 'level')
            ->toArray();

        // Auto vs Manual escalations
        $autoEscalated = Escalation::where('escalated_by', 1)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count();
        $manualEscalated = $totalEscalated - $autoEscalated;

        if ($this->hasFullAccess()) {
            $departments = Department::active()->ordered()->get();
        } else {
            $departments = Department::where('id', $this->getDepartmentFilter())->active()->ordered()->get();
        }

        return view('reports.escalation', compact(
            'escalatedIncidents', 'totalEscalated', 'escalationByDept',
            'escalationLevels', 'autoEscalated', 'manualEscalated',
            'departments', 'period', 'departmentId'
        ));
    }

    /**
     * Custom Date Range Report
     */
    public function customReport(Request $request)
    {
        $this->authorize('view-reports');

        $dateFrom = $request->get('date_from', now()->subDays(7)->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));
        $departmentId = $request->get('department_id');
        $categoryId = $request->get('category_id');
        $statusFilter = $request->get('status');
        $severityFilter = $request->get('severity');

        // AUTO FILTER: Apply department restriction for non-admin users
        $autoDepartmentId = $this->getDepartmentFilter();
        if ($autoDepartmentId && !$this->hasFullAccess()) {
            $departmentId = $autoDepartmentId;
        }

        $dateRange = [
            'start' => Carbon::parse($dateFrom)->startOfDay(),
            'end' => Carbon::parse($dateTo)->endOfDay(),
        ];

        $baseQuery = Incident::whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        if ($departmentId) {
            $baseQuery->where('department_id', $departmentId);
        }
        if ($categoryId) {
            $baseQuery->where('category_id', $categoryId);
        }
        if ($statusFilter) {
            $baseQuery->where('status', $statusFilter);
        }
        if ($severityFilter) {
            $baseQuery->where('severity', $severityFilter);
        }

        $incidents = $baseQuery->with(['department', 'category', 'assignedTo', 'reporter'])
            ->latest()
            ->paginate(20)
            ->appends($request->query());

        $kpiData = $this->getKpiMetricsForQuery(clone $baseQuery);

        if ($this->hasFullAccess()) {
            $departments = Department::active()->ordered()->get();
        } else {
            $departments = Department::where('id', $this->getDepartmentFilter())->active()->ordered()->get();
        }
        $categories = IncidentCategory::active()->get();

        return view('reports.custom', compact(
            'incidents', 'kpiData', 'dateFrom', 'dateTo',
            'departments', 'categories', 'departmentId', 'categoryId', 'statusFilter', 'severityFilter'
        ));
    }

    /**
     * Export Custom Date Report
     */
    public function exportCustom(Request $request, string $format)
    {
        $this->authorize('export-reports');

        $dateFrom = $request->get('date_from', now()->subDays(7)->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));
        $departmentId = $request->get('department_id');
        $categoryId = $request->get('category_id');
        $statusFilter = $request->get('status');
        $severityFilter = $request->get('severity');

        // AUTO FILTER: Apply department restriction for non-admin users
        $autoDepartmentId = $this->getDepartmentFilter();
        if ($autoDepartmentId && !$this->hasFullAccess()) {
            $departmentId = $autoDepartmentId;
        }

        $dateRange = [
            'start' => Carbon::parse($dateFrom)->startOfDay(),
            'end' => Carbon::parse($dateTo)->endOfDay(),
        ];

        $query = Incident::whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }
        if ($severityFilter) {
            $query->where('severity', $severityFilter);
        }

        $incidents = $query->with(['department', 'category', 'assignedTo', 'reporter'])->latest()->get();

        $data = [
            'incidents' => $incidents,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'total' => $incidents->count(),
            'generated_at' => now()->format('d M Y, H:i'),
            'generated_by' => Auth::user()->name,
            'is_restricted' => !$this->hasFullAccess(),
            'department_name' => $departmentId ? Department::find($departmentId)?->name : 'All Departments',
        ];

        $filename = 'Custom_Report_'.now()->format('Y-m-d_His');

        return match ($format) {
            'excel' => Excel::download(new CustomReportExport($data), $filename.'.xlsx'),
            'pdf' => Pdf::loadView('reports.exports.custom-pdf', $data)
                ->setPaper('a4', 'landscape')
                ->download($filename.'.pdf'),
            default => abort(400),
        };
    }
    
    
    
   
/**
 * Export All Data - Comprehensive Excel Export
 * 
 * This handles the "Export All Data" quick export button.
 * Generates a multi-sheet Excel file with:
 *   - Summary (KPI metrics)
 *   - All Incidents
 *   - Department breakdown
 *   - Category breakdown
 *   - SLA compliance
 *   - User performance
 * 
 * Route: /reports/export/{type}
 * Example: /reports/export/all
 *          /reports/export/all?format=excel
 *          /reports/export/incidents?format=excel
 */
public function export(Request $request, string $type = 'all')
{
    $this->authorize('export-reports');

    // Get format from query string, default to excel
    $format = $request->get('format', 'excel');

    // Validate format
    if (!in_array($format, ['excel', 'pdf', 'csv'])) {
        abort(400, 'Unsupported export format. Allowed: excel, pdf, csv');
    }

    // Get date range (default: last 30 days for "export all", or custom)
    $period = $request->get('period', 'last30days');
    $dateRange = $this->getDateRange($period, $request);

    // AUTO FILTER: Apply department restriction for non-admin users
    $departmentId = $request->get('department_id');
    $autoDepartmentId = $this->getDepartmentFilter();
    if ($autoDepartmentId && !$this->hasFullAccess()) {
        $departmentId = $autoDepartmentId;
    }

    // Route to appropriate export method
    return match ($type) {
        'all'         => $this->exportAll($request, $format, $dateRange, $departmentId),
        'incidents'   => $this->exportAllIncidents($request, $format, $dateRange, $departmentId),
        'kpi'         => $this->exportKpi($request, $format),
        'department'  => $this->exportDepartment($request, $format),
        'sla'         => $this->exportSla($request, $format),
        'category'    => $this->exportCategory($request, $format),
        'custom'      => $this->exportCustom($request, $format),
        default       => abort(404, "Unknown export type: {$type}"),
    };
}

/**
 * Export ALL data - comprehensive multi-sheet export
 */
protected function exportAll(Request $request, string $format, array $dateRange, ?int $departmentId = null)
{
    $categoryId = $request->get('category_id');

    // Build all datasets
    $data = [
        'kpi'                    => $this->getKpiMetrics($dateRange, $departmentId, $categoryId),
        'department_performance' => $this->getDepartmentPerformance($dateRange, $departmentId),
        'category_breakdown'     => $this->getCategoryBreakdown($dateRange, $departmentId),
        'sla_compliance'         => $this->getSlaComplianceByDepartment($dateRange, $departmentId),
        'severity_distribution'  => $this->getSeverityDistribution($dateRange, $departmentId, $categoryId),
        'status_distribution'    => $this->getStatusDistribution($dateRange, $departmentId, $categoryId),
        'incidents'              => $this->getAllIncidentsForExport($dateRange, $departmentId, $categoryId),
        'period'                 => $dateRange['start']->format('d M Y') . ' to ' . $dateRange['end']->format('d M Y'),
        'date_from'              => $dateRange['start']->format('d M Y'),
        'date_to'                => $dateRange['end']->format('d M Y'),
        'generated_at'           => now()->format('d M Y, H:i'),
        'generated_by'           => Auth::user()->name,
        'department_name'        => $departmentId ? Department::find($departmentId)?->name : 'All Departments',
        'is_restricted'          => !$this->hasFullAccess(),
    ];

    $filename = 'Complete_Report_' . now()->format('Y-m-d_His');

    return match ($format) {
        'excel' => Excel::download(new \App\Exports\CompleteReportExport($data), $filename . '.xlsx'),
        'pdf'   => $this->exportAllPdf($data, $filename),
        'csv'   => $this->exportAllCsv($data, $filename),
    };
}

/**
 * Export only incidents list
 */
protected function exportAllIncidents(Request $request, string $format, array $dateRange, ?int $departmentId = null)
{
    $categoryId = $request->get('category_id');
    $statusFilter = $request->get('status');
    $severityFilter = $request->get('severity');

    $incidents = $this->getAllIncidentsForExport(
        $dateRange,
        $departmentId,
        $categoryId,
        $statusFilter,
        $severityFilter
    );

    $data = [
        'incidents'       => $incidents,
        'date_from'       => $dateRange['start']->format('d M Y'),
        'date_to'         => $dateRange['end']->format('d M Y'),
        'total'           => $incidents->count(),
        'generated_at'    => now()->format('d M Y, H:i'),
        'generated_by'    => Auth::user()->name,
        'is_restricted'   => !$this->hasFullAccess(),
        'department_name' => $departmentId ? Department::find($departmentId)?->name : 'All Departments',
    ];

    $filename = 'All_Incidents_' . now()->format('Y-m-d_His');

    return match ($format) {
        'excel' => Excel::download(new \App\Exports\CustomReportExport($data), $filename . '.xlsx'),
        'pdf'   => Pdf::loadView('reports.exports.custom-pdf', $data)
                        ->setPaper('a4', 'landscape')
                        ->download($filename . '.pdf'),
        'csv'   => $this->exportIncidentsCsv($data, $filename),
    };
}

/**
 * Get all incidents for export with relationships loaded
 */
protected function getAllIncidentsForExport(
    array $dateRange,
    ?int $departmentId = null,
    ?int $categoryId = null,
    ?string $status = null,
    ?string $severity = null
) {
    $query = Incident::with([
        // 'airport',
        'department',
        'category',
        'assignedTo',
        'reporter',
    ])
        ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);

    if ($departmentId) {
        $query->where('department_id', $departmentId);
    }
    if ($categoryId) {
        $query->where('category_id', $categoryId);
    }
    if ($status) {
        $query->where('status', $status);
    }
    if ($severity) {
        $query->where('severity', $severity);
    }

    return $query->orderBy('created_at', 'desc')->get();
}

/**
 * Export All data as CSV (flat structure)
 */
protected function exportAllCsv(array $data, string $filename)
{
    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
    ];

    $callback = function () use ($data) {
        $file = fopen('php://output', 'w');

        // Header Info
        fputcsv($file, ['IRMSystem - Complete Report']);
        fputcsv($file, ['Period', $data['date_from'] . ' to ' . $data['date_to']]);
        fputcsv($file, ['Department', $data['department_name']]);
        fputcsv($file, ['Generated', $data['generated_at']]);
        fputcsv($file, ['Generated By', $data['generated_by']]);
        if ($data['is_restricted']) {
            fputcsv($file, ['Note', 'This report is restricted to your department only']);
        }
        fputcsv($file, ['']);

        // KPI Summary
        fputcsv($file, ['=== KPI SUMMARY ===']);
        fputcsv($file, ['Metric', 'Value']);
        foreach ($data['kpi'] as $key => $value) {
            fputcsv($file, [ucwords(str_replace('_', ' ', $key)), $value]);
        }
        fputcsv($file, ['']);

        // Department Performance
        fputcsv($file, ['=== DEPARTMENT PERFORMANCE ===']);
        fputcsv($file, ['Department', 'Total', 'Active', 'Resolved', 'Escalated', 'SLA %', 'Avg Response (min)', 'Performance %']);
        foreach ($data['department_performance'] as $dept) {
            fputcsv($file, [
                $dept['name'] ?? '',
                $dept['total'] ?? 0,
                $dept['active'] ?? 0,
                $dept['resolved'] ?? 0,
                $dept['escalated'] ?? 0,
                $dept['sla_compliance'] ?? 0,
                $dept['avg_response_time'] ?? 0,
                $dept['performance'] ?? 0,
            ]);
        }
        fputcsv($file, ['']);

        // Category Breakdown
        fputcsv($file, ['=== CATEGORY BREAKDOWN ===']);
        fputcsv($file, ['Category', 'Total', 'Open', 'Resolved', 'SLA %']);
        foreach ($data['category_breakdown'] as $cat) {
            fputcsv($file, [
                $cat['name'] ?? '',
                $cat['total'] ?? 0,
                $cat['open'] ?? 0,
                $cat['resolved'] ?? 0,
                $cat['sla_compliance'] ?? 0,
            ]);
        }
        fputcsv($file, ['']);

        // Incidents
        fputcsv($file, ['=== ALL INCIDENTS ===']);
        fputcsv($file, [
            'Incident ID', 'Title', 'Airport', 'Department', 'Category',
            'Severity', 'Priority', 'Status', 'Reported By', 'Assigned To',
            'Created At', 'Acknowledged At', 'Resolved At', 'Closed At',
            'SLA Due At', 'SLA Breaches', 'Location',
        ]);
        foreach ($data['incidents'] as $incident) {
            fputcsv($file, [
                $incident->incident_id,
                $incident->title,
                $incident->airport?->code ?? 'N/A',
                $incident->department?->name ?? 'N/A',
                $incident->category?->name ?? 'N/A',
                $incident->severity,
                $incident->priority,
                $incident->status,
                $incident->reportedBy?->name ?? 'N/A',
                $incident->assignedTo?->name ?? 'Unassigned',
                optional($incident->created_at)->format('Y-m-d H:i'),
                optional($incident->acknowledged_at)->format('Y-m-d H:i'),
                optional($incident->resolved_at)->format('Y-m-d H:i'),
                optional($incident->closed_at)->format('Y-m-d H:i'),
                optional($incident->sla_due_at)->format('Y-m-d H:i'),
                $incident->sla_breach_count,
                $incident->location ?? 'N/A',
            ]);
        }

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

/**
 * Export incidents as CSV
 */
protected function exportIncidentsCsv(array $data, string $filename)
{
    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
    ];

    $callback = function () use ($data) {
        $file = fopen('php://output', 'w');

        fputcsv($file, ['Incident ID', 'Title', 'Airport', 'Department', 'Category',
            'Severity', 'Priority', 'Status', 'Reported By', 'Assigned To',
            'Created At', 'Resolved At', 'SLA Breaches']);

        foreach ($data['incidents'] as $incident) {
            fputcsv($file, [
                $incident->incident_id,
                $incident->title,
                $incident->airport?->code ?? 'N/A',
                $incident->department?->name ?? 'N/A',
                $incident->category?->name ?? 'N/A',
                $incident->severity,
                $incident->priority,
                $incident->status,
                $incident->reportedBy?->name ?? 'N/A',
                $incident->assignedTo?->name ?? 'Unassigned',
                optional($incident->created_at)->format('Y-m-d H:i'),
                optional($incident->resolved_at)->format('Y-m-d H:i'),
                $incident->sla_breach_count,
            ]);
        }

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

/**
 * Export All data as PDF
 */
protected function exportAllPdf(array $data, string $filename)
{
    $pdf = Pdf::loadView('reports.exports.complete-pdf', $data);
    $pdf->setPaper('a4', 'landscape');

    return $pdf->download($filename . '.pdf');
}
}