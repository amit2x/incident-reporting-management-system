<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'phone',
        'employee_id',
        'avatar',
        'department_id',
        'designation',
        'status',
        'fcm_token',
        'device_type',
        'last_login_at',
        'last_login_ip',
        'preferences',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'preferences' => 'array',
        'password' => 'hashed',
    ];

    protected $appends = [
        'avatar_url',
        'full_name',
        'role_name',
        'initials',
    ];

    // In app/Models/User.php

    // Cache role checks to prevent repeated queries
    // Add this to cache roles
    protected $cachedRoles = null;

    protected $cachedPermissions = null;

    /**
     * Get cached roles to prevent repeated queries
     */
    public function getCachedRoles()
    {
        if ($this->cachedRoles === null) {
            // Only query once per request
            $this->cachedRoles = $this->getRoleNames()->toArray();
        }

        return $this->cachedRoles;
    }

    /**
     * Clear role cache
     */
    public function clearRoleCache(): void
    {
        $this->cachedRoles = null;
        $this->cachedPermissions = null;
    }

    /**
     * Check if user is super admin - OPTIMIZED
     */
    public function isSuperAdmin(): bool
    {
        return in_array('super-admin', $this->getCachedRoles());
    }

    /**
     * Check if user is admin - OPTIMIZED
     */
    public function isAdmin(): bool
    {
        $roles = $this->getCachedRoles();

        return in_array('admin', $roles) || in_array('super-admin', $roles);
    }

    /**
     * Check if user is HOD - OPTIMIZED
     */
    public function isHOD(): bool
    {
        return in_array('hod', $this->getCachedRoles());
    }

    /**
     * Check if user is supervisor - OPTIMIZED
     */
    public function isSupervisor(): bool
    {
        return in_array('supervisor', $this->getCachedRoles());
    }

    /**
     * Check if user is staff - OPTIMIZED
     */
    public function isStaff(): bool
    {
        return in_array('staff', $this->getCachedRoles());
    }

    /**
     * Get role name - OPTIMIZED
     */
    // public function getRoleNameAttribute(): string
    // {
    //     $roles = $this->getCachedRoles();
    //     return $roles[0] ?? 'No Role';
    // }

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function reportedIncidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'reported_by');
    }

    public function assignedIncidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'assigned_to');
    }

    public function escalatedIncidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'escalated_to');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(IncidentComment::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(UserActivityLog::class);
    }

    public function incidentLogs(): HasMany
    {
        return $this->hasMany(IncidentLog::class);
    }

    public function assignmentsMade(): HasMany
    {
        return $this->hasMany(IncidentAssignment::class, 'assigned_by');
    }

    public function assignmentsReceived(): HasMany
    {
        return $this->hasMany(IncidentAssignment::class, 'assigned_to');
    }

    public function escalationsMade(): HasMany
    {
        return $this->hasMany(Escalation::class, 'escalated_by');
    }

    public function escalationsReceived(): HasMany
    {
        return $this->hasMany(Escalation::class, 'escalated_to');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%")
                ->orWhere('employee_id', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%");
        });
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    /**
     * Get avatar URL
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return Storage::url($this->avatar);
        }

        return 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&background=random&size=128&bold=true&color=fff';
    }

    /**
     * Get full name
     */
    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    /**
     * Get role name - FIXED: Handle lazy loading properly
     */
    public function getRoleNameAttribute(): string
    {

        // Check if roles relationship is already loaded
        if ($this->relationLoaded('roles')) {
            return $this->roles->first()?->name ?? 'No Role';
        }

        // If not loaded, query directly to avoid lazy loading exception
        try {

            $roles = $this->getCachedRoles();

            return $roles[0] ?? 'No Role';
            $role = $this->roles()->first();

            return $role?->name ?? 'No Role';
        } catch (\Exception $e) {
            return 'No Role';
        }
    }

    /**
     * Get initials
     */
    public function getInitialsAttribute(): string
    {
        $words = explode(' ', trim($this->name));
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1).substr(end($words), 0, 1));
        }

        return strtoupper(substr($this->name, 0, 2));
    }

    /**
     * Get the first role name directly from database
     */
    public function getFirstRoleName(): string
    {
        return $this->roles()->value('name') ?? 'No Role';
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    /**
     * Check if user is super admin
     */
    // public function isSuperAdmin(): bool
    // {
    //     return $this->hasRole('super-admin');
    // }

    // /**
    //  * Check if user is admin
    //  */
    // public function isAdmin(): bool
    // {
    //     return $this->hasRole('admin') || $this->hasRole('super-admin');
    // }

    // /**
    //  * Check if user is HOD
    //  */
    // public function isHOD(): bool
    // {
    //     return $this->hasRole('hod');
    // }

    // /**
    //  * Check if user is supervisor
    //  */
    // public function isSupervisor(): bool
    // {
    //     return $this->hasRole('supervisor');
    // }

    // /**
    //  * Check if user is staff
    //  */
    // public function isStaff(): bool
    // {
    //     return $this->hasRole('staff');
    // }

    /**
     * Check if user can access an incident
     */
    // public function canAccessIncident(Incident $incident): bool
    // {
    //     if ($this->isAdmin()) {
    //         return true;
    //     }

    //     if ($this->isHOD() && $this->department_id === $incident->department_id) {
    //         return true;
    //     }

    //     if ($this->id === $incident->reported_by) {
    //         return true;
    //     }

    //     if ($this->id === $incident->assigned_to) {
    //         return true;
    //     }

    //     if ($this->department_id === $incident->department_id && $this->isSupervisor()) {
    //         return true;
    //     }

    //     return false;
    // }

    // app/Models/User.php

    /**
     * Check if user can access/view an incident
     */
    public function canAccessIncident(Incident $incident): bool
    {
        // Admin can access all incidents
        if ($this->isAdmin()) {
            return true;
        }

        // HOD can access own department incidents
        if ($this->isHOD() && $this->department_id === $incident->department_id) {
            return true;
        }

        // Reporter can access their own incidents
        if ($this->id === $incident->reported_by) {
            return true;
        }

        // Assigned user can access
        if ($this->id === $incident->assigned_to) {
            return true;
        }

        // ==========================================
        // ESCALATED USER CAN ACCESS (CRITICAL FIX)
        // ==========================================
        // If incident is currently escalated to this user, they can access it
        if ($this->id === $incident->escalated_to && $incident->status === 'escalated') {
            return true;
        }

        // If incident was ever escalated to this user, they can still access it
        // (for viewing history, even if they accepted/rejected/returned)
        $wasEscalatedToUser = Escalation::where('incident_id', $incident->id)
            ->where('escalated_to', $this->id)
            ->exists();

        if ($wasEscalatedToUser) {
            return true;
        }

        // Supervisor can access own department incidents
        if ($this->isSupervisor() && $this->department_id === $incident->department_id) {
            return true;
        }

        // Same department staff can view (but limited actions)
        if ($this->department_id === $incident->department_id) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can take action on this incident (beyond just viewing)
     */
    public function canTakeActionOnIncident(Incident $incident): bool
    {
        // Admin can always take action
        if ($this->isAdmin()) {
            return true;
        }

        // HOD can take action on own department incidents
        if ($this->isHOD() && $this->department_id === $incident->department_id) {
            return true;
        }

        // Assigned user can take action
        if ($this->id === $incident->assigned_to) {
            return true;
        }

        // Currently escalated user can take action (accept/reject/return)
        if ($this->id === $incident->escalated_to && $incident->status === 'escalated') {
            return true;
        }

        // Reporter can take limited action (edit if still open)
        if ($this->id === $incident->reported_by && in_array($incident->status, ['open', 'acknowledged'])) {
            return true;
        }

        // Supervisor can take action on own department incidents
        if ($this->isSupervisor() && $this->department_id === $incident->department_id) {
            return true;
        }

        return false;
    }

    /**
     * Get dashboard statistics for user
     */
    public function getDashboardStats(): array
    {
        $departmentId = $this->department_id;
        $userId = $this->id;

        if ($this->isAdmin()) {
            return [
                'total_incidents' => Incident::count(),
                'open_incidents' => Incident::whereIn('status', ['open', 'acknowledged', 'in_progress'])->count(),
                'resolved_today' => Incident::whereDate('resolved_at', today())->count(),
                'escalated_incidents' => Incident::where('status', 'escalated')->count(),
                'overdue_incidents' => Incident::overdue()->count(),
                'my_assigned' => Incident::where('assigned_to', $userId)
                    ->whereIn('status', ['open', 'acknowledged', 'in_progress'])->count(),
                'my_reported' => Incident::where('reported_by', $userId)->count(),
            ];
        }

        return [
            'total_incidents' => Incident::where('department_id', $departmentId)->count(),
            'open_incidents' => Incident::where('department_id', $departmentId)
                ->whereIn('status', ['open', 'acknowledged', 'in_progress'])->count(),
            'resolved_today' => Incident::where('department_id', $departmentId)
                ->whereDate('resolved_at', today())->count(),
            'escalated_incidents' => Incident::where('department_id', $departmentId)
                ->where('status', 'escalated')->count(),
            'overdue_incidents' => Incident::where('department_id', $departmentId)->overdue()->count(),
            'my_assigned' => Incident::where('assigned_to', $userId)
                ->whereIn('status', ['open', 'acknowledged', 'in_progress'])->count(),
            'my_reported' => Incident::where('reported_by', $userId)->count(),
        ];
    }
}
