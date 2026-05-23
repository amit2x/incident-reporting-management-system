<?php
// app/Http/Controllers/Api/DepartmentController.php

namespace App\Http\Controllers\Api;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DepartmentController extends BaseApiController
{
    /**
     * List all departments
     */
    public function index(): JsonResponse
    {
        $departments = Department::withCount(['users', 'incidents'])
            ->ordered()
            ->get();

        return $this->successResponse($departments);
    }

    /**
     * Create department
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20|unique:departments',
            'description' => 'nullable|string',
            'color' => 'required|string|size:7|starts_with:#',
            'icon' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'phone' => 'nullable|string|max:20',
            'location' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $department = Department::create($request->all());

        return $this->successResponse($department, 'Department created successfully', 201);
    }

    /**
     * Show department
     */
    public function show(Department $department): JsonResponse
    {
        $department->load(['users', 'incidents' => function ($query) {
            $query->latest()->limit(10);
        }]);
        $department->loadCount(['users', 'incidents']);

        return $this->successResponse($department);
    }

    /**
     * Update department
     */
    public function update(Request $request, Department $department): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:100',
            'code' => 'sometimes|string|max:20|unique:departments,code,' . $department->id,
            'description' => 'nullable|string',
            'color' => 'sometimes|string|size:7|starts_with:#',
            'icon' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'phone' => 'nullable|string|max:20',
            'location' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $department->update($request->all());

        return $this->successResponse($department->fresh(), 'Department updated successfully');
    }

    /**
     * Delete department
     */
    public function destroy(Department $department): JsonResponse
    {
        if ($department->users()->count() > 0) {
            return $this->errorResponse('Cannot delete department with active users', 400);
        }

        $department->delete();

        return $this->successResponse(null, 'Department deleted successfully');
    }
}