<?php
// app/Http/Controllers/Api/DashboardController.php

namespace App\Http\Controllers\Api;

use App\Repositories\IncidentRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends BaseApiController
{
    protected IncidentRepository $incidentRepository;

    public function __construct(IncidentRepository $incidentRepository)
    {
        $this->incidentRepository = $incidentRepository;
    }

    /**
     * Get dashboard statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $filters = [];

        if (!$this->isAdmin()) {
            $filters['department_id'] = $user->department_id;
        }

        $stats = $this->incidentRepository->getStats($filters);
        $userStats = $user->getDashboardStats();

        return $this->successResponse([
            'overall' => $stats,
            'user' => $userStats,
        ]);
    }

    /**
     * Get incident feed
     */
    public function feed(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $filters = $request->only(['status', 'severity', 'category_id']);

        if (!$this->isAdmin()) {
            $filters['department_id'] = $user->department_id;
        }

        $incidents = $this->incidentRepository->getFeedIncidents($filters);

        return $this->paginatedResponse($incidents);
    }

    /**
     * Get chart data
     */
    public function charts(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $filters = [];

        if (!$this->isAdmin()) {
            $filters['department_id'] = $user->department_id;
        }

        $severityData = \App\Models\Incident::when(!empty($filters['department_id']), function ($query) use ($filters) {
            return $query->where('department_id', $filters['department_id']);
        })
            ->selectRaw('severity, COUNT(*) as count')
            ->groupBy('severity')
            ->pluck('count', 'severity')
            ->toArray();

        $statusData = \App\Models\Incident::when(!empty($filters['department_id']), function ($query) use ($filters) {
            return $query->where('department_id', $filters['department_id']);
        })
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $trendData = \App\Models\Incident::when(!empty($filters['department_id']), function ($query) use ($filters) {
            return $query->where('department_id', $filters['department_id']);
        })
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        return $this->successResponse([
            'severity_distribution' => $severityData,
            'status_distribution' => $statusData,
            'daily_trends' => $trendData,
        ]);
    }

    /**
     * Get critical incidents
     */
    public function criticalIncidents(): JsonResponse
    {
        $user = $this->getUser();
        $query = \App\Models\Incident::with(['department', 'assignedTo', 'category'])
            ->whereIn('severity', ['critical', 'high'])
            ->whereIn('status', ['open', 'acknowledged', 'in_progress', 'escalated']);

        if (!$this->isAdmin()) {
            $query->where('department_id', $user->department_id);
        }

        $criticalIncidents = $query->orderBy('created_at', 'desc')->limit(10)->get();

        return $this->successResponse($criticalIncidents);
    }
}