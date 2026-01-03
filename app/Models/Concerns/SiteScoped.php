<?php

declare(strict_types=1);

namespace XetaSuite\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait for models that are scoped to a specific site.
 *
 * Provides a convenient scope to filter records by the current user's site,
 * unless the user is on headquarters (which can see all sites).
 *
 * Usage in models:
 *
 * use SiteScoped;
 *
 * // In queries:
 * Material::query()->forCurrentSite()->get();
 *
 * The model must have a 'site_id' column.
 */
trait SiteScoped
{
    /**
     * Scope a query to only include records for the current user's site.
     * If the user is on headquarters, returns all records (no filtering).
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForCurrentSite(Builder $query): Builder
    {
        // If on headquarters, don't filter - HQ can see all sites
        if (isOnHeadquarters()) {
            return $query;
        }

        // Get the table name to avoid ambiguity in joins
        $table = $this->getTable();

        return $query->where("{$table}.site_id", session('current_site_id'));
    }

    /**
     * Scope a query to only include records for a specific site.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForSite(Builder $query, int $siteId): Builder
    {
        $table = $this->getTable();

        return $query->where("{$table}.site_id", $siteId);
    }

    /**
     * Check if this model belongs to the current user's site.
     */
    public function belongsToCurrentSite(): bool
    {
        if (isOnHeadquarters()) {
            return true;
        }

        return $this->site_id === session('current_site_id');
    }
}
