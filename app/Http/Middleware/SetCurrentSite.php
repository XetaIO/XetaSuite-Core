<?php

declare(strict_types=1);

namespace XetaSuite\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use XetaSuite\Models\Site;

class SetCurrentSite
{
    /**
     * Sets the current site ID for the authenticated user.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (auth()->check()) {
            $user = auth()->user();
            $siteId = $user->current_site_id ?? $user->getFirstSiteId();

            // Cache headquarters status for this request (avoids N+1 in policies)
            $isHeadquarters = Site::where('id', $siteId)
                ->where('is_headquarters', true)
                ->exists();

            // Store in session (works for web and stateful SPA API via Sanctum)
            session([
                'current_site_id' => (int) $siteId,
                'is_on_headquarters' => $isHeadquarters,
            ]);
        }

        return $next($request);
    }
}
