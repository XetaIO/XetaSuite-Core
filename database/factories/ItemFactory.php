<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use XetaSuite\Models\Item;
use XetaSuite\Models\Site;
use XetaSuite\Models\Supplier;
use XetaSuite\Models\User;
use XetaSuite\Models\Material;

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
        $site      = Site::factory()->create();
        $supplier  = Supplier::factory()->forSite($site)->create();

        return [
            'site_id' => $site->id,
            'created_by_id' => User::factory()->create()->id,
            'created_by_name' => null,
            'supplier_id' => $supplier->id,
            'supplier_name' => $supplier->name,
            'supplier_reference' => $this->faker->optional()->word(),
            'edited_user_id' => User::factory()->create()->id,

            'name' => $this->faker->unique()->word(),
            'description' => $this->faker->optional()->paragraph(),
            'reference' => $this->faker->optional()->unique()->bothify('REF-####'),
            'purchase_price' => $this->faker->randomFloat(2, 0, 5000),
            'currency' => $this->faker->currencyCode,

            'item_entry_total' => 0,
            'item_exit_total' => 0,
            'item_entry_count' => 0,
            'item_exit_count' => 0,

            'material_count' => $this->faker->numberBetween(0, 20),
            'qrcode_flash_count' => $this->faker->numberBetween(0, 10),

            'number_warning_enabled' => $this->faker->boolean(),
            'number_warning_minimum' => $this->faker->numberBetween(0, 100),
            'number_critical_enabled' => $this->faker->boolean(),
            'number_critical_minimum' => $this->faker->numberBetween(0, 50)
        ];
    }

    /**
     * Forces the use of an existing site (or site ID).
     *
     * @param Site|int $site
     *
     * @return ItemFactory
     */
    public function forSite(Site|int $site): static
    {
        return $this->state(fn () => [
            'site_id' => $site instanceof Site ? $site->id : $site
        ]);
    }

    /**
     * Forces the use of an existing supplier (or supplier ID).
     *
     * @param Supplier|int $supplier
     *
     * @return ItemFactory
     */
    public function forSupplier(Supplier|int $supplier): static
    {
        $supplierId = $supplier instanceof Supplier ? $supplier->id : $supplier;

        return $this->state(fn () => ['supplier_id' => $supplierId]);
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
        return $this->hasAttached(Material::class, $materialIds, 'materials');
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
