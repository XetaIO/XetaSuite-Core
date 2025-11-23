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
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        $site = Site::factory()->create();
        $zone = Zone::factory()
            ->forSite($site)
            ->create();

        return [
            'site_id' => $site->id,
            'zone_id' => $zone->id,

            'created_by_id' => User::factory()->create()->id,
            'created_by_name' => null,

            'name' => $this->faker->unique()->word(),
            'description' => $this->faker->optional()->sentence(),

            'qrcode_flash_count' => $this->faker->numberBetween(0, 10),
            'incident_count' => $this->faker->numberBetween(0, 5),
            'item_count' => $this->faker->numberBetween(0, 20),
            'maintenance_count' => $this->faker->numberBetween(0, 5),
            'cleaning_count' => $this->faker->numberBetween(0, 3),

            'cleaning_alert' => $this->faker->boolean(),
            'cleaning_alert_email' => $this->faker->boolean(),
            'cleaning_alert_frequency_repeatedly' => $this->faker->numberBetween(1, 7),

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
        $siteId = $site instanceof Site ? $site->id : $site;
        $zoneId = $zone instanceof Zone ? $zone->id : $zone;

        return $this->state(fn () => [
            'site_id' => $siteId,
            'zone_id' => $zoneId,
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
