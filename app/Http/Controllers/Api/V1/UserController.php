<?php

declare(strict_types=1);

namespace XetaSuite\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use XetaSuite\Actions\Users\CreateUser;
use XetaSuite\Actions\Users\DeleteUser;
use XetaSuite\Actions\Users\RestoreUser;
use XetaSuite\Actions\Users\UpdateUser;
use XetaSuite\Http\Requests\V1\Users\StoreUserRequest;
use XetaSuite\Http\Requests\V1\Users\UpdateUserRequest;
use XetaSuite\Http\Resources\V1\Cleanings\CleaningResource;
use XetaSuite\Http\Resources\V1\Incidents\IncidentResource;
use XetaSuite\Http\Resources\V1\Maintenances\MaintenanceResource;
use XetaSuite\Http\Resources\V1\Users\UserDetailResource;
use XetaSuite\Http\Resources\V1\Users\UserResource;
use XetaSuite\Models\User;
use XetaSuite\Services\UserService;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService
    ) {
    }

    /**
     * Display a listing of users.
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', User::class);

        $users = $this->userService->getPaginatedUsers([
            'search' => request('search'),
            'sort_by' => request('sort_by'),
            'sort_direction' => request('sort_direction'),
            'site_id' => request('site_id'),
        ]);

        return UserResource::collection($users);
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request, CreateUser $action): JsonResponse
    {
        $user = $action->handle($request->validated());

        return (new UserDetailResource($user))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): UserDetailResource
    {
        $this->authorize('view', $user);

        $user->load(['sites', 'deleter']);

        return new UserDetailResource($user);
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user, UpdateUser $action): UserDetailResource
    {
        $user = $action->handle($user, $request->validated());

        return new UserDetailResource($user);
    }

    /**
     * Delete the specified user.
     */
    public function destroy(User $user, DeleteUser $action): JsonResponse
    {
        $this->authorize('delete', $user);

        $result = $action->handle($user, request()->user());

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
     * Restore a soft-deleted user.
     */
    public function restore(User $user, RestoreUser $action): JsonResponse
    {
        $this->authorize('restore', $user);

        $result = $action->handle($user);

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
     * Get available sites for user assignment.
     */
    public function availableSites(): JsonResponse
    {
        $sites = $this->userService->getAvailableSites(request('search'));

        return response()->json([
            'data' => $sites,
        ]);
    }

    /**
     * Get available roles for assignment.
     */
    public function availableRoles(): JsonResponse
    {
        $roles = $this->userService->getAvailableRoles(request('search'));

        return response()->json([
            'data' => $roles,
        ]);
    }

    /**
     * Get available permissions for direct assignment.
     */
    public function availablePermissions(): JsonResponse
    {
        $this->authorize('assignDirectPermission', User::class);

        $permissions = $this->userService->getAvailablePermissions(request('search'));

        return response()->json([
            'data' => $permissions,
        ]);
    }

    /**
     * Get user's roles and permissions per site.
     */
    public function rolesPerSite(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        $rolesPerSite = $this->userService->getUserRolesPerSite($user);
        $permissionsPerSite = $this->userService->getUserPermissionsPerSite($user);

        return response()->json([
            'data' => [
                'roles_per_site' => $rolesPerSite,
                'permissions_per_site' => $permissionsPerSite,
            ],
        ]);
    }

    /**
     * Get user's cleanings.
     */
    public function cleanings(User $user): AnonymousResourceCollection
    {
        $this->authorize('view', $user);

        $cleanings = $this->userService->getUserCleanings($user, [
            'per_page' => request('per_page', 10),
        ]);

        return CleaningResource::collection($cleanings);
    }

    /**
     * Get user's maintenances.
     */
    public function maintenances(User $user): AnonymousResourceCollection
    {
        $this->authorize('view', $user);

        $maintenances = $this->userService->getUserMaintenances($user, [
            'per_page' => request('per_page', 10),
        ]);

        return MaintenanceResource::collection($maintenances);
    }

    /**
     * Get user's incidents.
     */
    public function incidents(User $user): AnonymousResourceCollection
    {
        $this->authorize('view', $user);

        $incidents = $this->userService->getUserIncidents($user, [
            'per_page' => request('per_page', 10),
        ]);

        return IncidentResource::collection($incidents);
    }
}
