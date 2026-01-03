<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use XetaSuite\Enums\Cleanings\CleaningType;
use XetaSuite\Models\Cleaning;
use XetaSuite\Models\Material;
use XetaSuite\Models\Site;
use XetaSuite\Models\User;

class CleaningFactory extends Factory
{
    protected $model = Cleaning::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'site_id' => null,
            'material_id' => null,
            'material_name' => null,

            'created_by_id' => null,
            'created_by_name' => null,
            'edited_by_id' => null,

            'description' => \fake()->paragraph(),
            'type' => \fake()->randomElement(CleaningType::cases())->value,
        ];
    }

    /**
     * Forces the use of an existing site (or site ID).
     *
     *
     * @return CleaningFactory
     */
    public function forSite(Site|int $site): static
    {
        return $this->state(fn () => [
            'site_id' => $site instanceof Site ? $site->id : $site,
        ]);
    }

    /**
     * Indicate that the cleaning is for a specific material.
     *
     *
     * @return CleaningFactory
     */
    public function forMaterial(Material|int $material): static
    {
        return $this->state(fn () => [
            'material_id' => $material instanceof Material ? $material->id : $material,
        ]);
    }

    /**
     * Indicate that the cleaning has a specific type.
     *
     *
     * @return CleaningFactory
     */
    public function withType(CleaningType|string $type): static
    {
        return $this->state(fn () => [
            'type' => $type instanceof CleaningType ? $type->value : $type,
        ]);
    }

    /**
     * Indicate that the cleaning was created by a specific user.
     *
     *
     * @return CleaningFactory
     */
    public function createdBy(User|int $user): static
    {
        return $this->state(fn () => [
            'created_by_id' => $user instanceof User ? $user->id : $user,
        ]);
    }

    /**
     * Indicate that the cleaning was edited by a specific user.
     *
     *
     * @return CleaningFactory
     */
    public function editedBy(User|int $user): static
    {
        return $this->state(fn () => [
            'edited_by_id' => $user instanceof User ? $user->id : $user,
        ]);
    }
}
