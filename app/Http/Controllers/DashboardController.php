<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Incident;
use App\Repositories\IncidentRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
    /**
     * Show the application dashboard.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // ==========================================
        // STATISTICS
        // ==========================================
        $stats = $user->getDashboardStats();

        // Filters for department-based users
        $filters = [];
        if (! $user->isAdmin()) {
            $filters['department_id'] = $user->department_id;
        }

        // Handle feed filter
        $feedFilter = $request->get('feed_filter', 'all');
        switch ($feedFilter) {
            case 'escalated':
                $filters['escalated_to'] = $user->id;
                $filters['status'] = 'escalated';
                break;
            case 'assigned':
                $filters['assigned_to'] = $user->id;
                $filters['status'] = ['open', 'acknowledged', 'in_progress'];
                break;
            case 'critical':
                $filters['severity'] = ['critical', 'high'];
                $filters['status'] = ['open', 'acknowledged', 'in_progress', 'escalated'];
                break;
            default:
                // 'all' - no additional filters
                break;
        }

        // ==========================================
        // RECENT INCIDENTS FEED
        // ==========================================
        $recentIncidents = $this->incidentRepository->getFeedIncidents($filters, 10);

        // ==========================================
        // CHART DATA
        // ==========================================
        $severityDistribution = $this->getSeverityDistribution($filters);
        $dailyTrends = $this->getDailyTrends($filters);
        $statusDistribution = $this->getStatusDistribution($filters);

        // ==========================================
        // DEPARTMENT PERFORMANCE (Admin Only)
        // ==========================================
        $departmentPerformance = [];
        if ($user->isAdmin()) {
            $departmentPerformance = $this->getDepartmentPerformance();
        }

        // ==========================================
        // CRITICAL & OVERDUE COUNTS
        // ==========================================
        $criticalIncidents = Incident::critical()
            ->when(! $user->isAdmin(), fn ($q) => $q->where('department_id', $user->department_id))
            ->whereIn('status', ['open', 'acknowledged', 'in_progress', 'escalated'])
            ->with(['department', 'category', 'assignedTo'])
            ->latest()
            ->limit(10)
            ->get();

        $criticalCount = $criticalIncidents->count();

        $overdueIncidents = Incident::overdue()
            ->when(! $user->isAdmin(), fn ($q) => $q->where('department_id', $user->department_id))
            ->with(['department', 'category', 'assignedTo'])
            ->latest()
            ->limit(10)
            ->get();

        $overdueCount = $overdueIncidents->count();

        // ==========================================
        // MY INCIDENTS - ESCALATED TO ME
        // ==========================================
        $escalatedToMe = Incident::where('escalated_to', $user->id)
            ->where('status', 'escalated')
            ->with(['department', 'category', 'reporter', 'assignedTo'])
            ->latest('escalated_at')
            ->limit(5)
            ->get();

        // ==========================================
        // MY INCIDENTS - ASSIGNED TO ME
        // ==========================================
        $assignedToMe = Incident::where('assigned_to', $user->id)
            ->whereIn('status', ['open', 'acknowledged', 'in_progress'])
            ->with(['department', 'category', 'reporter'])
            ->latest()
            ->limit(5)
            ->get();

        // ==========================================
        // MY INCIDENTS - REPORTED BY ME
        // ==========================================
        $reportedByMe = Incident::where('reported_by', $user->id)
            ->with(['department', 'category', 'assignedTo'])
            ->latest()
            ->limit(5)
            ->get();

        // ==========================================
        // QUICK STATS FOR CARDS
        // ==========================================
        $myStats = [
            'assigned_count' => Incident::where('assigned_to', $user->id)
                ->whereIn('status', ['open', 'acknowledged', 'in_progress'])->count(),
            'escalated_count' => $escalatedToMe->count(),
            'reported_count' => Incident::where('reported_by', $user->id)->count(),
            'resolved_count' => Incident::where('assigned_to', $user->id)
                ->whereIn('status', ['resolved', 'closed'])->count(),
        ];

        // ==========================================
        // AJAX RESPONSE
        // ==========================================
        // if ($request->ajax() || $request->wantsJson()) {
        //     return response()->json([
        //         'success' => true,
        //         'data' => [
        //             'stats' => $stats,
        //             'my_stats' => $myStats,
        //             'recent_incidents' => $recentIncidents->items(),
        //             'severity_distribution' => $severityDistribution,
        //             'status_distribution' => $statusDistribution,
        //             'daily_trends' => $dailyTrends,
        //             'department_performance' => $departmentPerformance,
        //             'critical_incidents' => $criticalIncidents,
        //             'overdue_incidents' => $overdueIncidents,
        //             'escalated_to_me' => $escalatedToMe,
        //             'assigned_to_me' => $assignedToMe,
        //             'reported_by_me' => $reportedByMe,
        //             'critical_count' => $criticalCount,
        //             'overdue_count' => $overdueCount,
        //         ],
        //     ]);
        // }

        // ==========================================
        // AJAX RESPONSE
        // ==========================================
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'my_stats' => $myStats,
                    'recent_incidents' => $recentIncidents->map(function ($incident) {
                        return [
                            'id' => $incident->id,
                            'incident_id' => $incident->incident_id,
                            'title' => $incident->title,
                            'status' => $incident->status,
                            'priority' => $incident->priority,
                            'severity' => $incident->severity,
                            'is_overdue' => $incident->is_overdue,
                            'escalated_to' => $incident->escalated_to,
                            'created_at' => $incident->created_at->toISOString(),
                            'created_at_diff' => $incident->created_at->diffForHumans(),
                            'reporter' => [
                                'name' => $incident->reporter?->name,
                                'avatar_url' => $incident->reporter?->avatar_url,
                            ],
                            'department' => [
                                'name' => $incident->department?->name,
                                'code' => $incident->department?->code,
                                'color' => $incident->department?->color,
                            ],
                            'category' => [
                                'name' => $incident->category?->name,
                            ],
                        ];
                    })->values()->toArray(),
                    'severity_distribution' => $severityDistribution,
                    'status_distribution' => $statusDistribution,
                    'daily_trends' => $dailyTrends,
                    'department_performance' => $departmentPerformance,
                    'critical_incidents' => $criticalIncidents,
                    'overdue_incidents' => $overdueIncidents,
                    'escalated_to_me' => $escalatedToMe,
                    'assigned_to_me' => $assignedToMe,
                    'reported_by_me' => $reportedByMe,
                    'critical_count' => $criticalCount,
                    'overdue_count' => $overdueCount,
                    // ... other data if any ...
                ],
            ]);
        }

        // ==========================================
        // RETURN VIEW
        // ==========================================
        return view('dashboard', compact(
            'stats',
            'myStats',
            'recentIncidents',
            'severityDistribution',
            'dailyTrends',
            'statusDistribution',
            'departmentPerformance',
            'criticalIncidents',
            'overdueIncidents',
            'escalatedToMe',
            'assignedToMe',
            'reportedByMe',
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
        if (! empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        return $query->selectRaw('severity, COUNT(*) as count')
            ->groupBy('severity')
            ->pluck('count', 'severity')
            ->toArray();
    }

    /**
     * Get status distribution for charts
     */
    protected function getStatusDistribution(array $filters = []): array
    {
        $query = Incident::query();
        if (! empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        return $query->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    /**
     * Get daily trends for charts
     */
    protected function getDailyTrends(array $filters = [], int $days = 30): array
    {
        $query = Incident::whereDate('created_at', '>=', Carbon::now()->subDays($days));
        if (! empty($filters['department_id'])) {
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

        return array_merge($trends, $actualData);
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
