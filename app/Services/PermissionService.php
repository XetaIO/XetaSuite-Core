<?php

declare(strict_types=1);

namespace XetaSuite\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use XetaSuite\Models\Permission;
use XetaSuite\Models\Role;
use XetaSuite\Services\Concerns\HasSearchAndSort;

class PermissionService
{
    use HasSearchAndSort;

    private const SEARCH_COLUMNS = ['name'];

    private const ALLOWED_SORTS = ['name', 'created_at', 'roles_count'];

    /**
     * Get a paginated list of roles assigned to a permission.
     *
     * @param  array{search?: string, per_page?: int}  $filters
     */
    public function getPaginatedPermissionRoles(Permission $permission, array $filters = []): LengthAwarePaginator
    {
        $query = $permission->roles()->where('guard_name', 'web');

        // Apply search filter
        if ($search = $filters['search'] ?? null) {
            $query->where('name', 'ILIKE', "%{$search}%");
        }

        return $query->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Get a paginated list of permissions with optional search and sorting.
     *
     * @param  array{search?: string, sort_by?: string, sort_direction?: string, per_page?: int}  $filters
     */
    public function getPaginatedPermissions(array $filters = []): LengthAwarePaginator
    {
        $query = Permission::query()
            ->where('guard_name', 'web')
            ->withCount('roles');

        return $query
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $this->applySearchFilter($query, $search, self::SEARCH_COLUMNS))
            ->when(
                $filters['sort_by'] ?? null,
                fn (Builder $query, string $sortBy) => $this->applySortFilter($query, $sortBy, $filters['sort_direction'] ?? 'asc', self::ALLOWED_SORTS, 'name'),
                fn (Builder $query) => $query->orderBy('name', 'asc')
            )
            ->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Get all available roles.
     * Limits to 30 roles initially, returns all when searching.
     *
     * @param  string|null  $search  Optional search term
     * @return Collection<int, array{id: int, name: string}>
     */
    public function getAvailableRoles(?string $search = null, int $limit = 30): Collection
    {
        $query = Role::query()
            ->where('guard_name', 'web')
            ->orderBy('name');

        if ($search) {
            $query->where('name', 'ILIKE', "%{$search}%");
        } else {
            $query->limit($limit);
        }

        return $query->get(['id', 'name'])->map(fn (Role $role) => [
            'id' => $role->id,
            'name' => $role->name,
        ]);
    }
}
