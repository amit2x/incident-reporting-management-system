<?php

namespace App\Http\Controllers;

use App\Models\IncidentCategory;
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
    public function index()
    {
        // Eager load parent, children, and count incidents
        $categories = IncidentCategory::with(['parent', 'children'])
            ->withCount('incidents')
            ->ordered()
            ->get();

        $parentCategories = IncidentCategory::active()->root()->get();

        return view('admin.categories.index', compact('categories', 'parentCategories'));
    }

    /**
     * Show the form for creating a new category.
     */
    public function create()
    {
        $parentCategories = IncidentCategory::active()->root()->get();
        return view('admin.categories.form', compact('parentCategories'));
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
        $category->load(['parent', 'children', 'incidents' => function ($query) {
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

        return view('admin.categories.form', compact('category', 'parentCategories'));
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
