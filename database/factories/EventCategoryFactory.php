<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use XetaSuite\Models\EventCategory;
use XetaSuite\Models\Site;

/**
 * @extends Factory<EventCategory>
 */
class EventCategoryFactory extends Factory
{
    protected $model = EventCategory::class;

    public function definition(): array
    {
        return [
            'site_id' => null,
            'name' => fake()->unique()->word(),
            'color' => fake()->hexColor(),
            'description' => fake()->optional()->sentence(),
        ];
    }

    public function forSite(Site|int $site): static
    {
        return $this->state(fn () => [
            'site_id' => $site instanceof Site ? $site->id : $site,
        ]);
    }

    public function withColor(string $color): static
    {
        return $this->state(fn () => [
            'color' => $color,
        ]);
    }
}
