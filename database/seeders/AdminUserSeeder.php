<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'username' => 'superadmin',
            'email' => 'superadmin@irm.com',
            'password' => Hash::make('password123'),
            'phone' => '+1234567890',
            'employee_id' => 'EMP001',
            'department_id' => 1,
            'designation' => 'System Administrator',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $superAdmin->assignRole('super-admin');

        $admin = User::create([
            'name' => 'Admin User',
            'username' => 'admin',
            'email' => 'admin@irm.com',
            'password' => Hash::make('password123'),
            'phone' => '+1234567891',
            'employee_id' => 'EMP002',
            'department_id' => 1,
            'designation' => 'Administrator',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');

        $hodOps = User::create([
            'name' => 'John Operations',
            'username' => 'johnops',
            'email' => 'john.ops@irm.com',
            'password' => Hash::make('password123'),
            'phone' => '+1234567892',
            'employee_id' => 'EMP003',
            'department_id' => 1,
            'designation' => 'Head of Operations',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $hodOps->assignRole('hod');

        $hodSecurity = User::create([
            'name' => 'Jane Security',
            'username' => 'janesec',
            'email' => 'jane.sec@irm.com',
            'password' => Hash::make('password123'),
            'phone' => '+1234567893',
            'employee_id' => 'EMP004',
            'department_id' => 2,
            'designation' => 'Head of Security',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $hodSecurity->assignRole('hod');
    }
}
