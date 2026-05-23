<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class IncidentMedia extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'incident_id',
        'uploaded_by',
        'media_type',
        'file_path',
        'file_name',
        'original_name',
        'mime_type',
        'file_size',
        'thumbnail_path',
        'description',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'file_size' => 'integer',
    ];

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    public function getThumbnailUrlAttribute(): string
    {
        return $this->thumbnail_path ? Storage::url($this->thumbnail_path) : $this->url;
    }

    public function getHumanReadableSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function isImage(): bool
    {
        return $this->media_type === 'image';
    }

    public function isVideo(): bool
    {
        return $this->media_type === 'video';
    }

    public function isDocument(): bool
    {
        return $this->media_type === 'document';
    }
}
