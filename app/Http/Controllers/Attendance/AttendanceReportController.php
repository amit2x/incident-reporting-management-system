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

        // $usersQuery = User::where('airport_id', $nodal->airport_id)
        //     ->active()
        //     ->when($deptId, fn ($q) => $q->where('department_id', $deptId))
        //     ->when($search, fn ($q) => $q->where('name', 'LIKE', "%{$search}%"));
            
        
        $usersQuery = User::where('airport_id', $nodal->airport_id)
            ->active()
            ->with('department') // <-- Make sure this is here
            ->when($deptId, fn($q) => $q->where('department_id', $deptId))
            ->when($search, fn($q) => $q->where('name', 'LIKE', "%{$search}%"));

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

        // return view('reports.attendance.index', compact(
        //     'rows', 'departments', 'from', 'to', 'label', 'deptId', 'search'
        // ));
        
        $deptStats = $rows->groupBy('user.department_id')->map(function($users) {
            return [
                'present' => $users->sum('present'),
                'absent' => $users->sum('absent'),
            ];
        });
        
        return view('reports.attendance.index', compact(
            'rows', 'departments', 'from', 'to', 'label', 'deptId', 'search', 'deptStats'
        ));
    }

    // public function export(Request $request, string $format)
    // {
    //     $nodal = $request->user();
    //     [$from, $to, $label] = $this->resolvePeriod($request);

    //     $deptId = $request->get('department_id');

    //     $attendances = Attendance::where('airport_id', $nodal->airport_id)
    //         ->betweenDates($from, $to)
    //         ->with(['user.department', 'markedBy', 'modifiedBy'])
    //         ->when($deptId, fn ($q) => $q->whereHas('user', fn ($u) => $u->where('department_id', $deptId)))
    //         ->orderBy('attendance_date')
    //         ->orderBy('user_id')
    //         ->get();

    //     $filename = "Attendance_{$from}_{$to}";

    //     if ($format === 'csv') {
    //         return $this->exportCsv($attendances, $filename);
    //     }

    //     // For Excel/PDF you can reuse your existing Maatwebsite / DomPDF setup:
    //     return $this->exportExcelOrPdf($attendances, $filename, $format, $label, $nodal);
    // }
    
    public function export(Request $request, string $format)
    {
        $nodal = $request->user();
        [$from, $to, $label] = $this->resolvePeriod($request);
    
        $deptId = $request->get('department_id');
        $search = $request->get('search');
    
        // Fetch users
        $usersQuery = User::where('airport_id', $nodal->airport_id)
            ->active()
            ->with('department')
            ->when($deptId, fn($q) => $q->where('department_id', $deptId))
            ->when($search, fn($q) => $q->where('name', 'LIKE', "%{$search}%"));
    
        $users = $usersQuery->orderBy('name')->get();
    
        // Aggregate stats
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
    
        // Map rows
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
    
        $filename = "Attendance_Report_{$from}_to_{$to}";
    
        // ==========================================
        // EXCEL EXPORT
        // ==========================================
        if ($format === 'excel') {
            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\AttendanceReportExport($rows, $label), 
                $filename . '.xlsx'
            );
        }
    
        // ==========================================
        // PDF EXPORT
        // ==========================================
        if ($format === 'pdf') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.attendance.exports.pdf', [
                'rows' => $rows,
                'label' => $label,
                'from' => $from,
                'to' => $to
            ])->setPaper('a4', 'landscape');
    
            return $pdf->download($filename . '.pdf');
        }
    
        // ==========================================
        // CSV EXPORT (Fallback)
        // ==========================================
        if ($format === 'csv') {
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
            ];
    
            return response()->stream(function () use ($rows) {
                $f = fopen('php://output', 'w');
                fputcsv($f, ['Department', 'Employee Name', 'Employee ID', 'Total', 'Present', 'Absent', 'Late', 'Half Day', 'Leave', 'Self Marked', 'Nodal Marked']);
                
                foreach ($rows as $r) {
                    $deptName = $r['user']->department ? $r['user']->department->name : 'No Department Assigned';
                    fputcsv($f, [
                        $deptName,
                        $r['user']->name,
                        $r['user']->employee_id ?? '—',
                        $r['total'],
                        $r['present'],
                        $r['absent'],
                        $r['late'],
                        $r['half_day'],
                        $r['on_leave'],
                        $r['self_marked'],
                        $r['nodal_marked'],
                    ]);
                }
                fclose($f);
            }, 200, $headers);
        }
    
        abort(400, 'Unsupported format');
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
    
    public function getDetails(Request $request)
    {
        $nodal = $request->user();
        [$from, $to, $label] = $this->resolvePeriod($request);

        $type = $request->get('type'); // 'user' or 'department'
        $id = $request->get('id');

        $query = Attendance::where('airport_id', $nodal->airport_id)
            ->betweenDates($from, $to)
            ->with(['user.department', 'markedBy']);

        if ($type === 'user') {
            $query->where('user_id', $id);
            $title = \App\Models\User::find($id)?->name . "'s Attendance Details";
        } elseif ($type === 'department') {
            $query->whereHas('user', function($q) use ($id) {
                if ($id === 'unassigned') {
                    $q->whereNull('department_id');
                } else {
                    $q->where('department_id', $id);
                }
            });
            $deptName = $id === 'unassigned' ? 'Unassigned/No Department' : \App\Models\Department::find($id)?->name;
            $title = $deptName . " Department Attendance Details";
        } else {
            return response()->json(['error' => 'Invalid request'], 400);
        }

        $records = $query->orderBy('attendance_date', 'desc')->get();

        return response()->json([
            'title' => $title,
            'period' => $label . ' (' . \Carbon\Carbon::parse($from)->format('d M Y') . ' – ' . \Carbon\Carbon::parse($to)->format('d M Y') . ')',
            'records' => $records->map(function($att) use ($type) {
                return [
                    'date' => $att->attendance_date->format('d M Y (l)'),
                    'employee' => $type === 'department' ? $att->user->name : null,
                    'status' => ucfirst(str_replace('_', ' ', $att->status)),
                    'status_badge' => $att->status_badge,
                    'marked_by' => $att->marked_by_label . ($att->markedBy && $att->marked_by !== $att->user_id ? ' (' . $att->markedBy->name . ')' : ''),
                    'time' => $att->marked_at->format('h:i A'),
                    'remarks' => $att->remarks ?? '—',
                ];
            })
        ]);
    }
}
