<?php

declare(strict_types=1);

namespace XetaSuite\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use XetaSuite\Services\GlobalSearchService;

class GlobalSearchController extends Controller
{
    public function __construct(
        private readonly GlobalSearchService $searchService
    ) {
    }

    /**
     * Perform a global search across all authorized resources.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
            'per_type' => 'sometimes|integer|min:1|max:20',
        ]);

        $results = $this->searchService->search(
            query: $request->input('q'),
            perType: (int) $request->input('per_type', 5)
        );

        return response()->json($results);
    }

    /**
     * Get the list of searchable types available for the current user.
     */
    public function types(): JsonResponse
    {
        return response()->json([
            'types' => $this->searchService->getAvailableTypes(),
        ]);
    }
}
