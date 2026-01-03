<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use XetaSuite\Models\Company;
use XetaSuite\Models\Item;
use XetaSuite\Models\Site;
use XetaSuite\Models\User;

class ItemFactory extends Factory
{
    protected $model = Item::class;

    /**
     * Configure the model factory.
     */
    public function definition(): array
    {
        return [
            'site_id' => null,
            'created_by_id' => null,
            'created_by_name' => null,
            'company_id' => null,
            'company_name' => null,
            'company_reference' => \fake()->optional()->word(),
            'edited_by_id' => null,

            'name' => \fake()->unique()->name(),
            'description' => \fake()->optional()->paragraph(),
            'reference' => \fake()->unique()->bothify('REF-####'),
            'current_price' => \fake()->randomFloat(2, 0, 5000),

            'item_entry_total' => 0,
            'item_exit_total' => 0,
            'item_entry_count' => 0,
            'item_exit_count' => 0,

            'material_count' => 0,
            'qrcode_flash_count' => \fake()->numberBetween(0, 10),

            'number_warning_enabled' => false,
            'number_warning_minimum' => 0,
            'number_critical_enabled' => false,
            'number_critical_minimum' => 0,
        ];
    }

    /**
     * Forces the use of an existing site (or site ID).
     */
    public function forSite(Site|int $site): static
    {
        return $this->state(fn () => [
            'site_id' => $site instanceof Site ? $site->id : $site,
        ]);
    }

    /**
     * Forces the use of an existing company (or company ID).
     */
    public function fromCompany(Company|int $company): static
    {
        return $this->state(fn () => [
            'company_id' => $company instanceof Company ? $company->id : $company,
        ]);
    }

    /**
     * Defines a creator (User).
     */
    public function createdBy(User|int $user): static
    {
        return $this->state(fn () => [
            'created_by_id' => $user instanceof User ? $user->id : $user,
        ]);
    }

    /**
     * Defines an editor (User).
     */
    public function editedBy(User|int $user): static
    {
        return $this->state(fn () => [
            'edited_by_id' => $user instanceof User ? $user->id : $user,
        ]);
    }

    /**
     * Adds materials to the item.
     *
     *
     * @return ItemFactory
     */
    public function withMaterials(array $materialIds): static
    {
        return $this->hasAttached($materialIds, [], 'materials');
    }

    /**
     * Forces the "warning" alert status (and the minimum) for tests.
     *
     *
     * @return ItemFactory
     */
    public function withWarning(int $minimum = 10): static
    {
        return $this->state(fn () => [
            'number_warning_enabled' => true,
            'number_warning_minimum' => $minimum,
        ]);
    }

    /**
     * Forces the "critical" alert status (and the minimum) for tests.
     *
     *
     * @return ItemFactory
     */
    public function withCritical(int $minimum = 5): static
    {
        return $this->state(fn () => [
            'number_critical_enabled' => true,
            'number_critical_minimum' => $minimum,
        ]);
    }
}
