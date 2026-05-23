<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use App\Models\Incident;
use App\Observers\IncidentObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Force HTTPS in production
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        // Set default string length for MySQL
        Schema::defaultStringLength(191);

        // Enable strict mode for Eloquent
        Model::shouldBeStrict(!app()->isProduction());

        // Register observers
        Incident::observe(IncidentObserver::class);

        // Define gates for incident access
        Gate::define('view-incident', function ($user, $incident) {
            return $user->canAccessIncident($incident);
        });

        Gate::define('edit-incident', function ($user, $incident) {
            return $user->can('edit-incident') && $user->canAccessIncident($incident);
        });

        Gate::define('delete-incident', function ($user, $incident) {
            return $user->can('delete-incident') && 
                   ($user->isAdmin() || $user->id === $incident->reported_by);
        });

        // Department-based gates
        Gate::define('view-department-incidents', function ($user, $departmentId) {
            return $user->isAdmin() || $user->department_id === $departmentId;
        });

        // Blade directives
        \Blade::if('admin', function () {
            return auth()->check() && auth()->user()->isAdmin();
        });

        \Blade::if('hod', function () {
            return auth()->check() && auth()->user()->isHOD();
        });
    }
}