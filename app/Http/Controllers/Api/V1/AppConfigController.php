<?php

declare(strict_types=1);

namespace XetaSuite\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;

/**
 * Returns public application configuration.
 * This endpoint is NOT authenticated - it provides config needed before login.
 */
class AppConfigController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'demo_mode' => config('app.demo_mode', false),
            'app_name' => config('app.name'),
        ]);
    }
}
