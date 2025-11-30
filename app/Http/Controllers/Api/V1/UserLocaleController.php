<?php

declare(strict_types=1);

namespace XetaSuite\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserLocaleController extends Controller
{
    /**
     * Update the authenticated user's locale preference.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', 'in:fr,en'],
        ]);

        $request->user()->update([
            'locale' => $validated['locale'],
        ]);

        // Also set locale for current request
        app()->setLocale($validated['locale']);

        return response()->json([
            'message' => __('user.locale_updated'),
            'locale' => $validated['locale'],
        ]);
    }
}
