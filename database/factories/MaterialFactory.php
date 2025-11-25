<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use XetaSuite\Models\Material;
use XetaSuite\Models\Site;
use XetaSuite\Models\User;
use XetaSuite\Models\Zone;
use XetaSuite\Enums\Materials\CleaningFrequency;

class MaterialFactory extends Factory
{
    protected $model = Material::class;

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Material $material) {
            $material->zone()->increment('material_count');
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'site_id' => null,
            'zone_id' => null,

            'created_by_id' => null,
            'created_by_name' => null,

            'name' => $this->faker->unique()->word(),
            'description' => $this->faker->optional()->sentence(),

            'qrcode_flash_count' => $this->faker->numberBetween(0, 10),
            'incident_count' => 0,
            'item_count' => 0,
            'maintenance_count' => 0,
            'cleaning_count' => 0,

            'cleaning_alert' => false,
            'cleaning_alert_email' => false,
            'cleaning_alert_frequency_repeatedly' => 0,
            'cleaning_alert_frequency_type' => $this->faker->randomElement(
                CleaningFrequency::cases()
            )->value,

            'last_cleaning_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'last_cleaning_alert_send_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
        ];
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

    /**
     * Defines the area and site explicitly.
     *
     * Example:
     *     $site = Site::factory()->create();
     *     $zone = Zone::factory()->forSite($site)->create();
     *     Material::factory()->inSiteAndZone($site, $zone)->create();
     *
     * @param Site|int $site
     * @param Zone|int $zone
     *
     * @return MaterialFactory
     */
    public function inSiteAndZone(Site|int $site, Zone|int $zone): static
    {
        return $this->state(fn () => [
            'site_id' => $site instanceof Site ? $site->id : $site,
            'zone_id' => $zone instanceof Zone ? $zone->id : $zone,
        ]);
    }

    /**
     * Defines a creator (User).
     *
     * @param User|int $user
     *
     * @return MaterialFactory
     */
    public function createdBy(User|int $user): static
    {
        return $this->state(fn () => [
            'created_by_id'   => $user instanceof User ? $user->id : $user
        ]);
    }
}
