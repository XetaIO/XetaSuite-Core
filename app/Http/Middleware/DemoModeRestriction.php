<?php

declare(strict_types=1);

namespace XetaSuite\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to restrict destructive actions in demo mode.
 *
 * When DEMO_MODE is enabled, this middleware prevents:
 * - Deleting users, sites, or critical data
 * - Changing passwords of demo accounts
 * - Modifying certain protected resources
 */
class DemoModeRestriction
{
    /**
     * Routes/actions that are blocked in demo mode.
     */
    private const BLOCKED_PATTERNS = [
        // User deletion and password changes
        'DELETE:api\/v1\/users\/[^\/]+',
        'PUT:api\/v1\/user\/password',

        // Site deletion
        'DELETE:api\/v1\/sites\/[^\/]+',
        // Bulk deletions
        'DELETE:api\/v1\/notifications',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isDemoMode()) {
            return $next($request);
        }

        if ($this->isBlockedAction($request)) {
            return response()->json([
                'message' => __('demo.action_blocked'),
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }

    /**
     * Check if demo mode is enabled.
     */
    private function isDemoMode(): bool
    {
        return config('app.demo_mode', false);
    }

    /**
     * Check if the current request matches a blocked pattern.
     */
    private function isBlockedAction(Request $request): bool
    {
        $method = $request->method();
        $path = $request->path();

        foreach (self::BLOCKED_PATTERNS as $pattern) {
            [$blockedMethod, $blockedPath] = explode(':', $pattern, 2);

            if ($method !== $blockedMethod) {
                continue;
            }

            if (preg_match("/^{$blockedPath}$/", $path)) {
                return true;
            }
        }

        return false;
    }
}
