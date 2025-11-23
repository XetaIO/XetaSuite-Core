<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use XetaSuite\Models\Company;
use XetaSuite\Models\User;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'created_by_id' => User::factory()->create()->id,
            'created_by_name' => null,
            'name' => $this->faker->unique()->company(),
            'description' => $this->faker->optional(0.7)->paragraph(),
        ];
    }

    /**
     * Defines the creator.
     *
     * @param User|int $user
     *
     * @return CompanyFactory
     */
    public function createdBy(User|int $user): static
    {
        return $this->state(fn () => [
            'created_by_id'   => $user instanceof User ? $user->id : $user
        ]);
    }
}
