<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use XetaSuite\Enums\Companies\CompanyType;
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
            'created_by_id' => null,
            'created_by_name' => null,
            'name' => \fake()->unique()->company(),
            'description' => \fake()->optional(0.7)->paragraph(),
            'types' => [CompanyType::MAINTENANCE_PROVIDER->value],
            'email' => \fake()->optional(0.7)->companyEmail(),
            'phone' => \fake()->optional(0.5)->phoneNumber(),
            'address' => \fake()->optional(0.5)->address(),
            'maintenance_count' => 0,
            'item_count' => 0,
        ];
    }

    /**
     * Defines the creator.
     */
    public function createdBy(User|int $user): static
    {
        $userModel = $user instanceof User ? $user : User::findOrFail($user);

        return $this->state(fn () => [
            'created_by_id' => $userModel->id,
            'created_by_name' => $userModel->full_name,
        ]);
    }

    /**
     * Set the company as an item provider.
     */
    public function asItemProvider(): static
    {
        return $this->state(fn () => [
            'types' => [CompanyType::ITEM_PROVIDER->value],
        ]);
    }

    /**
     * Set the company as a maintenance provider.
     */
    public function asMaintenanceProvider(): static
    {
        return $this->state(fn () => [
            'types' => [CompanyType::MAINTENANCE_PROVIDER->value],
        ]);
    }

    /**
     * Set the company as both item and maintenance provider.
     */
    public function asBothProviders(): static
    {
        return $this->state(fn () => [
            'types' => [
                CompanyType::ITEM_PROVIDER->value,
                CompanyType::MAINTENANCE_PROVIDER->value,
            ],
        ]);
    }

    /**
     * Set specific company types.
     *
     * @param  array<CompanyType|string>  $types
     */
    public function withTypes(array $types): static
    {
        $typeValues = array_map(
            fn ($type) => $type instanceof CompanyType ? $type->value : $type,
            $types
        );

        return $this->state(fn () => [
            'types' => $typeValues,
        ]);
    }
}
