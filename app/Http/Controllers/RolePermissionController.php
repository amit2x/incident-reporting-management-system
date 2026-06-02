<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // $this->middleware('permission:view-roles|view-users');
    }

    /**
     * Display a listing of roles.
     */
    public function roles(Request $request)
    {
        $query = Role::withCount(['users', 'permissions']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $allowedSorts = ['name', 'created_at', 'users_count', 'permissions_count'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        $roles = $query->paginate($request->get('per_page', 10))
            ->appends($request->query());

        $totalRoles = Role::count();
        $totalPermissions = Permission::count();
        $totalUsersWithRoles = User::whereHas('roles')->count();

        return view('admin.roles.index', compact(
            'roles',
            'totalRoles',
            'totalPermissions',
            'totalUsersWithRoles'
        ));
    }

    /**
     * Show form to create a new role.
     */
    // public function createRole()
    // {
    //     $permissions = Permission::all()
    //         ->groupBy(function ($permission) {
    //             $parts = explode('-', $permission->name);

    //             return ucfirst($parts[0]);
    //         })
    //         ->sortKeys();

    //     $existingRoles = Role::with('permissions')->get();

    //     return view('admin.roles.create', compact('permissions', 'existingRoles'));
    // }

    public function createRole()
    {
        $permissions = Permission::all()
            ->groupBy(function ($permission) {
                $parts = explode('-', $permission->name);

                return ucfirst($parts[0]);
            })
            ->sortKeys();

        $existingRoles = Role::with('permissions')->get();
        $totalPermissionsCount = Permission::count(); // Pass this to view

        return view('admin.roles.create', compact('permissions', 'existingRoles', 'totalPermissionsCount'));
    }

    /**
     * Store a newly created role.
     */
    public function storeRole(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name|regex:/^[a-zA-Z0-9-_]+$/',
            'description' => 'nullable|string|max:500',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'exists:permissions,name',
        ], [
            'name.regex' => 'Role name can only contain letters, numbers, hyphens, and underscores.',
            'permissions.required' => 'Please select at least one permission for this role.',
        ]);

        try {
            DB::beginTransaction();

            $role = Role::create([
                'name' => $validated['name'],
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($validated['permissions']);

            DB::commit();

            Log::info("Role created: {$role->name}");

            return redirect()->route('admin.roles.index')
                ->with('success', "Role '{$role->name}' created successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create role: '.$e->getMessage());

            return back()->withInput()
                ->with('error', 'Failed to create role. Please try again.');
        }
    }

    /**
     * Display the specified role.
     */
    public function showRole(Role $role)
    {
        $role->loadCount(['users', 'permissions']);
        $role->load(['permissions', 'users' => function ($query) {
            $query->select('id', 'name', 'username', 'email', 'employee_id', 'designation', 'status')
                ->orderBy('name');
        }]);

        $permissionsByGroup = $role->permissions->groupBy(function ($permission) {
            $parts = explode('-', $permission->name);

            return ucfirst($parts[0]);
        });

        return view('admin.roles.show', compact('role', 'permissionsByGroup'));
    }

    /**
     * Show form to edit a role.
     */
    // public function editRole(Role $role)
    // {
    //     if ($role->name === 'super-admin' && ! auth()->user()->hasRole('super-admin')) {
    //         abort(403, 'You cannot edit the super-admin role.');
    //     }

    //     $permissions = Permission::all()
    //         ->groupBy(function ($permission) {
    //             $parts = explode('-', $permission->name);

    //             return ucfirst($parts[0]);
    //         })
    //         ->sortKeys();

    //     $rolePermissions = $role->permissions->pluck('name')->toArray();
    //     $usersCount = $role->users()->count();
    //     $permissionsCount = $role->permissions()->count();

    //     return view('admin.roles.edit', compact(
    //         'role',
    //         'permissions',
    //         'rolePermissions',
    //         'usersCount',
    //         'permissionsCount'
    //     ));
    // }
    public function editRole(Role $role)
    {
        if ($role->name === 'super-admin' && ! auth()->user()->hasRole('super-admin')) {
            abort(403, 'You cannot edit the super-admin role.');
        }

        $permissions = Permission::all()
            ->groupBy(function ($permission) {
                $parts = explode('-', $permission->name);

                return ucfirst($parts[0]);
            })
            ->sortKeys();

        $rolePermissions = $role->permissions->pluck('name')->toArray();
        $usersCount = $role->users()->count();
        $permissionsCount = $role->permissions()->count();
        $totalPermissionsCount = Permission::count(); // Pass this to view

        return view('admin.roles.edit', compact(
            'role',
            'permissions',
            'rolePermissions',
            'usersCount',
            'permissionsCount',
            'totalPermissionsCount'
        ));
    }

    /**
     * Update the specified role.
     */
    public function updateRole(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,'.$role->id.'|regex:/^[a-zA-Z0-9-_]+$/',
            'description' => 'nullable|string|max:500',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'exists:permissions,name',
        ]);

        try {
            DB::beginTransaction();

            $role->update(['name' => $validated['name']]);
            $role->syncPermissions($validated['permissions']);

            DB::commit();

            return redirect()->route('admin.roles.index')
                ->with('success', "Role '{$role->name}' updated successfully.");
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()
                ->with('error', 'Failed to update role.');
        }
    }

    /**
     * Remove the specified role.
     */
    public function destroyRole(Role $role)
    {
        if ($role->name === 'super-admin') {
            return back()->with('error', 'The super-admin role cannot be deleted.');
        }

        if ($role->users()->count() > 0) {
            return back()->with('error', 'Cannot delete role with assigned users.');
        }

        try {
            $role->delete();

            return redirect()->route('admin.roles.index')
                ->with('success', 'Role deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete role.');
        }
    }

    /**
     * Display a listing of permissions.
     */
    // public function permissions(Request $request)
    // {
    //     $query = Permission::withCount('roles');

    //     if ($request->filled('search')) {
    //         $query->where('name', 'like', "%{$request->search}%");
    //     }

    //     $permissions = $query->orderBy('name')
    //         ->paginate($request->get('per_page', 20))
    //         ->appends($request->query());

    //     $totalPermissions = Permission::count();
    //     $unusedPermissions = Permission::doesntHave('roles')->count();

    //     return view('admin.permissions.index', compact(
    //         'permissions',
    //         'totalPermissions',
    //         'unusedPermissions'
    //     ));
    // }

    /**
     * Display a listing of permissions.
     */
    public function permissions(Request $request)
    {
        $query = Permission::withCount('roles');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        // Sort functionality
        $sortField = $request->get('sort', 'name');
        $sortDirection = $request->get('direction', 'asc');

        $query->orderBy($sortField, $sortDirection);

        $permissions = $query->paginate($request->get('per_page', 20))
            ->appends($request->query());

        // Get permission groups for display
        $permissionGroups = Permission::select(DB::raw("SUBSTRING_INDEX(name, '-', 1) as prefix"))
            ->distinct()
            ->pluck('prefix')
            ->map(function ($prefix) {
                return ucfirst($prefix);
            });

        // Statistics
        $totalPermissions = Permission::count();
        $unusedPermissions = Permission::doesntHave('roles')->count();

        return view('admin.permissions.index', compact(
            'permissions',
            'permissionGroups',
            'totalPermissions',
            'unusedPermissions'
        ));
    }

    /**
     * Show form to create a new permission.
     */
    public function createPermission()
    {
        $existingGroups = Permission::select(DB::raw("SUBSTRING_INDEX(name, '-', 1) as prefix"))
            ->distinct()
            ->pluck('prefix');

        return view('admin.permissions.create', compact('existingGroups'));
    }

    /**
     * Store a newly created permission.
     */
    public function storePermission(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name|regex:/^[a-zA-Z0-9-]+$/',
        ], [
            'name.regex' => 'Permission name can only contain letters, numbers, and hyphens.',
        ]);

        try {
            $permission = Permission::create([
                'name' => $validated['name'],
                'guard_name' => 'web',
            ]);

            Log::info("Permission created: {$permission->name}");

            return redirect()->route('admin.permissions.index')
                ->with('success', "Permission '{$permission->name}' created successfully.");
        } catch (\Exception $e) {
            Log::error('Failed to create permission: '.$e->getMessage());

            return back()->withInput()
                ->with('error', 'Failed to create permission. Please try again.');
        }
    }

    /**
     * Show form to edit a permission.
     */
    public function editPermission(Permission $permission)
    {
        $roles = $permission->roles()->withCount('users')->get();

        return view('admin.permissions.edit', compact('permission', 'roles'));
    }

    /**
     * Update the specified permission.
     */
    public function updatePermission(Request $request, Permission $permission)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,'.$permission->id.'|regex:/^[a-zA-Z0-9-]+$/',
        ], [
            'name.regex' => 'Permission name can only contain letters, numbers, and hyphens.',
        ]);

        try {
            $oldName = $permission->name;
            $permission->update(['name' => $validated['name']]);

            Log::info("Permission updated: {$oldName} -> {$permission->name}");

            return redirect()->route('admin.permissions.index')
                ->with('success', "Permission '{$permission->name}' updated successfully.");
        } catch (\Exception $e) {
            Log::error('Failed to update permission: '.$e->getMessage());

            return back()->withInput()
                ->with('error', 'Failed to update permission. Please try again.');
        }
    }

    /**
     * Remove the specified permission.
     */
    public function destroyPermission(Permission $permission)
    {
        $rolesCount = $permission->roles()->count();
        if ($rolesCount > 0) {
            $roleNames = $permission->roles()->pluck('name')->implode(', ');

            return back()->with('error',
                "Cannot delete permission '{$permission->name}'. It is assigned to {$rolesCount} role(s): {$roleNames}");
        }

        try {
            $permissionName = $permission->name;
            $permission->delete();

            Log::info("Permission deleted: {$permissionName}");

            return redirect()->route('admin.permissions.index')
                ->with('success', "Permission '{$permissionName}' deleted successfully.");
        } catch (\Exception $e) {
            Log::error('Failed to delete permission: '.$e->getMessage());

            return back()->with('error', 'Failed to delete permission. Please try again.');
        }
    }

    /**
     * Bulk create permissions from an array.
     */
    public function bulkCreatePermissions(Request $request)
    {
        $validated = $request->validate([
            'permissions' => 'required|string',
        ]);

        $permissionsList = array_map('trim', explode("\n", $validated['permissions']));
        $permissionsList = array_filter($permissionsList, function ($value) {
            return ! empty($value);
        });

        $createdCount = 0;
        $existingCount = 0;
        $errors = [];

        foreach ($permissionsList as $permissionName) {
            try {
                if (! Permission::where('name', $permissionName)->exists()) {
                    Permission::create([
                        'name' => $permissionName,
                        'guard_name' => 'web',
                    ]);
                    $createdCount++;
                } else {
                    $existingCount++;
                }
            } catch (\Exception $e) {
                $errors[] = "Failed to create '{$permissionName}': ".$e->getMessage();
            }
        }

        $message = "{$createdCount} permission(s) created successfully.";
        if ($existingCount > 0) {
            $message .= " {$existingCount} permission(s) already existed and were skipped.";
        }

        if (! empty($errors)) {
            return back()->with('warning', $message)->with('errors_list', $errors);
        }

        return redirect()->route('admin.permissions.index')->with('success', $message);
    }
}
