<?php

namespace App\Http\Controllers;

use App\Models\IncidentCategory;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin|super-admin');
    }

    /**
     * Display a listing of categories.
     */
    // public function index()
    // {
    //     // Eager load parent, children, and count incidents
    //     $categories = IncidentCategory::with(['parent', 'children'])
    //         ->withCount('incidents')
    //         ->ordered()
    //         ->get();

    //     $parentCategories = IncidentCategory::active()->root()->get();

    //     return view('admin.categories.index', compact('categories', 'parentCategories'));
    // }
    
    /**
     * Display a listing of categories with filters.
     */
    public function index(Request $request)
    {
        $query = IncidentCategory::with(['parent', 'children', 'department'])
            ->withCount('incidents');
    
        // ==========================================
        // APPLY FILTERS
        // ==========================================
    
        // 1. Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }
    
        // 2. Status filter
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }
    
        // 3. Department filter
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
    
        // 4. Approval filter
        if ($request->filled('requires_approval') && $request->requires_approval !== '') {
            $query->where('requires_approval', (bool) $request->requires_approval);
        }
    
        // 5. Sort options
        switch ($request->get('sort')) {
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'incidents_desc':
                $query->orderBy('incidents_count', 'desc');
                break;
            case 'incidents_asc':
                $query->orderBy('incidents_count', 'asc');
                break;
            case 'sla_asc':
                $query->orderBy('sla_minutes', 'asc');
                break;
            case 'sla_desc':
                $query->orderBy('sla_minutes', 'desc');
                break;
            case 'created_desc':
                $query->orderBy('created_at', 'desc');
                break;
            case 'created_asc':
                $query->orderBy('created_at', 'asc');
                break;
            default:
                $query->orderBy('sort_order', 'asc')->orderBy('name', 'asc');
                break;
        }
    
        // ==========================================
        // GET DATA WITH PER-PAGE PAGINATION
        // ==========================================
    
        // Get per_page value (default 15)
        $perPage = $request->get('per_page', 15);
    
        // Handle 'all' option
        
        // Handle 'all' option - convert to a paginator with all items
        if ($perPage === 'all') {
            // Get all items and create a custom paginator
            $items = $query->get();
            $categories = new \Illuminate\Pagination\LengthAwarePaginator(
                $items,
                $items->count(),
                $items->count(),
                1,
                ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
            );
            $categories->appends($request->query());
        } else {
            $categories = $query->paginate((int) $perPage)->withQueryString();
        }
    
        // Get departments for filter dropdown
        $departments = Department::active()->ordered()->get();
    
        // Get parent categories for any additional use
        $parentCategories = IncidentCategory::active()->root()->get();
    
        return view('admin.categories.index', compact('categories', 'departments', 'parentCategories', 'perPage'));
    }

    /**
     * Show the form for creating a new category.
     */
    public function create()
    {
        $departments = Department::active()->ordered()->get();
        $parentCategories = IncidentCategory::active()->root()->get();
        return view('admin.categories.form', compact('parentCategories', 'departments'));
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:100|unique:incident_categories',
            'description' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:50',
            'color' => 'required|string|max:7',
            'parent_id' => 'nullable|exists:incident_categories,id',
            'department_id' => 'required|exists:departments,id',
            'default_priority' => 'required|integer|min:1|max:4',
            'sla_minutes' => 'required|integer|min:1',
            'requires_approval' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $validated['requires_approval'] = $request->has('requires_approval');
        $validated['is_active'] = $request->has('is_active') ? true : true;

        IncidentCategory::create($validated);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }

    /**
     * Display the specified category.
     */
    public function show(IncidentCategory $category)
    {
        $category->load(['parent', 'department', 'children', 'incidents' => function ($query) {
            $query->latest()->limit(20);
        }]);
        $category->loadCount('incidents');

        return view('admin.categories.show', compact('category'));
    }

    /**
     * Show the form for editing the specified category.
     */
    public function edit(IncidentCategory $category)
    {
        $parentCategories = IncidentCategory::where('id', '!=', $category->id)
            ->active()
            ->root()
            ->get();

        $departments = Department::active()->ordered()->get();
        
        return view('admin.categories.form', compact('category', 'parentCategories', 'departments'));
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, IncidentCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:100|unique:incident_categories,slug,' . $category->id,
            'description' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:50',
            'color' => 'required|string|max:7',
            'parent_id' => 'nullable|exists:incident_categories,id',
            'department_id' => 'required|exists:departments,id',
            'default_priority' => 'required|integer|min:1|max:4',
            'sla_minutes' => 'required|integer|min:1',
            'requires_approval' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        // Prevent circular parent reference
        if ($request->parent_id && $request->parent_id == $category->id) {
            return back()->with('error', 'Category cannot be its own parent.');
        }

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $validated['requires_approval'] = $request->has('requires_approval');
        $validated['is_active'] = $request->has('is_active');

        $category->update($validated);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    /**
     * Remove the specified category.
     */
    public function destroy(IncidentCategory $category)
    {
        if ($category->incidents()->count() > 0) {
            return back()->with('error', 'Cannot delete category with associated incidents.');
        }

        // Reassign children to parent or make them root
        if ($category->children()->count() > 0) {
            $category->children()->update(['parent_id' => $category->parent_id]);
        }

        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}
