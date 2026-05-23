<?php

namespace App\Http\Controllers;

use App\Models\EscalationMatrix;
use App\Models\Department;
use App\Models\IncidentCategory;
use App\Models\User;
use Illuminate\Http\Request;

class EscalationMatrixController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin|super-admin');
    }

    /**
     * Display a listing of escalation matrix entries.
     */
    public function index(Request $request)
    {
        $query = EscalationMatrix::with([
            'department',
            'category',
            'escalateToUser',
            'escalateToDepartment'
        ]);

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $matrices = $query->orderBy('department_id')
            ->orderBy('category_id')
            ->orderBy('level')
            ->get();

        $groupedMatrices = $matrices->groupBy('department_id');

        $departments = Department::active()->ordered()->get();
        $categories = IncidentCategory::active()->get();

        return view('admin.escalation-matrix.index', compact(
            'matrices',
            'groupedMatrices',
            'departments',
            'categories'
        ));
    }

    /**
     * Show the form for creating a new escalation matrix entry.
     */
    public function create(Request $request)
    {
        $departments = Department::active()->ordered()->get();
        $categories = IncidentCategory::active()->get();

        // Get users from selected department (if any) or all users
        $selectedDeptId = old('department_id', $request->get('department_id'));
        if ($selectedDeptId) {
            $users = User::active()->where('department_id', $selectedDeptId)->get();
        } else {
            $users = User::active()->with('department')->get();
        }

        $allDepartments = Department::active()->ordered()->get();

        return view('admin.escalation-matrix.form', compact(
            'departments',
            'categories',
            'users',
            'allDepartments'
        ));
    }

    /**
     * Store a newly created escalation matrix entry.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'category_id' => 'nullable|exists:incident_categories,id',
            'level' => 'required|integer|min:1|max:4',
            'timeout_minutes' => 'required|integer|min:5',
            'escalate_to_user_id' => 'required|exists:users,id',
            'escalate_to_department_id' => 'required|exists:departments,id',
            'notify_via_email' => 'nullable|boolean',
            'notify_via_push' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $exists = EscalationMatrix::where('department_id', $request->department_id)
            ->where('category_id', $request->category_id)
            ->where('level', $request->level)
            ->exists();

        if ($exists) {
            return back()
                ->with('error', 'This escalation level already exists for the selected department and category.')
                ->withInput();
        }

        $validated['notify_via_email'] = $request->has('notify_via_email');
        $validated['notify_via_push'] = $request->has('notify_via_push');
        $validated['is_active'] = $request->has('is_active') ? true : true;

        EscalationMatrix::create($validated);

        return redirect()->route('admin.escalation-matrix.index')
            ->with('success', 'Escalation matrix entry created successfully.');
    }

    /**
     * Display the specified escalation matrix entry.
     */
    public function show(EscalationMatrix $escalationMatrix)
    {
        $escalationMatrix->load([
            'department',
            'category',
            'escalateToUser',
            'escalateToDepartment'
        ]);

        // Get all entries for the same department
        $relatedEntries = EscalationMatrix::with(['category', 'escalateToUser', 'escalateToDepartment'])
            ->where('department_id', $escalationMatrix->department_id)
            ->where('id', '!=', $escalationMatrix->id)
            ->orderBy('level')
            ->get();

        return view('admin.escalation-matrix.show', compact('escalationMatrix', 'relatedEntries'));
    }

    /**
     * Show the form for editing the specified escalation matrix entry.
     */
    public function edit(EscalationMatrix $escalationMatrix)
    {
        $departments = Department::active()->ordered()->get();
        $categories = IncidentCategory::active()->get();

        // Get users from the entry's department
        $users = User::active()
            ->where('department_id', $escalationMatrix->department_id)
            ->get();

        $allDepartments = Department::active()->ordered()->get();

        return view('admin.escalation-matrix.form', compact(
            'escalationMatrix',
            'departments',
            'categories',
            'users',
            'allDepartments'
        ));
    }

    /**
     * Update the specified escalation matrix entry.
     */
    public function update(Request $request, EscalationMatrix $escalationMatrix)
    {
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'category_id' => 'nullable|exists:incident_categories,id',
            'level' => 'required|integer|min:1|max:4',
            'timeout_minutes' => 'required|integer|min:5',
            'escalate_to_user_id' => 'required|exists:users,id',
            'escalate_to_department_id' => 'required|exists:departments,id',
            'notify_via_email' => 'nullable|boolean',
            'notify_via_push' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['notify_via_email'] = $request->has('notify_via_email');
        $validated['notify_via_push'] = $request->has('notify_via_push');
        $validated['is_active'] = $request->has('is_active');

        $escalationMatrix->update($validated);

        return redirect()->route('admin.escalation-matrix.index')
            ->with('success', 'Escalation matrix entry updated successfully.');
    }

    /**
     * Remove the specified escalation matrix entry.
     */
    public function destroy(EscalationMatrix $escalationMatrix)
    {
        $escalationMatrix->delete();

        return redirect()->route('admin.escalation-matrix.index')
            ->with('success', 'Escalation matrix entry deleted successfully.');
    }

    /**
     * Get users by department (AJAX)
     */
    public function getUsersByDepartment(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id'
        ]);

        $users = User::active()
            ->where('department_id', $request->department_id)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role_name' => $user->role_name,
                    'avatar_url' => $user->avatar_url,
                ];
            });

        return response()->json(['success' => true, 'data' => $users]);
    }
}
