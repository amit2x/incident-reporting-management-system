<?php

// app/Models/Airport.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Airport extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'code',
        'icao_code',
        'iata_code',
        'name',
        'description',
        'address',
        'city',
        'state',
        'country',
        'timezone',
        'latitude',
        'longitude',
        'contact_email',
        'contact_phone',
        'website',
        'logo',
        'color',
        'settings',
        'is_active',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = [
        'full_name',
        'location_string',
    ];

    /**
     * Relationships
     */

    /**
     * Get the user who created this airport.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this airport.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get all users associated with this airport.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get all incidents for this airport.
     */
    public function incidents()
    {
        return $this->hasMany(Incident::class);
    }

    /**
     * Get all departments for this airport.
     */
    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    /**
     * Get all categories for this airport.
     */
    public function categories()
    {
        return $this->hasMany(IncidentCategory::class);
    }

    /**
     * Scopes
     */

    /**
     * Scope a query to only include active airports.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Scope a query to search airports.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
                ->orWhere('code', 'LIKE', "%{$search}%")
                ->orWhere('icao_code', 'LIKE', "%{$search}%")
                ->orWhere('iata_code', 'LIKE', "%{$search}%")
                ->orWhere('city', 'LIKE', "%{$search}%")
                ->orWhere('country', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Accessors & Mutators
     */

    /**
     * Get the full name attribute.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->name} ({$this->code})";
    }

    /**
     * Get the location string attribute.
     */
    public function getLocationStringAttribute(): string
    {
        $location = $this->city;

        if ($this->state) {
            $location .= ", {$this->state}";
        }

        if ($this->country && $this->country !== 'India') {
            $location .= ", {$this->country}";
        }

        return $location;
    }

    /**
     * Get the display name for dropdowns.
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->code} - {$this->name}";
    }

    /**
     * Set the code attribute to uppercase.
     */
    public function setCodeAttribute($value)
    {
        $this->attributes['code'] = strtoupper($value);
    }

    /**
     * Set the icao_code attribute to uppercase.
     */
    public function setIcaoCodeAttribute($value)
    {
        $this->attributes['icao_code'] = $value ? strtoupper($value) : null;
    }

    /**
     * Set the iata_code attribute to uppercase.
     */
    public function setIataCodeAttribute($value)
    {
        $this->attributes['iata_code'] = $value ? strtoupper($value) : null;
    }

    /**
     * Set the email attribute to lowercase.
     */
    public function setContactEmailAttribute($value)
    {
        $this->attributes['contact_email'] = $value ? strtolower($value) : null;
    }

    /**
     * Helper Methods
     */

    /**
     * Check if airport is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Activate the airport.
     */
    public function activate(): bool
    {
        return $this->update(['is_active' => true]);
    }

    /**
     * Deactivate the airport.
     */
    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }

    /**
     * Get the airport statistics.
     */
    public function getStatistics(): array
    {
        return [
            'total_incidents' => $this->incidents()->count(),
            'open_incidents' => $this->incidents()->where('status', 'open')->count(),
            'total_users' => $this->users()->count(),
            'total_departments' => $this->departments()->count(),
        ];
    }

    /**
     * Get the airport code with prefix for incident IDs.
     */
    public function getIncidentPrefix(): string
    {
        return $this->code;
    }

    public function attendanceSettings(): array
    {
        $defaults = [
            'allow_self_mark' => true,
            'location_check_required' => true,
            'geofence_lat' => $this->latitude ?? 22.64076300,
            'geofence_lng' => $this->longitude ?? 88.43851300,
            'geofence_radius_meters' => 300,
            'self_mark_start_time' => '11:00',
            'self_mark_end_time' => '18:00',
            'timezone' => $this->timezone ?? 'Asia/Kolkata',
        ];

        return array_merge($defaults, $this->settings['attendance'] ?? []);
    }
    
    
}
