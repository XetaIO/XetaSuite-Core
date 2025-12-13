<?php

declare(strict_types=1);

namespace XetaSuite\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use XetaSuite\Models\Setting;

class SettingsController extends Controller
{
    /**
     * Get all public settings for the frontend.
     * These are global settings (model_type and model_id are null).
     */
    public function index(): JsonResponse
    {
        // Get global settings only (no model context)
        $settings = Setting::query()
            ->whereNull('model_type')
            ->whereNull('model_id')
            ->pluck('value', 'key')
            ->toArray();

        return response()->json([
            'data' => $settings,
        ]);
    }
}
