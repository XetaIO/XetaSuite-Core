<?php

declare(strict_types=1);

namespace XetaSuite\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Xetaio\Counts\Concerns\HasCounts;
use XetaSuite\Models\Concerns\SiteScoped;
use XetaSuite\Observers\ZoneObserver;

#[ObservedBy([ZoneObserver::class])]
class Zone extends Model
{
    use HasCounts;
    use HasFactory;
    use SiteScoped;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'site_id',
        'name',
        'parent_id',
        'allow_material',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'allow_material' => 'boolean',
    ];

    /**
     * The relations to be counted.
     */
    protected static array $countsConfig = [
        'site' => 'zone_count',
    ];

    /**
     * Get the materials for the zone.
     */
    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }

    /**
     * Get the site that owns the zone.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get the parent zone for the zone.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'parent_id');
    }

    /**
     * Get the child zones for the zone.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Zone::class, 'parent_id');
    }

    /**
     * Get all descendant zones for the zone.
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    /**
     * Scope to load children recursively with counts.
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function withRecursiveChildren($query): void
    {
        $query->with(['children' => function ($q): void {
            $q->withRecursiveChildren();
        }])->withCount(['children', 'materials']);
    }

    /**
     * Get children with recursive loading for tree view.
     */
    public function childrenRecursive(): HasMany
    {
        return $this->children()
            ->with(['childrenRecursive', 'materials:id,zone_id,name,description'])
            ->withCount(['children', 'materials'])
            ->orderBy('name');
    }
}
