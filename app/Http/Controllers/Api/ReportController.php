<?php
// app/Http/Controllers/Api/ReportController.php

namespace App\Http\Controllers\Api;

use App\Exports\IncidentReportExport;
use App\Models\Incident;
use App\Models\Department;
use App\Models\IncidentCategory;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends BaseApiController
{
    /**
     * KPI Report
     */
    public function kpiReport(Request $request): JsonResponse
    {
        $filters = $this->getDateFilter($request);
        $departmentId = $request->get('department_id');

        $query = Incident::query();
        $this->applyFilters($query, $filters, $departmentId);

        $kpiData = [
            'total_incidents' => (clone $query)->count(),
            'open_incidents' => (clone $query)->whereIn('status', ['open', 'acknowledged', 'in_progress'])->count(),
            'resolved_incidents' => (clone $query)->whereIn('status', ['resolved', 'closed'])->count(),
            'escalated_incidents' => (clone $query)->where('status', 'escalated')->count(),
            'sla_breaches' => (clone $query)->where('sla_breach_count', '>', 0)->count(),
            'avg_response_time' => round((clone $query)->whereNotNull('acknowledged_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, acknowledged_at)) as avg_time')
                ->value('avg_time') ?? 0, 1),
            'avg_resolution_time' => round((clone $query)->whereNotNull('resolved_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_time')
                ->value('avg_time') ?? 0, 1),
            'sla_compliance' => $this->calculateSlaCompliance($query),
        ];

        // Severity distribution
        $kpiData['severity_distribution'] = (clone $query)
            ->selectRaw('severity, COUNT(*) as count')
            ->groupBy('severity')
            ->pluck('count', 'severity')
            ->toArray();

        // Status distribution
        $kpiData['status_distribution'] = (clone $query)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Daily trends
        $kpiData['daily_trends'] = (clone $query)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        return $this->successResponse($kpiData);
    }

    /**
     * Department Report
     */
    public function departmentReport(Request $request): JsonResponse
    {
        $filters = $this->getDateFilter($request);

        $departments = Department::active()->get()->map(function ($department) use ($filters) {
            $query = Incident::where('department_id', $department->id);
            $this->applyFilters($query, $filters, $department->id);

            $total = (clone $query)->count();
            $resolved = (clone $query)->whereIn('status', ['resolved', 'closed'])->count();

            return [
                'id' => $department->id,
                'name' => $department->name,
                'code' => $department->code,
                'color' => $department->color,
                'total_incidents' => $total,
                'active_incidents' => (clone $query)->whereIn('status', ['open', 'acknowledged', 'in_progress', 'escalated'])->count(),
                'resolved_incidents' => $resolved,
                'escalated_incidents' => (clone $query)->where('status', 'escalated')->count(),
                'sla_breaches' => (clone $query)->where('sla_breach_count', '>', 0)->count(),
                'performance_rate' => $total > 0 ? round(($resolved / $total) * 100, 1) : 0,
                'avg_response_time' => round((clone $query)->whereNotNull('acknowledged_at')
                    ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, acknowledged_at)) as avg_time')
                    ->value('avg_time') ?? 0, 1),
            ];
        });

        return $this->successResponse($departments);
    }

    /**
     * SLA Compliance Report
     */
    public function slaReport(Request $request): JsonResponse
    {
        $filters = $this->getDateFilter($request);

        // Overall SLA compliance
        $query = Incident::query();
        $this->applyFilters($query, $filters, $request->get('department_id'));

        $total = (clone $query)->count();
        $breached = (clone $query)->where('sla_breach_count', '>', 0)->count();
        $overallCompliance = $total > 0 ? round((($total - $breached) / $total) * 100, 2) : 100;

        // Department-wise SLA
        $departmentSla = Department::active()->get()->map(function ($department) use ($filters) {
            $deptQuery = Incident::where('department_id', $department->id);
            $this->applyFilters($deptQuery, $filters, $department->id);

            $deptTotal = (clone $deptQuery)->count();
            $deptBreached = (clone $deptQuery)->where('sla_breach_count', '>', 0)->count();

            return [
                'department' => $department->name,
                'color' => $department->color,
                'total' => $deptTotal,
                'breached' => $deptBreached,
                'compliance' => $deptTotal > 0 ? round((($deptTotal - $deptBreached) / $deptTotal) * 100, 2) : 100,
            ];
        });

        // Recent SLA breaches
        $recentBreaches = Incident::where('sla_breach_count', '>', 0)
            ->when($request->get('department_id'), fn($q) => $q->where('department_id', $request->department_id))
            ->with(['department', 'category'])
            ->orderBy('updated_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($incident) {
                return [
                    'incident_id' => $incident->incident_id,
                    'title' => $incident->title,
                    'department' => $incident->department->name,
                    'category' => $incident->category->name,
                    'breach_count' => $incident->sla_breach_count,
                    'sla_due_at' => $incident->sla_due_at?->format('Y-m-d H:i'),
                    'status' => $incident->status,
                ];
            });

        return $this->successResponse([
            'overall_compliance' => $overallCompliance,
            'total_incidents' => $total,
            'total_breached' => $breached,
            'department_compliance' => $departmentSla,
            'recent_breaches' => $recentBreaches,
        ]);
    }

    /**
     * Export report
     */
    public function exportReport(Request $request, string $format): mixed
    {
        $filters = $this->getDateFilter($request);
        $reportType = $request->get('type', 'general');

        $data = $this->getReportData($reportType, $filters, $request->get('department_id'));

        return match ($format) {
            'excel' => Excel::download(
                new IncidentReportExport($data, $reportType),
                "incident_{$reportType}_report_" . now()->format('Y-m-d_His') . '.xlsx'
            ),
            'pdf' => $this->exportPdf($data, $reportType),
            'csv' => $this->exportCsv($data),
            default => $this->errorResponse('Unsupported export format', 400),
        };
    }

    /**
     * Get date filter from request
     */
    private function getDateFilter(Request $request): array
    {
        $period = $request->get('period', 'last30days');

        return match ($period) {
            'today' => ['date_from' => now()->startOfDay(), 'date_to' => now()->endOfDay()],
            'yesterday' => ['date_from' => now()->subDay()->startOfDay(), 'date_to' => now()->subDay()->endOfDay()],
            'last7days' => ['date_from' => now()->subDays(7), 'date_to' => now()],
            'last30days' => ['date_from' => now()->subDays(30), 'date_to' => now()],
            'thisMonth' => ['date_from' => now()->startOfMonth(), 'date_to' => now()->endOfMonth()],
            'lastMonth' => ['date_from' => now()->subMonth()->startOfMonth(), 'date_to' => now()->subMonth()->endOfMonth()],
            'custom' => [
                'date_from' => $request->get('date_from') ? now()->parse($request->date_from) : now()->subDays(30),
                'date_to' => $request->get('date_to') ? now()->parse($request->date_to)->endOfDay() : now(),
            ],
            default => ['date_from' => now()->subDays(30), 'date_to' => now()],
        };
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($query, array $filters, ?int $departmentId): void
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
     * Calculate SLA compliance
     */
    private function calculateSlaCompliance($query): float
    {
        $total = (clone $query)->count();
        if ($total === 0) return 100.0;

        $breached = (clone $query)->where('sla_breach_count', '>', 0)->count();
        return round((($total - $breached) / $total) * 100, 2);
    }

    /**
     * Get report data
     */
    private function getReportData(string $type, array $filters, ?int $departmentId): array
    {
        $query = Incident::query();
        $this->applyFilters($query, $filters, $departmentId);

        return [
            'type' => $type,
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'generated_by' => $this->getUser()->name,
            'total_incidents' => (clone $query)->count(),
            'open_incidents' => (clone $query)->whereIn('status', ['open', 'acknowledged', 'in_progress'])->count(),
            'resolved_incidents' => (clone $query)->whereIn('status', ['resolved', 'closed'])->count(),
            'escalated_incidents' => (clone $query)->where('status', 'escalated')->count(),
        ];
    }

    /**
     * Export as PDF
     */
    private function exportPdf(array $data, string $type)
    {
        $pdf = Pdf::loadView("reports.exports.{$type}_pdf", [
            'data' => $data,
            'reportDate' => now()->format('d M Y, H:i'),
            'generatedBy' => $this->getUser()->name,
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
            fputcsv($file, ['Metric', 'Value']);

            foreach ($data as $key => $value) {
                if (!is_array($value)) {
                    fputcsv($file, [ucwords(str_replace('_', ' ', $key)), $value]);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}