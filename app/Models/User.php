<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes;

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

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * User's department
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Incidents reported by this user
     */
    public function reportedIncidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'reported_by');
    }

    /**
     * Incidents assigned to this user
     */
    public function assignedIncidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'assigned_to');
    }

    /**
     * Incidents escalated to this user
     */
    public function escalatedIncidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'escalated_to');
    }

    /**
     * Comments made by this user
     */
    public function comments(): HasMany
    {
        return $this->hasMany(IncidentComment::class);
    }

    /**
     * Activity logs for this user
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(UserActivityLog::class);
    }

    /**
     * Incident logs created by this user
     */
    public function incidentLogs(): HasMany
    {
        return $this->hasMany(IncidentLog::class);
    }

    /**
     * Assignments made by this user
     */
    public function assignmentsMade(): HasMany
    {
        return $this->hasMany(IncidentAssignment::class, 'assigned_by');
    }

    /**
     * Assignments received by this user
     */
    public function assignmentsReceived(): HasMany
    {
        return $this->hasMany(IncidentAssignment::class, 'assigned_to');
    }

    /**
     * Escalations made by this user
     */
    public function escalationsMade(): HasMany
    {
        return $this->hasMany(Escalation::class, 'escalated_by');
    }

    /**
     * Escalations received by this user
     */
    public function escalationsReceived(): HasMany
    {
        return $this->hasMany(Escalation::class, 'escalated_to');
    }

    /**
     * Escalation matrix entries where this user is the escalation target
     */
    public function escalationMatrixEntries(): HasMany
    {
        return $this->hasMany(EscalationMatrix::class, 'escalate_to_user_id');
    }

    /**
     * Media files uploaded by this user
     */
    public function uploadedMedia(): HasMany
    {
        return $this->hasMany(IncidentMedia::class, 'uploaded_by');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Scope for active users only
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for users in a specific department
     */
    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope for users with specific role
     */
    public function scopeByRole($query, $role)
    {
        return $query->whereHas('roles', function ($q) use ($role) {
            $q->where('name', $role);
        });
    }

    /**
     * Scope for searching users
     */
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
    // ACCESSORS & MUTATORS
    // ==========================================

    /**
     * Get avatar URL
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return Storage::url($this->avatar);
        }
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=random&size=128&bold=true';
    }

    /**
     * Get full name
     */
    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    /**
     * Get role name
     */
    public function getRoleNameAttribute(): string
    {
        return $this->roles->first()->name ?? 'No Role';
    }

    /**
     * Get initials
     */
    public function getInitialsAttribute(): string
    {
        $words = explode(' ', trim($this->name));
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr(end($words), 0, 1));
        }
        return strtoupper(substr($this->name, 0, 2));
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin') || $this->hasRole('super-admin');
    }

    /**
     * Check if user is HOD
     */
    public function isHOD(): bool
    {
        return $this->hasRole('hod');
    }

    /**
     * Check if user is supervisor
     */
    public function isSupervisor(): bool
    {
        return $this->hasRole('supervisor');
    }

    /**
     * Check if user is staff
     */
    public function isStaff(): bool
    {
        return $this->hasRole('staff');
    }

    /**
     * Check if user can access an incident
     */
    public function canAccessIncident(Incident $incident): bool
    {
        // Admin can access all
        if ($this->isAdmin()) {
            return true;
        }

        // HOD can access own department incidents
        if ($this->isHOD() && $this->department_id === $incident->department_id) {
            return true;
        }

        // Reporter can access own incidents
        if ($this->id === $incident->reported_by) {
            return true;
        }

        // Assigned user can access
        if ($this->id === $incident->assigned_to) {
            return true;
        }

        // Same department user can access
        if ($this->department_id === $incident->department_id && $this->isSupervisor()) {
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
                'avg_response_time' => round(Incident::whereNotNull('acknowledged_at')
                    ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, acknowledged_at)) as avg_time')
                    ->value('avg_time') ?? 0, 1),
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
            'avg_response_time' => round(Incident::where('department_id', $departmentId)
                ->whereNotNull('acknowledged_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, acknowledged_at)) as avg_time')
                ->value('avg_time') ?? 0, 1),
        ];
    }

    /**
     * Get notification preferences
     */
    public function wantsEmailNotification(string $type): bool
    {
        return $this->preferences['email_notifications'][$type] ?? true;
    }

    /**
     * Get notification preferences
     */
    public function wantsPushNotification(string $type): bool
    {
        return $this->preferences['push_notifications'][$type] ?? true;
    }

    /**
     * Record login
     */
    public function recordLogin(string $ip): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ]);
    }
}
