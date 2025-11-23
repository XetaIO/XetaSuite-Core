<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use XetaSuite\Models\Supplier;
use XetaSuite\Models\Site;
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
            'site_id' => Site::factory(),
            'name' => $this->faker->unique()->company(),
            'description' => $this->faker->optional()->sentence(),
            'created_by_id' => User::factory()->create()->id,
        ];
    }

    /**
     * Forces the use of an existing site (or site ID).
     *
     * Example:
     *     Supplier::factory()->forSite($site)->create();
     *
     * @param Site|int $site
     *
     * @return SupplierFactory
     */
    public function forSite(Site|int $site): static
    {
        $siteId = $site instanceof Site ? $site->id : $site;

        return $this->state(fn () => [
            'site_id' => $siteId,
        ]);
    }

    /**
     * Attributes a specific creator.
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
