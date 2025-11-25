<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use XetaSuite\Models\Item;
use XetaSuite\Models\Site;
use XetaSuite\Models\Supplier;
use XetaSuite\Models\User;

class ItemFactory extends Factory
{
    protected $model = Item::class;

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Item $item) {
            $item->materials()->increment('item_count');
            $item->supplier()->increment('item_count');
            $item->creator()->increment('item_count');
        });
    }

    /**
     * Configure the model factory.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'site_id' => null,
            'created_by_id' => null,
            'created_by_name' => null,
            'supplier_id' => null,
            'supplier_name' => null,
            'supplier_reference' => $this->faker->optional()->word(),
            'edited_by_id' => null,

            'name' => $this->faker->unique()->name(),
            'description' => $this->faker->optional()->paragraph(),
            'reference' => $this->faker->unique()->bothify('REF-####'),
            'purchase_price' => $this->faker->randomFloat(2, 0, 5000),
            'currency' => $this->faker->currencyCode,

            'item_entry_total' => 0,
            'item_exit_total' => 0,
            'item_entry_count' => 0,
            'item_exit_count' => 0,

            'material_count' => 0,
            'qrcode_flash_count' => $this->faker->numberBetween(0, 10),

            'number_warning_enabled' => false,
            'number_warning_minimum' => 0,
            'number_critical_enabled' => false,
            'number_critical_minimum' => 0
        ];
    }

    /**
     * Forces the use of an existing site (or site ID) & Forces the use of an existing supplier (or supplier ID).
     *
     * @param Site|int $site
     * @param Supplier|int $supplier
     *
     * @return ItemFactory
     */
    public function forSiteWithSupplier(Site|int $site, Supplier|int $supplier): static
    {
        return $this->state(fn () => [
            'site_id' => $site instanceof Site ? $site->id : $site,
            'supplier_id' => $supplier instanceof Supplier ? $supplier->id : $supplier,
        ]);
    }

    /**
     * Defines a creator (User).
     *
     * @param User|int  $user
     *
     * @return ItemFactory
     */
    public function createdBy(User|int $user): static
    {
        return $this->state(fn () => [
            'created_by_id'   => $user instanceof User ? $user->id : $user
        ]);
    }

    /**
     * Defines an editor (User).
     *
     * @param User|int  $user
     *
     * @return ItemFactory
     */
    public function editedBy(User|int $user): static
    {
        return $this->state(fn () => [
            'edited_user_id' => $user instanceof User ? $user->id : $user
        ]);
    }

    /**
     * Adds materials to the item.
     *
     * @param array $materialIds
     *
     * @return ItemFactory
     */
    public function withMaterials(array $materialIds): static
    {
        return $this->hasAttached($materialIds, [], 'materials')
            ->state(fn () => [
                'material_count' => count($materialIds)
            ]);
    }

    /**
     * Forces the "warning" alert status (and the minimum) for tests.
     *
     * @param int $minimum
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
     * @param int $minimum
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
