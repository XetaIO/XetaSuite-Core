<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use XetaSuite\Enums\Materials\CleaningFrequency;
use XetaSuite\Models\Material;
use XetaSuite\Models\Site;
use XetaSuite\Models\User;
use XetaSuite\Models\Zone;

class MaterialFactory extends Factory
{
    protected $model = Material::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'site_id' => null,
            'zone_id' => null,

            'created_by_id' => null,
            'created_by_name' => null,

            'name' => \fake()->unique()->word(),
            'description' => \fake()->optional()->sentence(),

            'qrcode_flash_count' => \fake()->numberBetween(0, 10),
            'incident_count' => 0,
            'item_count' => 0,
            'maintenance_count' => 0,
            'cleaning_count' => 0,

            'cleaning_alert' => false,
            'cleaning_alert_email' => false,
            'cleaning_alert_frequency_repeatedly' => 0,
            'cleaning_alert_frequency_type' => \fake()->randomElement(
                CleaningFrequency::cases()
            )->value,

            'last_cleaning_at' => \fake()->optional()->dateTimeBetween('-1 year', 'now'),
            'last_cleaning_alert_send_at' => \fake()->optional()->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Defines a site (Site).
     *
     *
     * @return MaterialFactory
     */
    public function forSite(Site|int $site): static
    {
        return $this->state(fn () => [
            'site_id' => $site instanceof Site ? $site->id : $site,
        ]);
    }

    /**
     * Defines a zone (Zone).
     *
     *
     * @return MaterialFactory
     */
    public function forZone(Zone|int $zone): static
    {
        $zoneModel = $zone instanceof Zone ? $zone : Zone::find($zone);

        return $this->state(fn () => [
            'zone_id' => $zoneModel->id,
            'site_id' => $zoneModel->site_id,
        ]);
    }

    /**
     * Defines a creator (User).
     *
     *
     * @return MaterialFactory
     */
    public function createdBy(User|int $user): static
    {
        return $this->state(fn () => [
            'created_by_id' => $user instanceof User ? $user->id : $user,
        ]);
    }

    /**
     * Forces the presence of a cleaning alert (and its sending by email).
     *
     * @return MaterialFactory
     */
    public function withCleaningAlert(): static
    {
        return $this->state(fn () => [
            'cleaning_alert' => true,
            'cleaning_alert_email' => true,
        ]);
    }
}
