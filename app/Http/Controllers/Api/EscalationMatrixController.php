<?php
// app/Http/Controllers/Api/EscalationMatrixController.php

namespace App\Http\Controllers\Api;

use App\Models\EscalationMatrix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EscalationMatrixController extends BaseApiController
{
    /**
     * List escalation matrix entries
     */
    public function index(Request $request): JsonResponse
    {
        $query = EscalationMatrix::with([
            'department',
            'category',
            'escalateToUser',
            'escalateToDepartment'
        ]);

        if ($request->department_id) {
            $query->forDepartment($request->department_id);
        }

        if ($request->category_id) {
            $query->forCategory($request->category_id);
        }

        if ($request->has('active_only')) {
            $query->active();
        }

        $matrices = $query->orderedByLevel()->get();

        return $this->successResponse($matrices);
    }

    /**
     * Create escalation matrix entry
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'department_id' => 'required|exists:departments,id',
            'category_id' => 'nullable|exists:incident_categories,id',
            'level' => 'required|integer|min:1|max:4',
            'timeout_minutes' => 'required|integer|min:5',
            'escalate_to_user_id' => 'required|exists:users,id',
            'escalate_to_department_id' => 'required|exists:departments,id',
            'notify_via_email' => 'boolean',
            'notify_via_push' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        // Check for duplicate level
        $exists = EscalationMatrix::where('department_id', $request->department_id)
            ->where('category_id', $request->category_id)
            ->where('level', $request->level)
            ->exists();

        if ($exists) {
            return $this->errorResponse('Escalation level already exists for this department/category', 400);
        }

        $matrix = EscalationMatrix::create($request->all());

        return $this->successResponse(
            $matrix->load(['department', 'category', 'escalateToUser', 'escalateToDepartment']),
            'Escalation matrix created successfully',
            201
        );
    }

    /**
     * Show escalation matrix entry
     */
    public function show(EscalationMatrix $escalationMatrix): JsonResponse
    {
        $escalationMatrix->load([
            'department',
            'category',
            'escalateToUser',
            'escalateToDepartment'
        ]);

        return $this->successResponse($escalationMatrix);
    }

    /**
     * Update escalation matrix entry
     */
    public function update(Request $request, EscalationMatrix $escalationMatrix): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'department_id' => 'sometimes|exists:departments,id',
            'category_id' => 'nullable|exists:incident_categories,id',
            'level' => 'sometimes|integer|min:1|max:4',
            'timeout_minutes' => 'sometimes|integer|min:5',
            'escalate_to_user_id' => 'sometimes|exists:users,id',
            'escalate_to_department_id' => 'sometimes|exists:departments,id',
            'notify_via_email' => 'boolean',
            'notify_via_push' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $escalationMatrix->update($request->all());

        return $this->successResponse(
            $escalationMatrix->fresh()->load(['department', 'category', 'escalateToUser', 'escalateToDepartment']),
            'Escalation matrix updated successfully'
        );
    }

    /**
     * Delete escalation matrix entry
     */
    public function destroy(EscalationMatrix $escalationMatrix): JsonResponse
    {
        $escalationMatrix->delete();

        return $this->successResponse(null, 'Escalation matrix deleted successfully');
    }
}