<?php

declare(strict_types=1);

namespace XetaSuite\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use XetaSuite\Actions\Settings\UpdateSetting;
use XetaSuite\Http\Requests\V1\Settings\UpdateSettingRequest;
use XetaSuite\Http\Resources\V1\Settings\SettingResource;
use XetaSuite\Models\Setting;

class SettingsController extends Controller
{
    /**
     * Get all public settings for the frontend (simple key-value format).
     * These are global settings (model_type and model_id are null).
     * This is a public endpoint for fetching settings values.
     */
    public function public(): JsonResponse
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

    /**
     * Get all settings for management (full resource format).
     * Requires viewAny permission.
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Setting::class);

        $settings = Setting::query()
            ->whereNull('model_type')
            ->whereNull('model_id')
            ->with('updater')
            ->orderBy('key')
            ->get();

        return SettingResource::collection($settings);
    }

    /**
     * Display the specified setting.
     */
    public function show(Setting $setting): SettingResource
    {
        $this->authorize('view', $setting);

        $setting->load('updater');

        return new SettingResource($setting);
    }

    /**
     * Update the specified setting.
     */
    public function update(
        UpdateSettingRequest $request,
        Setting $setting,
        UpdateSetting $action
    ): SettingResource {
        $setting = $action->handle($setting, $request->user(), $request->validated());
        $setting->load('updater');

        return new SettingResource($setting);
    }
}
