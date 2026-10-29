<?php

// app/Models/Attendance.php

namespace App\Models;

use App\Traits\HasAirport;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasAirport;

    protected $fillable = [
        'airport_id', 'user_id', 'attendance_date', 'status',
        'marked_by', 'marked_by_role', 'marked_at',
        'latitude', 'longitude', 'location_verified', 'distance_from_geofence',
        'is_modified', 'modified_by', 'modified_at', 'modification_reason',
        'is_backfilled', 'remarks',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'marked_at' => 'datetime',
        'modified_at' => 'datetime',
        'location_verified' => 'boolean',
        'is_modified' => 'boolean',
        'is_backfilled' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    // ───────────── Relationships ─────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function markedBy()
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    public function modifiedBy()
    {
        return $this->belongsTo(User::class, 'modified_by');
    }

    public function logs()
    {
        return $this->hasMany(AttendanceLog::class);
    }

    // ───────────── Scopes ─────────────

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('attendance_date', $date);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeSelfMarked($query)
    {
        return $query->where('marked_by_role', 'self');
    }

    public function scopeNodalMarked($query)
    {
        return $query->where('marked_by_role', 'nodal_officer');
    }

    public function scopeBetweenDates($query, $from, $to)
    {
        return $query->whereBetween('attendance_date', [$from, $to]);
    }

    // ───────────── Accessors ─────────────

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'present' => 'success',
            'absent' => 'danger',
            'late' => 'warning',
            'half_day' => 'info',
            'on_leave' => 'secondary',
            default => 'light',
        };
    }

    public function getMarkedByLabelAttribute(): string
    {
        return match ($this->marked_by_role) {
            'self' => 'Self',
            'nodal_officer' => 'Nodal Officer',
            'admin' => 'Admin',
            default => 'Unknown',
        };
    }
}
