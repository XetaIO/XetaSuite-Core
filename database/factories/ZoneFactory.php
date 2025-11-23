<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use XetaSuite\Models\Site;
use XetaSuite\Models\Zone;

class ZoneFactory extends Factory
{
    protected $model = Zone::class;

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Zone $zone) {
            $zone->site()->increment('zone_count');
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
            'site_id' => Site::factory()->create()->id,
            'parent_id' => null,
            'name' => $this->faker->unique()->word(),
            'allow_material' => $this->faker->boolean(),
            'material_count' => $this->faker->numberBetween(0, 50),
        ];
    }

    /**
     * Create a unique name for the site.
     *
     * @param int $siteId
     *
     * @return ZoneFactory
     */
    public function uniqueForSite(int $siteId): static
    {
        return $this->state(fn () => [
            'site_id' => $siteId,
            'name' => $this->faker->unique()->word(),
        ]);
    }

    /**
     * Creates a child zone of an existing zone.
     *
     * Example:
     *     $parent = Zone::factory()->create();
     *     Zone::factory()->withParent($parent)->create();
     *
     * @param Zone $parent
     *
     * @return ZoneFactory
     */
    public function withParent(Zone $parent): static
    {
        return $this->state(fn () => [
            'parent_id' => $parent->id,
            // Le parent et l’enfant partagent le même site.
            'site_id'   => $parent->site_id,
        ]);
    }

    /**
     * Creates a "root" zone (without a parent) in a given site.
     *
     * @param Site|int $site
     *
     * @return ZoneFactory
     */
    public function forSite(Site|int $site): static
    {
        return $this->state(fn () => [
            'site_id'   => $site instanceof Site ? $site->id : $site,
        ]);
    }
}
