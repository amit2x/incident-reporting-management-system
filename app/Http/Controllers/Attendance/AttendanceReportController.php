<?php

// app/Http/Controllers/Attendance/AttendanceReportController.php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;

class AttendanceReportController extends Controller
{
    public function index(Request $request)
    {
        $nodal = $request->user();
        [$from, $to, $label] = $this->resolvePeriod($request);

        $deptId = $request->get('department_id');
        $search = $request->get('search');

        $usersQuery = User::where('airport_id', $nodal->airport_id)
            ->active()
            ->when($deptId, fn ($q) => $q->where('department_id', $deptId))
            ->when($search, fn ($q) => $q->where('name', 'LIKE', "%{$search}%"));

        $users = $usersQuery->with('department')->orderBy('name')->get();

        // Aggregate per-user stats
        $stats = Attendance::where('airport_id', $nodal->airport_id)
            ->betweenDates($from, $to)
            ->selectRaw('user_id,
                SUM(status = "present") AS present,
                SUM(status = "absent") AS absent,
                SUM(status = "late") AS late,
                SUM(status = "half_day") AS half_day,
                SUM(status = "on_leave") AS on_leave,
                SUM(marked_by_role = "self") AS self_marked,
                SUM(marked_by_role = "nodal_officer") AS nodal_marked,
                COUNT(*) AS total'
            )
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $rows = $users->map(function ($u) use ($stats) {
            $s = $stats->get($u->id);

            return [
                'user' => $u,
                'total' => $s->total ?? 0,
                'present' => $s->present ?? 0,
                'absent' => $s->absent ?? 0,
                'late' => $s->late ?? 0,
                'half_day' => $s->half_day ?? 0,
                'on_leave' => $s->on_leave ?? 0,
                'self_marked' => $s->self_marked ?? 0,
                'nodal_marked' => $s->nodal_marked ?? 0,
            ];
        });

        $departments = Department::where('airport_id', $nodal->airport_id)
            ->active()->ordered()->get();

        return view('reports.attendance.index', compact(
            'rows', 'departments', 'from', 'to', 'label', 'deptId', 'search'
        ));
    }

    public function export(Request $request, string $format)
    {
        $nodal = $request->user();
        [$from, $to, $label] = $this->resolvePeriod($request);

        $deptId = $request->get('department_id');

        $attendances = Attendance::where('airport_id', $nodal->airport_id)
            ->betweenDates($from, $to)
            ->with(['user.department', 'markedBy', 'modifiedBy'])
            ->when($deptId, fn ($q) => $q->whereHas('user', fn ($u) => $u->where('department_id', $deptId)))
            ->orderBy('attendance_date')
            ->orderBy('user_id')
            ->get();

        $filename = "Attendance_{$from}_{$to}";

        if ($format === 'csv') {
            return $this->exportCsv($attendances, $filename);
        }

        // For Excel/PDF you can reuse your existing Maatwebsite / DomPDF setup:
        return $this->exportExcelOrPdf($attendances, $filename, $format, $label, $nodal);
    }

    // ─────────── Helpers ───────────

    private function resolvePeriod(Request $request): array
    {
        $type = $request->get('period', 'month');

        return match ($type) {
            'month' => [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString(), 'This Month'],
            'quarter' => [now()->startOfQuarter()->toDateString(), now()->endOfQuarter()->toDateString(), 'This Quarter'],
            'year' => [now()->startOfYear()->toDateString(), now()->endOfYear()->toDateString(), 'This Year'],
            'custom' => [
                $request->get('date_from', now()->startOfMonth()->toDateString()),
                $request->get('date_to', now()->toDateString()),
                'Custom Range',
            ],
            default => [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString(), 'This Month'],
        };
    }

    private function exportCsv($attendances, string $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
        ];

        return response()->stream(function () use ($attendances) {
            $f = fopen('php://output', 'w');
            fputcsv($f, ['Date', 'Employee', 'Employee ID', 'Department', 'Status',
                'Marked By', 'Marked By Role', 'Marked At', 'Modified', 'Backfilled', 'Remarks']);
            foreach ($attendances as $a) {
                fputcsv($f, [
                    $a->attendance_date->format('Y-m-d'),
                    $a->user?->name,
                    $a->user?->employee_id,
                    $a->user?->department?->name,
                    $a->status,
                    $a->markedBy?->name,
                    $a->marked_by_role,
                    $a->marked_at?->format('Y-m-d H:i'),
                    $a->is_modified ? 'Yes' : 'No',
                    $a->is_backfilled ? 'Yes' : 'No',
                    $a->remarks,
                ]);
            }
            fclose($f);
        }, 200, $headers);
    }

    private function exportExcelOrPdf($attendances, string $filename, string $format, string $label, $nodal)
    {
        // Reuse your existing Maatwebsite / DomPDF approach:
        // return Excel::download(new AttendanceExport($attendances, $label), $filename . '.xlsx');
        // return Pdf::loadView('reports.attendance.exports.pdf', compact('attendances', 'label'))->download($filename . '.pdf');
        abort(501, 'Excel/PDF export: wire into your existing export class');
    }
}
