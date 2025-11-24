<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use XetaSuite\Models\Supplier;
use XetaSuite\Models\User;

class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company(),
            'description' => $this->faker->optional()->sentence(),
            'created_by_id' => User::factory()->create()->id,
        ];
    }

    /**
     * Define a specific creator.
     *
     * Example:
     *     Supplier::factory()->createdBy($user)->create();
     *
     * @param User|int $user
     *
     * @return SupplierFactory
     */
    public function createdBy(User|int $user): static
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $this->state(fn () => [
            'created_by_id' => $userId,
        ]);
    }
}
