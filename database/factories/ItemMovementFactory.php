<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use XetaSuite\Models\ItemMovement;
use XetaSuite\Models\Item;
use XetaSuite\Models\Supplier;
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
                $movement->creator()->increment('item_entry_count');
            } else {
                $item->increment('item_exit_total', $movement->quantity);
                $item->increment('item_exit_count');
                $movement->creator()->increment('item_exit_count');
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
        // By default, an "entry" movement is generated. The `exit()` state can change this.
        $type = 'entry';

        $invoiceNumber = null;
        $invoiceDate   = null;

        // Entry movement
        if ($type === 'entry') {
            $invoiceNumber = $this->faker->optional()->bothify('INV-####');
            $invoiceDate   = $this->faker->optional()->date();
        }

        return [
            'item_id' => null,

            'type' => $type,

            'quantity' => 0,
            'unit_price' => 0,
            'total_price'   => 0,

            // Entry (supplier)
            'supplier_id' => null,
            'supplier_name' => null,
            'supplier_invoice_number' => $invoiceNumber,
            'invoice_date' => $invoiceDate,

            // Polymorph (maintenance)
            'movable_type' => null,
            'movable_id' => null,

            'created_by_id' => null,
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
     * Associates an Item to the movement.
     *
     * @param Item|int $item
     *
     * @return ItemMovementFactory
     */
    public function forItem(Item|int $item): static
    {
        return $this->state(fn () => [
            'item_id'   => $item instanceof Item ? $item->id : $item,
        ]);
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
        return $this->state(fn () => [
            'supplier_id' => $supplier instanceof Supplier ? $supplier->id : $supplier,
            'supplier_invoice_number' => $this->faker->bothify('INV-####'),
            'invoice_date' => $this->faker->date(),
        ]);
    }

    /**
     * Link the movement to a Maintenance via the polymorphic column. (For Exits)
     *
     * @param Maintenance|int $maintenance
     *
     * @return ItemMovementFactory
     */
    public function withMaintenance(Maintenance|int $maintenance): static
    {
        return $this->state(fn () => [
            'movable_type' => Maintenance::class,
            'movable_id'   => $maintenance instanceof Maintenance ? $maintenance->id : $maintenance,
        ]);
    }

    /**
     * Defines quantity and unit price for the movement.
     *
     * @param int   $quantity
     * @param float $unitPrice
     *
     * @return ItemMovementFactory
     */
    public function withQuantity(int $quantity, float $unitPrice): static
    {
        $totalPrice = $quantity * $unitPrice;

        return $this->state(fn () => [
            'quantity'   => $quantity,
            'unit_price'   => $unitPrice,
            'total_price'   => $totalPrice,
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
