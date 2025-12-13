<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use XetaSuite\Models\Setting;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = require database_path('seeders/data/settings.php');

        // Create / sync settings (global, not team-specific)
        foreach ($settings as $key => $value) {
            // Assuming you have a Setting model to handle settings
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }
}
