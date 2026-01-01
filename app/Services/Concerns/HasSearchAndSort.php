<?php

declare(strict_types=1);

namespace XetaSuite\Services\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Provides reusable search and sort functionality for services.
 *
 * Usage in services:
 *
 * use HasSearchAndSort;
 *
 * // In your query method:
 * $query->when($filters['search'] ?? null, fn (Builder $q, string $s) =>
 *     $this->applySearchFilter($q, $s, ['name', 'description'])
 * )
 * ->when($filters['sort_by'] ?? null, fn (Builder $q, string $sortBy) =>
 *     $this->applySortFilter($q, $sortBy, $filters['sort_direction'] ?? 'asc', ['name', 'created_at'])
 * );
 */
trait HasSearchAndSort
{
    /**
     * Apply search filter across multiple columns using ILIKE (PostgreSQL).
     *
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  array<int, string>  $columns  Columns to search in
     * @param  array<string, string>  $relations  Relations to search in (relation => column)
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    protected function applySearchFilter(Builder $query, string $search, array $columns, array $relations = []): Builder
    {
        if (empty($columns) && empty($relations)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search, $columns, $relations) {
            // Search in direct columns
            foreach ($columns as $i => $column) {
                $method = $i === 0 ? 'where' : 'orWhere';
                $q->$method($column, 'ILIKE', "%{$search}%");
            }

            // Search in related models
            foreach ($relations as $relation => $column) {
                $q->orWhereHas($relation, fn (Builder $rq) => $rq->where($column, 'ILIKE', "%{$search}%"));
            }
        });
    }

    /**
     * Apply sorting with validation against allowed sort fields.
     *
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  array<int, string>  $allowedSorts  Allowed sort columns
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    protected function applySortFilter(
        Builder $query,
        string $sortBy,
        string $direction,
        array $allowedSorts,
        ?string $defaultSort = null,
        string $defaultDirection = 'asc'
    ): Builder {
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';

        if (in_array($sortBy, $allowedSorts, true)) {
            return $query->orderBy($sortBy, $direction);
        }

        // Apply default sort if provided and sortBy is invalid
        if ($defaultSort !== null) {
            return $query->orderBy($defaultSort, $defaultDirection);
        }

        return $query;
    }
}
