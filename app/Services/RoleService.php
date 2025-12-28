<?php

declare(strict_types=1);

namespace XetaSuite\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use XetaSuite\Models\Permission;
use XetaSuite\Models\Role;
use XetaSuite\Models\Site;

class RoleService
{
    /**
     * Get a paginated list of users assigned to a role.
     *
     * @param  array{search?: string, per_page?: int}  $filters
     * @return array{users: LengthAwarePaginator, site_names: array<int, string>}
     */
    public function getPaginatedRoleUsers(Role $role, array $filters = []): array
    {
        $query = $role->users();

        // Apply search filter
        if ($search = $filters['search'] ?? null) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'ILIKE', "%{$search}%")
                    ->orWhere('last_name', 'ILIKE', "%{$search}%")
                    ->orWhere('email', 'ILIKE', "%{$search}%");
            });
        }

        $users = $query->paginate($filters['per_page'] ?? 20);

        // Get all site IDs from the pivot and fetch site names
        $siteIds = $users->getCollection()->pluck('pivot.site_id')->unique()->filter();
        $siteNames = Site::whereIn('id', $siteIds)->pluck('name', 'id')->toArray();

        return [
            'users' => $users,
            'site_names' => $siteNames,
        ];
    }

    /**
     * Get a paginated list of roles with optional search and sorting.
     *
     * @param  array{search?: string, sort_by?: string, sort_direction?: string, per_page?: int}  $filters
     */
    public function getPaginatedRoles(array $filters = []): LengthAwarePaginator
    {
        $query = Role::query()
            ->where('guard_name', 'web')
            ->withCount(['permissions', 'users']);

        return $query
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $this->applySearch($query, $search))
            ->when(
                $filters['sort_by'] ?? null,
                fn (Builder $query, string $sortBy) => $this->applySorting($query, $sortBy, $filters['sort_direction'] ?? 'asc'),
                fn (Builder $query) => $query->orderBy('name', 'asc')
            )
            ->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Get all available permissions.
     * Limits to 30 permissions initially, returns all when searching.
     *
     * @param  string|null  $search  Optional search term
     */
    public function getAvailablePermissions(?string $search = null, int $limit = 30): Collection
    {
        $query = Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name');

        if ($search) {
            $query->where('name', 'ILIKE', "%{$search}%");
        } else {
            $query->limit($limit);
        }

        return $query->get(['id', 'name']);
    }

    /**
     * Apply search filter to query.
     */
    private function applySearch(Builder $query, string $search): Builder
    {
        return $query->where('name', 'ILIKE', "%{$search}%");
    }

    /**
     * Apply sorting to query.
     */
    private function applySorting(Builder $query, string $sortBy, string $direction): Builder
    {
        $allowedSorts = ['name', 'created_at', 'permissions_count', 'users_count'];
        $direction = in_array(strtolower($direction), ['asc', 'desc']) ? $direction : 'asc';

        if (in_array($sortBy, $allowedSorts)) {
            return $query->orderBy($sortBy, $direction);
        }

        return $query->orderBy('name', 'asc');
    }
}
