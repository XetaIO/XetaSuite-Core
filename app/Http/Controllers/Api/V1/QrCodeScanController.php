<?php

declare(strict_types=1);

namespace XetaSuite\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use XetaSuite\Models\Item;
use XetaSuite\Models\Material;

class QrCodeScanController extends Controller
{
    /**
     * Get scanned material information.
     * Returns material details and available actions based on user permissions.
     */
    public function material(Material $material): JsonResponse
    {
        $this->authorize('scanQrCode', Material::class);

        $user = request()->user();
        $siteId = $material->zone?->site_id;

        // Check if user has access to the material's site
        $userHasSiteAccess = $user->sites()->where('sites.id', $siteId)->exists();

        if (! $userHasSiteAccess) {
            return response()->json([
                'error' => 'no_site_access',
                'message' => 'You do not have access to this material\'s site.',
            ], 403);
        }

        // Load relationships
        $material->load(['zone.site']);

        // Determine available actions based on permissions
        $availableActions = [];

        if ($user->can('cleaning.create')) {
            $availableActions[] = 'cleaning';
        }
        if ($user->can('maintenance.create')) {
            $availableActions[] = 'maintenance';
        }
        if ($user->can('incident.create')) {
            $availableActions[] = 'incident';
        }

        return response()->json([
            'data' => [
                'type' => 'material',
                'id' => $material->id,
                'name' => $material->name,
                'description' => $material->description,
                'site' => [
                    'id' => $material->zone?->site?->id,
                    'name' => $material->zone?->site?->name,
                ],
                'zone' => [
                    'id' => $material->zone?->id,
                    'name' => $material->zone?->name,
                ],
                'available_actions' => $availableActions,
            ],
        ]);
    }

    /**
     * Get scanned item information.
     * Returns item details and available actions based on user permissions.
     */
    public function item(Item $item): JsonResponse
    {
        $this->authorize('scanQrCode', Item::class);

        $user = request()->user();
        $siteId = $item->site_id;

        // Check if user has access to the item's site
        $userHasSiteAccess = $user->sites()->where('sites.id', $siteId)->exists();

        if (! $userHasSiteAccess) {
            return response()->json([
                'error' => 'no_site_access',
                'message' => 'You do not have access to this item\'s site.',
            ], 403);
        }

        // Load relationships
        $item->load(['site']);

        // Determine available actions based on permissions
        $availableActions = [];

        if ($user->can('item-movement.create')) {
            $availableActions[] = 'entry';
            $availableActions[] = 'exit';
        }

        return response()->json([
            'data' => [
                'type' => 'item',
                'id' => $item->id,
                'name' => $item->name,
                'reference' => $item->reference,
                'description' => $item->description,
                'current_stock' => $item->current_stock,
                'site' => [
                    'id' => $item->site?->id,
                    'name' => $item->site?->name,
                ],
                'available_actions' => $availableActions,
            ],
        ]);
    }
}
