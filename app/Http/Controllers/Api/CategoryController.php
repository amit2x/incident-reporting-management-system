<?php
// app/Http/Controllers/Api/CategoryController.php

namespace App\Http\Controllers\Api;

use App\Models\IncidentCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends BaseApiController
{
    /**
     * List all categories
     */
    public function index(Request $request): JsonResponse
    {
        $query = IncidentCategory::with('parent')
            ->withCount('incidents');

        if ($request->has('active_only')) {
            $query->active();
        }

        $categories = $query->ordered()->get();

        return $this->successResponse($categories);
    }

    /**
     * Create category
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:100|unique:incident_categories',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'color' => 'required|string|size:7|starts_with:#',
            'parent_id' => 'nullable|exists:incident_categories,id',
            'default_priority' => 'required|integer|min:1|max:4',
            'sla_minutes' => 'required|integer|min:1',
            'requires_approval' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $category = IncidentCategory::create($request->all());

        return $this->successResponse($category, 'Category created successfully', 201);
    }

    /**
     * Show category
     */
    public function show(IncidentCategory $category): JsonResponse
    {
        $category->load(['parent', 'children', 'incidents' => function ($query) {
            $query->latest()->limit(10);
        }]);
        $category->loadCount('incidents');

        return $this->successResponse($category);
    }

    /**
     * Update category
     */
    public function update(Request $request, IncidentCategory $category): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:100',
            'slug' => 'sometimes|string|max:100|unique:incident_categories,slug,' . $category->id,
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'color' => 'sometimes|string|size:7|starts_with:#',
            'parent_id' => 'nullable|exists:incident_categories,id',
            'default_priority' => 'sometimes|integer|min:1|max:4',
            'sla_minutes' => 'sometimes|integer|min:1',
            'requires_approval' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        // Prevent circular parent reference
        if ($request->parent_id && $request->parent_id == $category->id) {
            return $this->errorResponse('Category cannot be its own parent', 400);
        }

        $category->update($request->all());

        return $this->successResponse($category->fresh(), 'Category updated successfully');
    }

    /**
     * Delete category
     */
    public function destroy(IncidentCategory $category): JsonResponse
    {
        if ($category->incidents()->count() > 0) {
            return $this->errorResponse('Cannot delete category with associated incidents', 400);
        }

        // Reassign children to parent
        if ($category->children()->count() > 0) {
            $category->children()->update(['parent_id' => $category->parent_id]);
        }

        $category->delete();

        return $this->successResponse(null, 'Category deleted successfully');
    }
}