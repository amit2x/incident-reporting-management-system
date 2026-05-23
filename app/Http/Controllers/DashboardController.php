<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\Department;
use App\Repositories\IncidentRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    protected IncidentRepository $incidentRepository;

    public function __construct(IncidentRepository $incidentRepository)
    {
        $this->incidentRepository = $incidentRepository;
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Get user stats
        $stats = $user->getDashboardStats();

        // Filters
        $filters = [];
        if (!$user->isAdmin()) {
            $filters['department_id'] = $user->department_id;
        }

        // Get recent incidents for feed
        $recentIncidents = $this->incidentRepository->getFeedIncidents($filters, 10);

        // Get chart data
        $severityDistribution = $this->getSeverityDistribution($filters);
        $dailyTrends = $this->getDailyTrends($filters);

        // Department performance (admin only)
        $departmentPerformance = [];
        if ($user->isAdmin()) {
            $departmentPerformance = $this->getDepartmentPerformance();
        }

        // Get critical/overdue counts
        $criticalCount = Incident::critical()
            ->when(!$user->isAdmin(), fn($q) => $q->where('department_id', $user->department_id))
            ->whereIn('status', ['open', 'acknowledged', 'in_progress', 'escalated'])
            ->count();

        $overdueCount = Incident::overdue()
            ->when(!$user->isAdmin(), fn($q) => $q->where('department_id', $user->department_id))
            ->count();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'recent_incidents' => $recentIncidents->items(),
                    'severity_distribution' => $severityDistribution,
                    'daily_trends' => $dailyTrends,
                    'department_performance' => $departmentPerformance,
                    'critical_count' => $criticalCount,
                    'overdue_count' => $overdueCount,
                ]
            ]);
        }

        return view('dashboard', compact(
            'stats',
            'recentIncidents',
            'severityDistribution',
            'dailyTrends',
            'departmentPerformance',
            'criticalCount',
            'overdueCount'
        ));
    }

    /**
     * Get severity distribution for charts
     */
    protected function getSeverityDistribution(array $filters = []): array
    {
        $query = Incident::query();

        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        return $query->selectRaw('severity, COUNT(*) as count')
            ->groupBy('severity')
            ->pluck('count', 'severity')
            ->toArray();
    }

    /**
     * Get daily trends for last 30 days
     */
    protected function getDailyTrends(array $filters = [], int $days = 30): array
    {
        $query = Incident::whereDate('created_at', '>=', Carbon::now()->subDays($days));

        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        // Fill all days with 0
        $trends = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $trends[$date] = 0;
        }

        // Get actual counts
        $actualData = $query->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Merge actual data
        foreach ($actualData as $date => $count) {
            if (isset($trends[$date])) {
                $trends[$date] = $count;
            }
        }

        return $trends;
    }

    /**
     * Get department performance data
     */
    protected function getDepartmentPerformance(): array
    {
        return Department::active()
            ->withCount(['incidents as total_incidents'])
            ->withCount(['incidents as open_incidents_count' => function ($query) {
                $query->whereIn('status', ['open', 'acknowledged', 'in_progress']);
            }])
            ->withCount(['incidents as resolved_incidents_count' => function ($query) {
                $query->whereIn('status', ['resolved', 'closed']);
            }])
            ->get()
            ->map(function ($dept) {
                return [
                    'name' => $dept->name,
                    'code' => $dept->code,
                    'color' => $dept->color,
                    'total_incidents' => $dept->total_incidents,
                    'open_incidents_count' => $dept->open_incidents_count,
                    'resolved_incidents_count' => $dept->resolved_incidents_count,
                ];
            })
            ->toArray();
    }
}
