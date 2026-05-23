<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class IncidentCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'parent_id',
        'default_priority',
        'sla_minutes',
        'requires_approval',
        'is_active',
        'sort_order',
        'required_fields',
    ];

    protected $casts = [
        'requires_approval' => 'boolean',
        'is_active' => 'boolean',
        'required_fields' => 'array',
        'default_priority' => 'integer',
        'sla_minutes' => 'integer',
    ];

    // ==========================================
    // BOOT METHOD
    // ==========================================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });

        static::updating(function ($category) {
            if ($category->isDirty('name') && !$category->isDirty('slug')) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function parent(): BelongsTo
    {
        return $this->belongsTo(IncidentCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(IncidentCategory::class, 'parent_id');
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'category_id');
    }

    public function escalationMatrices(): HasMany
    {
        return $this->hasMany(EscalationMatrix::class, 'category_id');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeRequiresApproval($query)
    {
        return $query->where('requires_approval', true);
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    public function getIncidentCountAttribute(): int
    {
        return $this->incidents()->count();
    }

    public function getOpenIncidentsCountAttribute(): int
    {
        return $this->incidents()->open()->count();
    }

    public function getResolvedIncidentsCountAttribute(): int
    {
        return $this->incidents()->resolved()->count();
    }

    public function getSlaFormattedAttribute(): string
    {
        if ($this->sla_minutes < 60) {
            return "{$this->sla_minutes} minutes";
        }

        $hours = floor($this->sla_minutes / 60);
        $minutes = $this->sla_minutes % 60;

        if ($minutes === 0) {
            return "{$hours} hour" . ($hours > 1 ? 's' : '');
        }

        return "{$hours}h {$minutes}m";
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    /**
     * Get all subcategories (including nested)
     */
    public function getAllChildren(): array
    {
        $children = [];

        foreach ($this->children as $child) {
            $children[] = $child->id;
            $children = array_merge($children, $child->getAllChildren());
        }

        return $children;
    }

    /**
     * Get statistics for this category
     */
    public function getStats(string $period = 'monthly'): array
    {
        $dateRange = match ($period) {
            'weekly' => [now()->startOfWeek(), now()->endOfWeek()],
            'monthly' => [now()->startOfMonth(), now()->endOfMonth()],
            'yearly' => [now()->startOfYear(), now()->endOfYear()],
            default => [now()->subDays(30), now()],
        };

        $incidents = $this->incidents()->whereBetween('created_at', $dateRange);

        return [
            'name' => $this->name,
            'icon' => $this->icon,
            'color' => $this->color,
            'total' => (clone $incidents)->count(),
            'open' => (clone $incidents)->open()->count(),
            'resolved' => (clone $incidents)->resolved()->count(),
            'sla_breaches' => (clone $incidents)->slaBreached()->count(),
        ];
    }

    /**
     * Get SLA compliance percentage
     */
    public function getSlaCompliance(string $period = 'monthly'): float
    {
        $dateRange = match ($period) {
            'weekly' => [now()->startOfWeek(), now()->endOfWeek()],
            'monthly' => [now()->startOfMonth(), now()->endOfMonth()],
            default => [now()->subDays(30), now()],
        };

        $total = $this->incidents()->whereBetween('created_at', $dateRange)->count();

        if ($total === 0) {
            return 100.0;
        }

        $breached = $this->incidents()
            ->whereBetween('created_at', $dateRange)
            ->slaBreached()
            ->count();

        return round((($total - $breached) / $total) * 100, 2);
    }
}
