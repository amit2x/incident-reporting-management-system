<?php

// app/Http/Controllers/Attendance/NodalAttendanceController.php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\User;
use App\Services\Attendance\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class NodalAttendanceController extends Controller
{
    public function __construct(private AttendanceService $service) {}

    /**
     * Main marking dashboard.
     */
    public function index(Request $request)
    {
        $nodal = $request->user();
        $date = $request->get('date', now($nodal->airport->timezone)->toDateString());
        $deptId = $request->get('department_id');
        $search = $request->get('search');

        // Users of this airport
        $usersQuery = User::where('airport_id', $nodal->airport_id)
            ->active()
            ->with('department')
            ->when($deptId, fn ($q) => $q->where('department_id', $deptId))
            ->when($search, fn ($q) => $q->where(function ($w) use ($search) {
                $w->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('employee_id', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
            }));

        // Load attendance for this date keyed by user_id
        $attendances = Attendance::where('airport_id', $nodal->airport_id)
            ->whereDate('attendance_date', $date)
            ->get()
            ->keyBy('user_id');

        $users = $usersQuery->orderBy('name')->paginate(50)->withQueryString();

        $departments = Department::where('airport_id', $nodal->airport_id)
            ->active()->ordered()->get();

        // Load the User model instances into the paginated collection
        $users->getCollection()->transform(function ($user) use ($attendances) {
            $user->todayAttendance = $attendances->get($user->id);

            return $user;
        });

        // Stats for the day
        $stats = [
            'total' => $users->total(),
            'present' => $attendances->where('status', 'present')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'unmarked' => max(0, $users->total() - $attendances->count()),
            'self' => $attendances->where('marked_by_role', 'self')->count(),
        ];

        return view('nodal.attendance.index', compact(
            'users', 'attendances', 'departments', 'date', 'deptId', 'search', 'stats'
        ));
    }

    /**
     * Bulk mark: select department → employees → submit.
     */
    public function bulkMark(Request $request)
    {
        $data = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'date' => ['required', 'date'],
            'status' => ['required', 'in:present,absent,late,half_day,on_leave'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $results = $this->service->markByNodal(
                $request->user(),
                $data['user_ids'],
                $data['date'],
                $data['status'],
                $data['remarks'] ?? null
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        $summary = sprintf(
            'Created: %d, Updated: %d, Skipped (same status): %d',
            count($results['created']),
            count($results['updated']),
            count($results['skipped'])
        );

        return back()->with('success', "Attendance processed. {$summary}");
    }

    /**
     * Single mark (same as bulk with one user).
     */
    public function singleMark(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'date' => ['required', 'date'],
            'status' => ['required', 'in:present,absent,late,half_day,on_leave'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->service->markByNodal(
                $request->user(),
                [$data['user_id']],
                $data['date'],
                $data['status'],
                $data['remarks'] ?? null
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', 'Attendance marked.');
    }

    /**
     * Override an existing attendance.
     */
    public function override(Request $request, Attendance $attendance)
    {
        $data = $request->validate([
            'status' => ['required', 'in:present,absent,late,half_day,on_leave'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        try {
            $this->service->override(
                $request->user(),
                $attendance,
                $data['status'],
                $data['reason']
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', 'Attendance overridden successfully.');
    }

    /**
     * JSON endpoint: employees by department (for bulk UI).
     */
    public function employeesByDepartment(Request $request, Department $department)
    {
        $nodal = $request->user();

        abort_unless($department->airport_id === $nodal->airport_id, 403);

        $date = $request->get('date', now()->toDateString());

        $employees = User::where('airport_id', $nodal->airport_id)
            ->where('department_id', $department->id)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'employee_id', 'designation', 'avatar']);

        $attendances = Attendance::where('airport_id', $nodal->airport_id)
            ->whereDate('attendance_date', $date)
            ->whereIn('user_id', $employees->pluck('id'))
            ->get()
            ->keyBy('user_id');

        $employees = $employees->map(function ($u) use ($attendances) {
            $a = $attendances->get($u->id);

            return [
                'id' => $u->id,
                'name' => $u->name,
                'employee_id' => $u->employee_id,
                'designation' => $u->designation,
                'avatar' => $u->avatar,
                'status' => $a->status ?? null,
                'marked_by_role' => $a->marked_by_role ?? null,
                'marked_at' => $a?->marked_at?->format('h:i A'),
                'attendance_id' => $a->id ?? null,
            ];
        });

        return response()->json(['employees' => $employees]);
    }

    /**
     * Review / override page.
     */
    public function review(Request $request)
    {
        $nodal = $request->user();
        $date = $request->get('date', now()->toDateString());

        $attendances = Attendance::where('airport_id', $nodal->airport_id)
            ->whereDate('attendance_date', $date)
            ->with(['user', 'markedBy', 'modifiedBy'])
            ->orderByDesc('marked_at')
            ->paginate(50);

        return view('nodal.attendance.review', compact('attendances', 'date'));
    }
}
