<?php

declare(strict_types=1);

namespace XetaSuite\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use XetaSuite\Http\Resources\V1\Users\UserDetailResource;
use XetaSuite\Models\Site;

class UserSiteController extends Controller
{
    /**
     * Update the authenticated user's current site.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'site_id' => [
                'required',
                'integer',
                Rule::exists(Site::class, 'id'),
                Rule::in($user->sites->pluck('id')->toArray()),
            ],
        ]);

        $user->update([
            'current_site_id' => $validated['site_id'],
        ]);

        // Update session with new site info
        $site = Site::find($validated['site_id']);
        session([
            'current_site_id' => (int) $validated['site_id'],
            'is_on_headquarters' => $site->is_headquarters,
        ]);

        return response()->json([
            'message' => __('user.site_updated'),
            'user' => new UserDetailResource($user->fresh()),
        ]);
    }
}
