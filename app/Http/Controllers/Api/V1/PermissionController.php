<?php

declare(strict_types=1);

namespace XetaSuite\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use XetaSuite\Actions\Permissions\CreatePermission;
use XetaSuite\Actions\Permissions\DeletePermission;
use XetaSuite\Actions\Permissions\UpdatePermission;
use XetaSuite\Http\Requests\V1\Permissions\StorePermissionRequest;
use XetaSuite\Http\Requests\V1\Permissions\UpdatePermissionRequest;
use XetaSuite\Http\Resources\V1\Permissions\PermissionDetailResource;
use XetaSuite\Http\Resources\V1\Permissions\PermissionResource;
use XetaSuite\Http\Resources\V1\Permissions\PermissionRoleResource;
use XetaSuite\Models\Permission;
use XetaSuite\Services\PermissionService;

class PermissionController extends Controller
{
    public function __construct(
        private readonly PermissionService $permissionService
    ) {
    }

    /**
     * Display a listing of permissions.
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Permission::class);

        $permissions = $this->permissionService->getPaginatedPermissions([
            'search' => request('search'),
            'sort_by' => request('sort_by'),
            'sort_direction' => request('sort_direction'),
        ]);

        return PermissionResource::collection($permissions);
    }

    /**
     * Display the specified permission.
     */
    public function show(Permission $permission): PermissionDetailResource
    {
        $this->authorize('view', $permission);

        $permission->load('roles');

        return new PermissionDetailResource($permission);
    }

    /**
     * Store a newly created permission.
     */
    public function store(StorePermissionRequest $request, CreatePermission $action): PermissionDetailResource
    {
        $permission = $action->handle($request->validated());

        return new PermissionDetailResource($permission);
    }

    /**
     * Update the specified permission.
     */
    public function update(UpdatePermissionRequest $request, Permission $permission, UpdatePermission $action): PermissionDetailResource
    {
        $permission = $action->handle($permission, $request->validated());

        return new PermissionDetailResource($permission);
    }

    /**
     * Remove the specified permission.
     */
    public function destroy(Permission $permission, DeletePermission $action): JsonResponse
    {
        $this->authorize('delete', $permission);

        $result = $action->handle($permission);

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
     * Get available roles for permission display.
     */
    public function availableRoles(): JsonResponse
    {
        $this->authorize('viewAny', Permission::class);

        $search = request('search');
        $limit = request('limit', 30);

        $roles = $this->permissionService->getAvailableRoles($search, (int) $limit);

        return response()->json([
            'data' => $roles,
        ]);
    }

    /**
     * Get roles assigned to a specific permission.
     */
    public function roles(Permission $permission): AnonymousResourceCollection
    {
        $this->authorize('view', $permission);

        $roles = $this->permissionService->getPaginatedPermissionRoles($permission, [
            'search' => request('search'),
        ]);

        return PermissionRoleResource::collection($roles);
    }
}
