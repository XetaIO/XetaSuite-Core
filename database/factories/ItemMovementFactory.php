<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use XetaSuite\Models\ItemMovement;
use XetaSuite\Models\Item;
use XetaSuite\Models\Supplier;
use XetaSuite\Models\Material;
use XetaSuite\Models\User;
use XetaSuite\Models\Maintenance;

class ItemMovementFactory extends Factory
{
    protected $model = ItemMovement::class;

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (ItemMovement $movement) {
            $item = $movement->item;

            if ($movement->type === 'entry') {
                $item->increment('item_entry_total', $movement->quantity);
                $item->increment('item_entry_count');
            } else {
                $item->increment('item_exit_total', $movement->quantity);
                $item->increment('item_exit_count');
            }
        });
    }

    /**
     * Configure the model factory.
     *
     * @return array
     */
    public function definition(): array
    {
        $item = Item::factory()->create();
        $creator = User::factory()->create();

        // By default, an "entry" movement is generated. The `exit()` state can change this.
        $type = 'entry';

        $quantity = $this->faker->numberBetween(1, 100);
        $unitPrice = $this->faker->randomFloat(2, 0.5, 5000);
        $totalPrice = $quantity * $unitPrice;

        // Data linked to entries (supplier)
        $supplierId  = null;
        $invoiceNumber = null;
        $invoiceDate   = null;

        // Data linked to exits (material)
        $materialId  = null;
        $materialName = null;

        // Entry movement
        if ($type === 'entry') {
            $supplier = Supplier::factory()->create();
            $supplierId = $supplier->id;
            $invoiceNumber = $this->faker->optional()->bothify('INV-####');
            $invoiceDate   = $this->faker->optional()->date();
        }

        // Exit movement
        if ($type === 'exit') {
            $material = Material::factory()->create();
            $materialId = $material->id;
            $materialName = $material->name;
        }

        return [
            'item_id'   => $item->id,

            'type'      => $type,

            'quantity'      => $quantity,
            'unit_price'    => $unitPrice,
            'total_price'   => $totalPrice,

            // Entry (supplier)
            'supplier_id' => $supplierId,
            'supplier_name' => null,
            'supplier_invoice_number' => $invoiceNumber,
            'invoice_date' => $invoiceDate,

            // Exit (material)
            'material_id' => $materialId,
            'material_name' => $materialName,

            // Polymorph (maintenance)
            'movable_type' => null,
            'movable_id' => null,

            'created_by_id' => $creator->id,
            'created_by_name' => null,

            'notes' => $this->faker->optional()->sentence(),
            'movement_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }


    /**
     * For the type of the movement to be an Entry.
     *
     * @return ItemMovementFactory
     */
    public function entry(): static
    {
        return $this->state(fn () => ['type' => 'entry'])
            ->afterMaking(function (ItemMovement $movement) {
                $movement->material_id = null;
                $movement->material_name = null;
                $movement->movable_type = null;
                $movement->movable_id = null;
            });
    }

    /**
     * For the type of the movement to be an Exit.
     *
     * @return ItemMovementFactory
     */
    public function exit(): static
    {
        return $this->state(fn () => ['type' => 'exit'])
            ->afterMaking(function (ItemMovement $movement) {
                $movement->supplier_id = null;
                $movement->supplier_name = null;
                $movement->supplier_invoice_number = null;
                $movement->invoice_date = null;
            });
    }

    /**
     * Associates a Supplier to the movement. (For Entries)
     *
     * @param Supplier|int $supplier
     *
     * @return ItemMovementFactory
     */
    public function withSupplier(Supplier|int $supplier): static
    {
        $supplierId = $supplier instanceof Supplier ? $supplier->id : $supplier;

        return $this->state(fn () => [
            'supplier_id' => $supplierId,
            'supplier_invoice_number' => $this->faker->bothify('INV-####'),
            'invoice_date' => $this->faker->date(),
        ]);
    }

    /**
     * Associates a Material to the movement. (For Exits)
     *
     * @param Material|int $material
     *
     * @return ItemMovementFactory
     */
    public function withMaterial(Material|int $material): static
    {
        $materialId   = $material instanceof Material ? $material->id : $material;

        return $this->state(fn () => [
            'material_id'   => $materialId
        ]);
    }

    /**
     * Link the movement to a Maintenance via the polymorphic column.
     *
     * @param Maintenance|int $maintenance
     *
     * @return ItemMovementFactory
     */
    public function withMaintenance(Maintenance|int $maintenance): static
    {
        $maintenanceId = $maintenance instanceof Maintenance ? $maintenance->id : $maintenance;

        return $this->state(fn () => [
            'movable_type' => Maintenance::class,
            'movable_id'   => $maintenanceId,
        ]);
    }

    /**
     * Defines a creator (User).
     *
     * @param User|int $user
     *
     * @return ItemMovementFactory
     */
    public function createdBy(User|int $user): static
    {
        return $this->state(fn () => [
            'created_by_id'   => $user instanceof User ? $user->id : $user
        ]);
    }
}
