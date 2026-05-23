<?php

namespace App\Repositories;

use App\Models\Incident;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class IncidentRepository extends BaseRepository
{
    public function __construct(Incident $incident)
    {
        parent::__construct($incident);
    }

    public function getFeedIncidents(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with([
            'reporter',
            'assignedTo',
            'department',
            'category',
            'media',
        ])->withCount(['comments', 'escalations']);

        // Apply filters
        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['severity'])) {
            $query->whereIn('severity', (array) $filters['severity']);
        }

        if (!empty($filters['priority'])) {
            $query->whereIn('priority', (array) $filters['priority']);
        }

        if (!empty($filters['status'])) {
            $query->whereIn('status', (array) $filters['status']);
        }

        if (!empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('incident_id', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        // Order by latest first for feed
        $query->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    public function getIncidentDetails(int $id): Incident
    {
        return $this->model->with([
            'reporter',
            'assignedTo',
            'department',
            'category',
            'escalatedTo',
            'media',
            'comments' => function ($query) {
                $query->with(['user', 'replies.user'])->latest();
            },
            'logs' => function ($query) {
                $query->with('user')->latest()->limit(50);
            },
            'escalations' => function ($query) {
                $query->with(['escalatedBy', 'escalatedTo'])->latest();
            },
            'assignments' => function ($query) {
                $query->with(['assignedBy', 'assignedTo'])->latest();
            },
        ])->findOrFail($id);
    }

    public function getDepartmentIncidents(int $departmentId, array $filters = []): LengthAwarePaginator
    {
        $filters['department_id'] = $departmentId;
        return $this->getFeedIncidents($filters);
    }

    public function getAssignedIncidents(int $userId, array $filters = []): LengthAwarePaginator
    {
        $filters['assigned_to'] = $userId;
        return $this->getFeedIncidents($filters);
    }

    public function getCriticalIncidents(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->with(['department', 'assignedTo', 'category'])
            ->whereIn('severity', ['critical', 'high'])
            ->whereIn('status', ['open', 'acknowledged', 'in_progress', 'escalated'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    public function getOverdueIncidents(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->with(['department', 'assignedTo'])
            ->where('sla_due_at', '<', now())
            ->whereIn('status', ['open', 'acknowledged', 'in_progress', 'escalated'])
            ->orderBy('sla_due_at', 'asc')
            ->get();
    }

    /**
     * Get incident statistics
     */
    public function getStats(array $filters = []): array
    {
        $query = $this->model->newQuery();

        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $total = (clone $query)->count();
        $open = (clone $query)->whereIn('status', ['open', 'acknowledged', 'in_progress'])->count();
        $escalated = (clone $query)->where('status', 'escalated')->count();
        $resolved = (clone $query)->where('status', 'resolved')->count();
        $closed = (clone $query)->where('status', 'closed')->count();
        $overdue = (clone $query)->where('sla_due_at', '<', now())
            ->whereIn('status', ['open', 'acknowledged', 'in_progress', 'escalated'])->count();

        $avgResponseTime = (clone $query)
            ->whereNotNull('acknowledged_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, acknowledged_at)) as avg_time')
            ->value('avg_time') ?? 0;

        $avgResolutionTime = (clone $query)
            ->whereNotNull('resolved_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_time')
            ->value('avg_time') ?? 0;

        return [
            'total' => $total,
            'open' => $open,
            'escalated' => $escalated,
            'resolved' => $resolved,
            'closed' => $closed,
            'overdue' => $overdue,
            'avg_response_time' => round($avgResponseTime, 1),
            'avg_resolution_time' => round($avgResolutionTime, 1),
        ];
    }

    private function calculateAverageResponseTime(array $filters): float
    {
        $query = $this->model->whereNotNull('acknowledged_at');

        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        return $query->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, acknowledged_at)) as avg_time')
            ->value('avg_time') ?? 0;
    }

    private function calculateAverageResolutionTime(array $filters): float
    {
        $query = $this->model->whereNotNull('resolved_at');

        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        return $query->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_time')
            ->value('avg_time') ?? 0;
    }
}
