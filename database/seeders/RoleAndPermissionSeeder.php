<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Incident permissions
            'create-incident',
            'edit-incident',
            'delete-incident',
            'view-incident',
            'assign-incident',
            'escalate-incident',
            'resolve-incident',
            'close-incident',
            'reopen-incident',

            // Comment permissions
            'add-comment',
            'edit-comment',
            'delete-comment',

            // Media permissions
            'upload-media',
            'delete-media',

            // Dashboard permissions
            'view-dashboard',
            'view-analytics',
            'view-reports',
            'export-reports',

            // Admin permissions
            'manage-users',
            'manage-roles',
            'manage-departments',
            'manage-categories',
            'manage-escalation-matrix',
            'manage-settings',
            'view-audit-logs',

            // Notification permissions
            'send-notifications',
            'manage-notification-settings',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Super Admin
        $superAdmin = Role::create(['name' => 'super-admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Admin
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo([
            'create-incident', 'edit-incident', 'delete-incident', 'view-incident',
            'assign-incident', 'escalate-incident', 'resolve-incident', 'close-incident',
            'reopen-incident', 'add-comment', 'edit-comment', 'delete-comment',
            'upload-media', 'delete-media', 'view-dashboard', 'view-analytics',
            'view-reports', 'export-reports', 'manage-users', 'manage-departments',
            'manage-categories', 'manage-escalation-matrix', 'send-notifications',
            'view-audit-logs',
        ]);

        // Department Head (HOD)
        $hod = Role::create(['name' => 'hod']);
        $hod->givePermissionTo([
            'create-incident', 'edit-incident', 'view-incident',
            'assign-incident', 'escalate-incident', 'resolve-incident', 'close-incident',
            'reopen-incident', 'add-comment', 'edit-comment',
            'upload-media', 'view-dashboard', 'view-analytics',
            'view-reports', 'export-reports', 'send-notifications',
        ]);

        // Supervisor
        $supervisor = Role::create(['name' => 'supervisor']);
        $supervisor->givePermissionTo([
            'create-incident', 'edit-incident', 'view-incident',
            'assign-incident', 'escalate-incident', 'resolve-incident',
            'add-comment', 'edit-comment', 'upload-media',
            'view-dashboard', 'view-reports',
        ]);

        // Staff/User
        $staff = Role::create(['name' => 'staff']);
        $staff->givePermissionTo([
            'create-incident', 'edit-incident', 'view-incident',
            'add-comment', 'edit-comment', 'upload-media',
            'view-dashboard',
        ]);

        // Viewer/Auditor
        $viewer = Role::create(['name' => 'viewer']);
        $viewer->givePermissionTo([
            'view-incident', 'view-dashboard', 'view-reports',
        ]);
    }
}
