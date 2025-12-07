<?php

declare(strict_types=1);

namespace XetaSuite\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use XetaSuite\Actions\Sites\CreateSite;
use XetaSuite\Actions\Sites\DeleteSite;
use XetaSuite\Actions\Sites\UpdateSite;
use XetaSuite\Http\Requests\V1\Sites\StoreSiteRequest;
use XetaSuite\Http\Requests\V1\Sites\UpdateSiteRequest;
use XetaSuite\Http\Resources\V1\Sites\SiteDetailResource;
use XetaSuite\Http\Resources\V1\Sites\SiteUserResource;
use XetaSuite\Models\Site;
use XetaSuite\Services\SiteService;

class SiteController extends Controller
{
    public function __construct(
        private readonly SiteService $siteService
    ) {
    }

    /**
     * Display a listing of sites.
     * Only shows sites the user has access to.
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Site::class);

        $sites = $this->siteService->getPaginatedSites([
            'search' => request('search'),
            'sort_by' => request('sort_by'),
            'sort_direction' => request('sort_direction'),
        ]);

        return SiteDetailResource::collection($sites);
    }

    /**
     * Store a newly created site.
     *
     * @param  StoreSiteRequest  $request  The incoming request.
     * @param  CreateSite  $action  The action to create the site.
     */
    public function store(StoreSiteRequest $request, CreateSite $action): JsonResponse|SiteDetailResource
    {
        $result = $action->handle($request->validated());

        if (! $result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], 422);
        }

        return new SiteDetailResource($result['site']->load(['managers', 'users'])->loadCount(['zones', 'users']));
    }

    /**
     * Display the specified site.
     *
     * @param  Site  $site  The site to display.
     */
    public function show(Site $site): SiteDetailResource
    {
        $this->authorize('view', $site);

        $site->load(['managers', 'users'])->loadCount(['zones', 'users']);

        return new SiteDetailResource($site);
    }

    /**
     * Update the specified site.
     *
     * @param  UpdateSiteRequest  $request  The incoming request.
     * @param  Site  $site  The site to update.
     * @param  UpdateSite  $action  The action to update the site.
     */
    public function update(UpdateSiteRequest $request, Site $site, UpdateSite $action): JsonResponse|SiteDetailResource
    {
        $result = $action->handle($site, $request->validated());

        if (! $result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], 422);
        }

        return new SiteDetailResource($result['site']->load(['managers', 'users'])->loadCount(['zones', 'users']));
    }

    /**
     * Delete the specified site.
     *
     * @param  Site  $site  The site to delete.
     * @param  DeleteSite  $action  The action to delete the site.
     */
    public function destroy(Site $site, DeleteSite $action): JsonResponse
    {
        $this->authorize('delete', $site);

        $result = $action->handle($site);

        if (! $result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], 409);
        }

        return response()->json(null, 204);
    }

    /**
     * Get users for a site (for manager selection).
     *
     * @param  Site  $site  The site to get users for.
     */
    public function users(Site $site): AnonymousResourceCollection
    {
        $this->authorize('view', $site);

        // Set site ID for role scoping in resource
        SiteUserResource::$siteId = $site->id;

        $query = $site->users()
            ->orderBy('last_name')
            ->orderBy('first_name');

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'ILIKE', "%{$search}%")
                    ->orWhere('last_name', 'ILIKE', "%{$search}%")
                    ->orWhere('username', 'ILIKE', "%{$search}%");
            });
        }

        return SiteUserResource::collection($query->limit(15)->get());
    }

    /**
     * Get members for a site (with pagination).
     *
     * @param  Site  $site  The site to get members for.
     */
    public function members(Site $site): AnonymousResourceCollection
    {
        $this->authorize('view', $site);

        // Set site ID for role scoping in resource
        SiteUserResource::$siteId = $site->id;

        $query = $site->users()
            ->orderBy('last_name')
            ->orderBy('first_name');

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'ILIKE', "%{$search}%")
                    ->orWhere('last_name', 'ILIKE', "%{$search}%")
                    ->orWhere('email', 'ILIKE', "%{$search}%");
            });
        }

        return SiteUserResource::collection($query->paginate(10));
    }
}
