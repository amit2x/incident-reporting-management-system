<?php

// app/Traits/HasAirport.php

namespace App\Traits;

use App\Models\Airport;
use App\Models\Scopes\AirportScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

trait HasAirport
{
    /**
     * Boot the HasAirport trait
     */
    protected static function bootHasAirport()
    {
        // Apply global scope
        static::addGlobalScope(new AirportScope());

        // Auto-set airport_id on creation
        static::creating(function ($model) {
            if (Auth::check() && empty($model->airport_id)) {
                $model->airport_id = Auth::user()->airport_id;
            }
        });

        // Prevent changing airport_id on update (unless super admin)
        static::updating(function ($model) {
            if (Auth::check() && $model->isDirty('airport_id')) {
                $user = Auth::user();

                // Allow super admin to change airport
                if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
                    return;
                }

                // Revert airport_id to original
                $model->airport_id = $model->getOriginal('airport_id');
            }
        });

        // Clear cache on model events
        static::saved(function ($model) {
            if ($model->wasChanged('airport_id')) {
                Cache::forget("airport_{$model->getOriginal('airport_id')}");
                Cache::forget("airport_{$model->airport_id}");
            }
        });
    }

    /**
     * Get the airport that owns the model.
     */
    public function airport()
    {
        return $this->belongsTo(Airport::class)->withDefault([
            'code' => 'N/A',
            'name' => 'No Airport Assigned',
        ]);
    }

    /**
     * Scope a query to only include models from a specific airport.
     */
    public function scopeForAirport($query, $airportId)
    {
        return $query->where($this->getTable() . '.airport_id', $airportId);
    }

    /**
     * Scope a query to only include models from the current airport.
     */
    public function scopeCurrentAirport($query)
    {
        if (Auth::check()) {
            return $query->where($this->getTable() . '.airport_id', Auth::user()->airport_id);
        }
        return $query;
    }

    /**
     * Scope a query to include multiple airports.
     */
    public function scopeInAirports($query, array $airportIds)
    {
        return $query->whereIn($this->getTable() . '.airport_id', $airportIds);
    }

    /**
     * Check if the model belongs to the current user's airport.
     */
    public function belongsToCurrentAirport(): bool
    {
        if (!Auth::check()) {
            return false;
        }
        return $this->airport_id === Auth::user()->airport_id;
    }

    /**
     * Check if the model belongs to a specific airport.
     */
    public function belongsToAirport($airportId): bool
    {
        if ($airportId instanceof Airport) {
            $airportId = $airportId->id;
        }
        return $this->airport_id == $airportId;
    }

    /**
     * Move the model to a different airport.
     */
    public function moveToAirport($airportId): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $user = Auth::user();

        // Only super admin can move records between airports
        if (!(method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())) {
            return false;
        }

        return $this->update(['airport_id' => $airportId]);
    }

    /**
     * Get the airport code attribute.
     */
    public function getAirportCodeAttribute()
    {
        return $this->airport ? $this->airport->code : null;
    }

    /**
     * Get the airport name attribute.
     */
    public function getAirportNameAttribute()
    {
        return $this->airport ? $this->airport->name : null;
    }

    /**
     * Get the airport code with prefix for IDs.
     */
    public function getAirportPrefix(): string
    {
        return $this->airport ? $this->airport->code : 'N/A';
    }

    /**
     * Check if model is accessible in current context.
     */
    public function isAccessible(): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $user = Auth::user();

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        return $this->airport_id === $user->airport_id;
    }
}
