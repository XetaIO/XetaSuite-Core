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
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'site_id' => null,
            'parent_id' => null,
            'name' => $this->faker->unique()->word(),
            'allow_material' => false,
            'material_count' => 0,
        ];
    }

    /**
     * Create a unique name for the site.
     *
     *
     * @return ZoneFactory
     */
    public function uniqueForSite(Site|int $site): static
    {
        return $this->state(fn () => [
            'site_id' => $site instanceof Site ? $site->id : $site,
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
     *
     * @return ZoneFactory
     */
    public function withParent(Zone $parent): static
    {
        return $this->state(fn () => [
            'parent_id' => $parent->id,
            // Parent and child share the same site.
            'site_id' => $parent->site_id,
        ]);
    }

    /**
     * Enables the ability to add materials to this zone.
     *
     * @return ZoneFactory
     */
    public function withAllowMaterial(): static
    {
        return $this->state(fn () => [
            'allow_material' => true,
        ]);
    }

    /**
     * Creates a "root" zone (without a parent) in a given site.
     *
     *
     * @return ZoneFactory
     */
    public function forSite(Site|int $site): static
    {
        return $this->state(fn () => [
            'site_id' => $site instanceof Site ? $site->id : $site,
        ]);
    }
}
