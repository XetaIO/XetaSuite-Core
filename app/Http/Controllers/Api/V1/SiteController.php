<?php

declare(strict_types=1);

namespace XetaSuite\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use XetaSuite\Models\Site;

/**
 * Endpoint                                   Resource                              Données retournées
 *
 * GET /api/items                        ItemResource                      Minimalist list
 * GET /api/items/{id}                ItemDetailResource            All the details
 * GET /api/dashboard/items    ItemDashboardResource    Alerts + key stats
 */
class ItemController extends Controller
{
    public function destroy(Site $site): JsonResponse
    {
        if ($site->zones()->doesntExist()) {
            return response()->json([
                'message' => 'This site cannot be deleted because it contains Zones.',
            ], 422);
        }

        $site->delete();

        return response()->json(null, 204);
    }
}
