<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            DepartmentSeeder::class,
            RoleAndPermissionSeeder::class,
            AdminUserSeeder::class,
            IncidentCategorySeeder::class,
            EscalationMatrixSeeder::class,
        ]);
    }
}
