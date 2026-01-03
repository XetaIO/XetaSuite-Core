<?php

declare(strict_types=1);

namespace XetaSuite\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use XetaSuite\Services\Concerns\HasSearchAndSort;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use XetaSuite\Models\Site;
use XetaSuite\Models\User;

class UserService
{
    use HasSearchAndSort;

    private const SEARCH_COLUMNS = ['first_name', 'last_name', 'email', 'username'];

    private const ALLOWED_SORTS = ['first_name', 'last_name', 'email', 'username', 'created_at'];

    /**
     * Get a paginated list of users with optional search and sorting.
     * Includes soft-deleted users and their roles on the current site.
     *
     * @param  array{search?: string, sort_by?: string, sort_direction?: string, per_page?: int, site_id?: int}  $filters
     */
    public function getPaginatedUsers(array $filters = []): LengthAwarePaginator
    {
        return User::query()
            ->withTrashed()
            ->with(['sites', 'deleter', 'roles'])
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $this->applySearchFilter($query, $search, self::SEARCH_COLUMNS))
            ->when($filters['site_id'] ?? null, fn (Builder $query, int $siteId) => $query->whereHas('sites', fn (Builder $q) => $q->where('sites.id', $siteId)))
            ->when(
                $filters['sort_by'] ?? null,
                fn (Builder $query, string $sortBy) => $this->applySortFilter($query, $sortBy, $filters['sort_direction'] ?? 'asc', self::ALLOWED_SORTS),
                fn (Builder $query) => $query->orderBy('last_name')->orderBy('first_name')
            )
            ->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Get available sites for user assignment.
     */
    public function getAvailableSites(?string $search = null): Collection
    {
        return Site::query()
            ->select(['id', 'name', 'is_headquarters'])
            ->when($search, fn (Builder $query) => $query->where('name', 'ILIKE', "%{$search}%"))
            ->orderBy('name')
            ->limit(50)
            ->get();
    }

    /**
     * Get available roles for assignment.
     */
    public function getAvailableRoles(?string $search = null): Collection
    {
        return Role::query()
            ->select(['id', 'name', 'site_id'])
            ->when($search, fn (Builder $query) => $query->where('name', 'ILIKE', "%{$search}%"))
            ->orderBy('name')
            ->limit(50)
            ->get();
    }

    /**
     * Get available permissions for direct assignment.
     */
    public function getAvailablePermissions(?string $search = null): Collection
    {
        return Permission::query()
            ->select(['id', 'name'])
            ->when($search, fn (Builder $query) => $query->where('name', 'ILIKE', "%{$search}%"))
            ->orderBy('name')
            ->limit(100)
            ->get();
    }

    /**
     * Get user's roles per site.
     */
    public function getUserRolesPerSite(User $user): array
    {
        $result = [];
        $teamId = getPermissionsTeamId();

        foreach ($user->sites as $site) {
            setPermissionsTeamId($site->id);
            $user->unsetRelation('roles');
            $result[$site->id] = [
                'site' => [
                    'id' => $site->id,
                    'name' => $site->name,
                    'is_headquarters' => $site->is_headquarters,
                ],
                'roles' => $user->roles->pluck('name')->toArray(),
            ];
        }

        setPermissionsTeamId($teamId);
        $user->unsetRelation('roles');

        return array_values($result);
    }

    /**
     * Get user's direct permissions per site.
     */
    public function getUserPermissionsPerSite(User $user): array
    {
        $result = [];
        $teamId = getPermissionsTeamId();

        foreach ($user->sites as $site) {
            setPermissionsTeamId($site->id);
            $user->unsetRelation('permissions');
            $result[$site->id] = [
                'site' => [
                    'id' => $site->id,
                    'name' => $site->name,
                ],
                'permissions' => $user->getDirectPermissions()->pluck('name')->toArray(),
            ];
        }

        setPermissionsTeamId($teamId);
        $user->unsetRelation('permissions');

        return array_values($result);
    }

    /**
     * Get user cleanings with pagination.
     *
     * @param  array{page?: int, per_page?: int}  $filters
     */
    public function getUserCleanings(User $user, array $filters = []): LengthAwarePaginator
    {
        return $user->cleanings()
            ->with(['material', 'site', 'creator'])
            ->orderByDesc('created_at')
            ->paginate($filters['per_page'] ?? 10);
    }

    /**
     * Get user maintenances with pagination.
     *
     * @param  array{page?: int, per_page?: int}  $filters
     */
    public function getUserMaintenances(User $user, array $filters = []): LengthAwarePaginator
    {
        return $user->maintenances()
            ->with(['material', 'site', 'creator'])
            ->orderByDesc('created_at')
            ->paginate($filters['per_page'] ?? 10);
    }

    /**
     * Get user incidents with pagination.
     *
     * @param  array{page?: int, per_page?: int}  $filters
     */
    public function getUserIncidents(User $user, array $filters = []): LengthAwarePaginator
    {
        return $user->incidents()
            ->with(['material', 'site', 'reporter'])
            ->orderByDesc('created_at')
            ->paginate($filters['per_page'] ?? 10);
    }

}
