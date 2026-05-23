<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\IncidentCategory;

class IncidentCategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            [
                'name' => 'Safety Hazard',
                'slug' => 'safety-hazard',
                'description' => 'Any safety-related hazards or risks',
                'icon' => 'fa-exclamation-triangle',
                'color' => '#EF4444',
                'default_priority' => 4,
                'sla_minutes' => 30,
                'requires_approval' => true,
            ],
            [
                'name' => 'Security Issue',
                'slug' => 'security-issue',
                'description' => 'Security-related incidents or breaches',
                'icon' => 'fa-shield-alt',
                'color' => '#DC2626',
                'default_priority' => 4,
                'sla_minutes' => 15,
                'requires_approval' => true,
            ],
            [
                'name' => 'Maintenance Required',
                'slug' => 'maintenance-required',
                'description' => 'General maintenance and repairs',
                'icon' => 'fa-tools',
                'color' => '#F59E0B',
                'default_priority' => 2,
                'sla_minutes' => 240,
            ],
            [
                'name' => 'IT/Network Issue',
                'slug' => 'it-network-issue',
                'description' => 'IT infrastructure and network problems',
                'icon' => 'fa-laptop-code',
                'color' => '#8B5CF6',
                'default_priority' => 3,
                'sla_minutes' => 120,
            ],
            [
                'name' => 'Cleaning Issue',
                'slug' => 'cleaning-issue',
                'description' => 'Cleaning and hygiene-related issues',
                'icon' => 'fa-broom',
                'color' => '#10B981',
                'default_priority' => 1,
                'sla_minutes' => 120,
            ],
            [
                'name' => 'Electrical Issue',
                'slug' => 'electrical-issue',
                'description' => 'Electrical problems and outages',
                'icon' => 'fa-bolt',
                'color' => '#6366F1',
                'default_priority' => 3,
                'sla_minutes' => 60,
                'requires_approval' => true,
            ],
            [
                'name' => 'Water Leakage',
                'slug' => 'water-leakage',
                'description' => 'Water leakage and plumbing issues',
                'icon' => 'fa-water',
                'color' => '#0EA5E9',
                'default_priority' => 3,
                'sla_minutes' => 120,
            ],
            [
                'name' => 'Infrastructure Damage',
                'slug' => 'infrastructure-damage',
                'description' => 'Damage to infrastructure and property',
                'icon' => 'fa-building',
                'color' => '#6B7280',
                'default_priority' => 2,
                'sla_minutes' => 480,
            ],
            [
                'name' => 'Suspicious Activity',
                'slug' => 'suspicious-activity',
                'description' => 'Suspicious persons or activities',
                'icon' => 'fa-user-secret',
                'color' => '#7C3AED',
                'default_priority' => 4,
                'sla_minutes' => 10,
                'requires_approval' => true,
            ],
            [
                'name' => 'Vehicle Obstruction',
                'slug' => 'vehicle-obstruction',
                'description' => 'Vehicle-related obstructions or issues',
                'icon' => 'fa-car',
                'color' => '#F97316',
                'default_priority' => 2,
                'sla_minutes' => 60,
            ],
            [
                'name' => 'Fire Safety',
                'slug' => 'fire-safety',
                'description' => 'Fire safety equipment and concerns',
                'icon' => 'fa-fire',
                'color' => '#EF4444',
                'default_priority' => 4,
                'sla_minutes' => 15,
                'requires_approval' => true,
            ],
            [
                'name' => 'Progress Update',
                'slug' => 'progress-update',
                'description' => 'Progress updates from teams',
                'icon' => 'fa-chart-line',
                'color' => '#10B981',
                'default_priority' => 1,
                'sla_minutes' => 1440,
            ],
        ];

        foreach ($categories as $category) {
            IncidentCategory::create($category);
        }
    }
}
