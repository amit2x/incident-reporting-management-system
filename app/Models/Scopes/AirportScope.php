<?php

// app/Models/Scopes/AirportScope.php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class AirportScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (!auth()->check()) {
            return;
        }

        $user = auth()->user();

        // Check if user has role check method
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return;
        }

        // Apply airport filter
        if (!empty($user->airport_id)) {
            $builder->where(
                $model->getTable() . '.airport_id',
                $user->airport_id
            );
        }
    }

    /**
     * Remove the scope from the query.
     */
    public function remove(Builder $builder, Model $model): void
    {
        $builder->withoutGlobalScope($this);
    }
}
