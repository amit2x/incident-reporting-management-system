<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    public function run()
    {
        $departments = [
            [
                'name' => 'Operations',
                'code' => 'OPS',
                'description' => 'Operations Department',
                'color' => '#3B82F6',
                'icon' => 'fa-cogs',
                'email' => 'ops@irm.com',
            ],
            [
                'name' => 'Security',
                'code' => 'SEC',
                'description' => 'Security Department',
                'color' => '#EF4444',
                'icon' => 'fa-shield-alt',
                'email' => 'security@irm.com',
            ],
            [
                'name' => 'Engg Civil',
                'code' => 'CIVIL',
                'description' => 'Civil Department',
                'color' => '#F59E0B',
                'icon' => 'fa-tools',
                'email' => 'civil@irm.com',
            ],
            [
                'name' => 'IT',
                'code' => 'IT',
                'description' => 'Information Technology',
                'color' => '#8B5CF6',
                'icon' => 'fa-laptop-code',
                'email' => 'it@irm.com',
            ],
            [
                'name' => 'Housekeeping',
                'code' => 'HK',
                'description' => 'Housekeeping Department',
                'color' => '#10B981',
                'icon' => 'fa-broom',
                'email' => 'housekeeping@irm.com',
            ],
            [
                'name' => 'Fire & Safety',
                'code' => 'FS',
                'description' => 'Fire and Safety Department',
                'color' => '#EC4899',
                'icon' => 'fa-fire-extinguisher',
                'email' => 'firesafety@irm.com',
            ],
            [
                'name' => 'Electrical',
                'code' => 'ELE',
                'description' => 'Electrical Department',
                'color' => '#6366F1',
                'icon' => 'fa-bolt',
                'email' => 'electrical@irm.com',
            ],
            [
                'name' => 'Administration',
                'code' => 'ADM',
                'description' => 'Administration Department',
                'color' => '#14B8A6',
                'icon' => 'fa-building',
                'email' => 'admin@irm.com',
            ],
        ];

        foreach ($departments as $department) {
            Department::create($department);
        }
    }
}
