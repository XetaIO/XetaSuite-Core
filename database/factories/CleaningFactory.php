<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use XetaSuite\Models\Cleaning;
use XetaSuite\Models\Material;
use XetaSuite\Models\Site;
use XetaSuite\Models\User;
use XetaSuite\Enums\Cleanings\CleaningType;
use XetaSuite\Models\Zone;

class CleaningFactory extends Factory
{
    protected $model = Cleaning::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        $site = Site::factory()->create();
        $zone = Zone::factory()->forSite($site)->create();
        $material = Material::factory()->inSiteAndZone($site, $zone)->create();

        return [
            'site_id' => $site->id,
            'material_id' => $material->id,
            'material_name' => null,

            'created_by_id' => User::factory()->create()->id,
            'created_by_name' => null,
            'edited_user_id' => null,

            'description' => $this->faker->paragraph(),
            'type' => $this->faker->randomElement(CleaningType::cases())->value,
        ];
    }

    /**
     * Indicate that the cleaning was created by a specific user.
     *
     * @param User|int $user
     *
     * @return CleaningFactory
     */
    public function createdBy(User|int $user): static
    {
        return $this->state(fn () => [
            'created_by_id' => $user instanceof User ? $user->id : $user
        ]);
    }

    /**
     * Indicate that the cleaning was edited by a specific user.
     *
     * @param User|int $user
     *
     * @return CleaningFactory
     */
    public function editedBy(User|int $user): static
    {
        return $this->state(fn () => [
            'edited_user_id' => $user instanceof User ? $user->id : $user
        ]);
    }

    /**
     * Forces the use of an existing site (or site ID).
     *
     * @param Site|int $site
     *
     * @return CleaningFactory
     */
    public function forSite(Site|int $site): static
    {
        return $this->state(fn () => [
            'site_id' => $site instanceof Site ? $site->id : $site
        ]);
    }

    /**
     * Indicate that the cleaning is for a specific material.
     *
     * @param Material|int $material
     *
     * @return CleaningFactory
     */
    public function forMaterial(Material|int $material): static
    {
        return $this->state(fn () => [
            'material_id' => $material instanceof Material ? $material->id : $material
        ]);
    }

    /**
     * Indicate that the cleaning has a specific type.
     *
     * @param CleaningType|string $type
     *
     * @return CleaningFactory
     */
    public function withType(CleaningType|string $type): static
    {
        return $this->state(function (array $attributes) use ($type) {
            return [
                'type' => $type instanceof CleaningType ? $type->value : $type,
            ];
        });
    }
}
