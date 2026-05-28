<?php
// app/Http/Controllers/Api/UserController.php

namespace App\Http\Controllers\Api;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends BaseApiController
{
    /**
     * List all users
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::with(['department', 'roles']);

        if ($request->search) {
            $query->search($request->search);
        }

        if ($request->department_id) {
            $query->byDepartment($request->department_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->role) {
            $query->byRole($request->role);
        }

        $users = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginatedResponse($users);
    }

    /**
     * Create new user
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:50|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'employee_id' => 'nullable|string|max:50|unique:users',
            'department_id' => 'required|exists:departments,id',
            'designation' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive,suspended',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'employee_id' => $request->employee_id,
            'department_id' => $request->department_id,
            'designation' => $request->designation,
            'status' => $request->status,
        ]);

        $user->assignRole($request->roles);

        return $this->successResponse(
            $user->load('department', 'roles'),
            'User created successfully',
            201
        );
    }

    /**
     * Show user details
     */
    public function show(User $user): JsonResponse
    {
        $user->load(['department', 'roles', 'permissions']);

        return $this->successResponse($user);
    }

    /**
     * Update user
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'username' => 'sometimes|string|max:50|unique:users,username,' . $user->id,
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
            'phone' => 'nullable|string|max:20',
            'employee_id' => 'nullable|string|max:50|unique:users,employee_id,' . $user->id,
            'department_id' => 'sometimes|exists:departments,id',
            'designation' => 'nullable|string|max:100',
            'status' => 'sometimes|in:active,inactive,suspended',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $data = $request->except('password');

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return $this->successResponse(
            $user->fresh()->load('department', 'roles'),
            'User updated successfully'
        );
    }

    /**
     * Delete user
     */
    public function destroy(User $user): JsonResponse
    {
        if ($user->isSuperAdmin()) {
            return $this->errorResponse('Cannot delete super admin', 403);
        }

        $user->delete();

        return $this->successResponse(null, 'User deleted successfully');
    }

    /**
     * Update user status
     */
    public function updateStatus(Request $request, User $user): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive,suspended',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $user->update(['status' => $request->status]);

        // Revoke all tokens if deactivated
        if ($request->status !== 'active') {
            $user->tokens()->delete();
        }

        return $this->successResponse(null, "User status updated to {$request->status}");
    }

    /**
     * Update user roles
     */
    public function updateRoles(Request $request, User $user): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $user->syncRoles($request->roles);

        return $this->successResponse(
            $user->fresh()->load('roles'),
            'User roles updated successfully'
        );
    }

    /**
     * Get user activity
     */
    public function activity(User $user): JsonResponse
    {
        $activities = $user->activityLogs()
            ->latest()
            ->paginate(50);

        return $this->paginatedResponse($activities);
    }


    /**
     * Get users for @mention suggestions
     */
    public function mentionSuggestions()
    {
        $users = User::active()
            ->select('id', 'name', 'username', 'avatar')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'avatar_url' => $user->avatar_url,
                ];
            });

        return response()->json(['success' => true, 'data' => $users]);
    }

    /**
     * Get users by department for escalation
     */
    public function departmentUsers(Department $department)
    {
        $users = $department->users()->active()
            ->get(['id', 'name', 'username', 'designation'])
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'role_name' => $user->getFirstRoleName(),
                ];
            });

        return response()->json(['success' => true, 'data' => $users]);
    }
}
