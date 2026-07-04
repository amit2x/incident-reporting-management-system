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

        // Disable strict mode completely to prevent memory issues
        Model::shouldBeStrict(false);

        // Comment out lazy loading handler - it can cause recursion
        // Model::handleLazyLoadingViolationUsing(function ($model, $relation) {
        //     \Illuminate\Support\Facades\Log::warning(
        //         'Lazy loading attempted: ' . get_class($model) . '::' . $relation
        //     );
        // });

        // Register observers
        Incident::observe(IncidentObserver::class);

        // Define gates with simple checks
        Gate::define('view-incident', function ($user, $incident = null) {
            if (!$incident) return true;
            return $user->canAccessIncident($incident);
        });

        Gate::define('edit-incident', function ($user, $incident = null) {
            if (!$incident) return $user->can('edit-incident');
            return $user->can('edit-incident') && $user->canAccessIncident($incident);
        });

        Gate::define('delete-incident', function ($user, $incident = null) {
            if (!$incident) return $user->can('delete-incident');
            return $user->can('delete-incident') &&
                   ($user->isAdmin() || $user->id === $incident->reported_by);
        });

        Gate::define('view-department-incidents', function ($user, $departmentId = null) {
            if (!$departmentId) return true;
            return $user->isAdmin() || $user->department_id === $departmentId;
        });

        // Blade directives
        \Blade::if('admin', function () {
            return auth()->check() && auth()->user()->isAdmin();
        });

        \Blade::if('hod', function () {
            return auth()->check() && auth()->user()->isHOD();
        });
        
        
        //'open','acknowledged','in_progress','escalated','resolved','closed','rejected'
         // Dynamic Badge Counter for Navigation/Layouts
        view()->composer('*', function ($view) {
            if (auth()->check()) {
                $user = auth()->user();
                
                // Base query for un-resolved and un-closed incidents
                $query = Incident::whereNotIn('status', ['resolved', 'closed']);
        
                // Filter by department ONLY IF the user is NOT a super-admin and NOT a director
                if (!$user->hasRole(['super-admin', 'director'])) {
                    $query->where('department_id', $user->department_id);
                }
        
                $openCount = $query->count();
                
                $view->with('globalOpenIncidentCount', $openCount);
            }
        });

    }
}
