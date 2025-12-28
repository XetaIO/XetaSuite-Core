<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Settings;

use XetaSuite\Models\Setting;
use XetaSuite\Models\User;
use XetaSuite\Settings\Settings as SettingsCache;

class UpdateSetting
{
    public function __construct(
        protected SettingsCache $settingsCache,
    ) {
    }

    /**
     * Update the setting with the given data.
     *
     * @param  Setting  $setting  The setting to update.
     * @param  User  $user  The user performing the update.
     * @param  array  $data  The validated data.
     */
    public function handle(Setting $setting, User $user, array $data): Setting
    {
        // Clear cache for this setting key (with null context for global settings)
        $this->settingsCache
            ->withoutContext()
            ->remove($setting->key);

        // Update the setting
        $setting->update([
            'value' => $data['value'],
            'updated_by_id' => $user->id,
        ]);

        return $setting->refresh();
    }
}
