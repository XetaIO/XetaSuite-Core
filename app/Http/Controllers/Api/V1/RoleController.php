<?php

declare(strict_types=1);

namespace XetaSuite\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use XetaSuite\Actions\Roles\CreateRole;
use XetaSuite\Actions\Roles\DeleteRole;
use XetaSuite\Actions\Roles\UpdateRole;
use XetaSuite\Http\Requests\V1\Roles\StoreRoleRequest;
use XetaSuite\Http\Requests\V1\Roles\UpdateRoleRequest;
use XetaSuite\Http\Resources\V1\Roles\RoleDetailResource;
use XetaSuite\Http\Resources\V1\Roles\RoleResource;
use XetaSuite\Http\Resources\V1\Roles\RoleUserResource;
use XetaSuite\Models\Role;
use XetaSuite\Models\Site;
use XetaSuite\Services\RoleService;

class RoleController extends Controller
{
    public function __construct(
        private readonly RoleService $roleService
    ) {
    }

    /**
     * Display a listing of roles.
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Role::class);

        $roles = $this->roleService->getPaginatedRoles([
            'search' => request('search'),
            'sort_by' => request('sort_by'),
            'sort_direction' => request('sort_direction'),
        ]);

        return RoleResource::collection($roles);
    }

    /**
     * Store a newly created role.
     */
    public function store(StoreRoleRequest $request, CreateRole $action): RoleDetailResource
    {
        return new RoleDetailResource($action->handle($request->validated()));
    }

    /**
     * Display the specified role.
     */
    public function show(Role $role): RoleDetailResource
    {
        $this->authorize('view', $role);

        $role->load('permissions');

        return new RoleDetailResource($role);
    }

    /**
     * Update the specified role.
     */
    public function update(UpdateRoleRequest $request, Role $role, UpdateRole $action): RoleDetailResource
    {
        $role = $action->handle($role, $request->validated());

        return new RoleDetailResource($role);
    }

    /**
     * Delete the specified role.
     */
    public function destroy(Role $role, DeleteRole $action): JsonResponse
    {
        $this->authorize('delete', $role);

        $result = $action->handle($role);

        if (! $result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], 422);
        }

        return response()->json([
            'message' => $result['message'],
        ]);
    }

    /**
     * Get available permissions for role creation/update.
     */
    public function availablePermissions(): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        $search = request('search');
        $limit = request('limit', 30);

        $permissions = $this->roleService->getAvailablePermissions($search, (int) $limit);

        return response()->json([
            'data' => $permissions,
        ]);
    }

    /**
     * Get users assigned to a specific role.
     */
    public function users(Role $role): AnonymousResourceCollection
    {
        $this->authorize('view', $role);

        $result = $this->roleService->getPaginatedRoleUsers($role, [
            'search' => request('search'),
        ]);

        // Set site names cache for the resource
        RoleUserResource::withSiteNames($result['site_names']);

        return RoleUserResource::collection($result['users']);
    }
}
