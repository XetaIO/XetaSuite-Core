<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use XetaSuite\Models\Site;

class SiteFactory extends Factory
{
    protected $model = Site::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => \fake()->unique()->company(),

            'is_headquarters' => false,

            'zone_count' => 0,

            'email' => \fake()->optional()->companyEmail(),
            'office_phone' => \fake()->optional()->phoneNumber(),
            'cell_phone' => \fake()->optional()->phoneNumber(),

            'address' => \fake()->optional()->streetAddress(),
            'zip_code' => \fake()->optional()->postcode(),
            'city' => \fake()->optional()->city(),
            'country' => \fake()->optional()->country(),
        ];
    }

    /**
     * State to create the head office.
     *
     * @return SiteFactory
     */
    public function headquarters(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_headquarters' => true,
        ]);
    }
}
