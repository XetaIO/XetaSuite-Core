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
use XetaSuite\Http\Resources\V1\Sites\SiteResource;
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
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Site::class);

        $sites = $this->siteService->getPaginatedSites([
            'search' => request('search'),
            'sort_by' => request('sort_by'),
            'sort_direction' => request('sort_direction'),
        ]);

        return SiteResource::collection($sites);
    }

    /**
     * Store a newly created site.
     */
    public function store(StoreSiteRequest $request, CreateSite $action): JsonResponse|SiteDetailResource
    {
        $result = $action->handle($request->validated());

        if (! $result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], 422);
        }

        return new SiteDetailResource($result['site']->loadCount(['zones', 'users']));
    }

    /**
     * Display the specified site.
     */
    public function show(Site $site): SiteDetailResource
    {
        $this->authorize('view', $site);

        $site->loadCount(['zones', 'users']);

        return new SiteDetailResource($site);
    }

    /**
     * Update the specified site.
     */
    public function update(UpdateSiteRequest $request, Site $site, UpdateSite $action): JsonResponse|SiteDetailResource
    {
        $result = $action->handle($site, $request->validated());

        if (! $result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], 422);
        }

        return new SiteDetailResource($result['site']->loadCount(['zones', 'users']));
    }

    /**
     * Remove the specified site.
     */
    public function destroy(Site $site, DeleteSite $action): JsonResponse
    {
        $this->authorize('delete', $site);

        $result = $action->handle($site);

        if (! $result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], 422);
        }

        return response()->json(null, 204);
    }
}
