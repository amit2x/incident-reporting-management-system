<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin|super-admin');
    }

    // public function index()
    // {
    //     $departments = Department::withCount(['users', 'incidents'])
    //         ->ordered()
    //         ->get();

    //     return view('admin.departments.index', compact('departments'));
    // }
    
    /**
     * Display a listing of departments with filters.
     */
    public function index(Request $request)
    {
        $query = Department::withCount(['users', 'incidents']);
    
        // ==========================================
        // APPLY FILTERS
        // ==========================================
    
        // 1. Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }
    
        // 2. Status filter
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }
    
        // 3. Has users filter
        if ($request->filled('has_users')) {
            if ($request->has_users == '1') {
                $query->having('users_count', '>', 0);
            } else {
                $query->having('users_count', '=', 0);
            }
        }
    
        // 4. Sort options
        switch ($request->get('sort')) {
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'users_desc':
                $query->orderBy('users_count', 'desc');
                break;
            case 'users_asc':
                $query->orderBy('users_count', 'asc');
                break;
            case 'incidents_desc':
                $query->orderBy('incidents_count', 'desc');
                break;
            case 'incidents_asc':
                $query->orderBy('incidents_count', 'asc');
                break;
            case 'created_desc':
                $query->orderBy('created_at', 'desc');
                break;
            case 'created_asc':
                $query->orderBy('created_at', 'asc');
                break;
            default:
                $query->ordered(); // Uses existing ordered scope
                break;
        }
    
        // ==========================================
        // GET DATA
        // ==========================================
    
        // Paginate results (12 per page)
        $departments = $query->paginate(12)->withQueryString();
    
        return view('admin.departments.index', compact('departments'));
    }

    public function create()
    {
        return view('admin.departments.form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20|unique:departments',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'phone' => 'nullable|string|max:20',
            'location' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        Department::create($validated);

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department created successfully.');
    }

    public function show(Department $department)
    {
        $department->load('users');
        return view('admin.departments.show', compact('department'));
    }

    public function edit(Department $department)
    {
        return view('admin.departments.form', compact('department'));
    }

    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20|unique:departments,code,' . $department->id,
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'phone' => 'nullable|string|max:20',
            'location' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $department->update($validated);

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department updated successfully.');
    }

    public function destroy(Department $department)
    {
        if ($department->users()->count() > 0) {
            return back()->with('error', 'Cannot delete department with active users.');
        }

        $department->delete();

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department deleted successfully.');
    }
}