<?php

declare(strict_types=1);

namespace XetaSuite\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use XetaSuite\Observers\SiteObserver;

#[ObservedBy([SiteObserver::class])]
class Site extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'is_headquarters',
        'email',
        'office_phone',
        'cell_phone',
        'address',
        'zip_code',
        'city',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_headquarters' => 'boolean',
    ];

    /**
     * Get all the zones for the site.
     */
    public function zones(): HasMany
    {
        return $this->hasMany(Zone::class)->whereNull('parent_id');
    }

    /**
     * Get the managers for the site.
     */
    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('manager')
            ->where('manager', true)
            ->withTrashed();
    }

    /**
     * Get the users for the site.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withTimestamps()
            ->withTrashed();
    }
}
