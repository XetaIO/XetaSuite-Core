<?php

declare(strict_types=1);

namespace XetaSuite\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;

class SetCurrentPermissionsAndRoles
{
    /**
     * Configures Spatie Permission team context for the current site.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (auth()->check()) {
            // Use cached value from session
            $siteId = session('current_site_id');

            if ($siteId) {
                app(PermissionRegistrar::class)->setPermissionsTeamId((int) $siteId);

                // Unset cached model relations so new team relations will get reloaded
                auth()->user()->unsetRelation('roles');
                auth()->user()->unsetRelation('permissions');
            }
        }

        return $next($request);
    }
}
