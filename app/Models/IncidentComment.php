<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IncidentComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'incident_id',
        'user_id',
        'parent_id',
        'content',
        'mentions',
        'attachments',
        'is_internal',
        'is_edited',
        'edited_at',
    ];

    protected $casts = [
        'mentions' => 'array',
        'attachments' => 'array',
        'is_internal' => 'boolean',
        'is_edited' => 'boolean',
        'edited_at' => 'datetime',
    ];

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(IncidentComment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(IncidentComment::class, 'parent_id')->orderBy('created_at', 'asc');
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopePublic($query)
    {
        return $query->where('is_internal', false);
    }

    public function getMentionedUsersAttribute()
    {
        if ($this->mentions) {
            return User::whereIn('id', $this->mentions)->get();
        }
        return collect();
    }
}
