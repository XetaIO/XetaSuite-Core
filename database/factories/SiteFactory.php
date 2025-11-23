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
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company(),

            'is_headquarters' => false,

            'zone_count' => 0,

            'email' => $this->faker->optional()->companyEmail(),
            'office_phone' => $this->faker->optional()->phoneNumber(),
            'cell_phone' => $this->faker->optional()->phoneNumber(),

            'address_line_1' => $this->faker->optional()->streetAddress(),
            'address_line_2' => $this->faker->optional()->secondaryAddress(),
            'postal_code' => $this->faker->optional()->postcode(),
            'city' => $this->faker->optional()->city(),
            'country' => $this->faker->optional()->country(),
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
