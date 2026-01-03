<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use XetaSuite\Models\Company;
use XetaSuite\Models\Item;
use XetaSuite\Models\ItemMovement;
use XetaSuite\Models\Maintenance;
use XetaSuite\Models\User;

class ItemMovementFactory extends Factory
{
    protected $model = ItemMovement::class;

    /**
     * Configure the model factory.
     */
    public function definition(): array
    {
        // By default, an "entry" movement is generated. The `exit()` state can change this.
        $type = 'entry';

        $invoiceNumber = null;
        $invoiceDate = null;

        // Entry movement
        if ($type === 'entry') {
            $invoiceNumber = $this->faker->optional()->bothify('INV-####');
            $invoiceDate = $this->faker->optional()->date();
        }

        return [
            'item_id' => null,

            'type' => $type,

            'quantity' => 0,
            'unit_price' => 0,
            'total_price' => 0,

            // Entry (company)
            'company_id' => null,
            'company_name' => null,
            'company_invoice_number' => $invoiceNumber,
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
     */
    public function exit(): static
    {
        return $this->state(fn () => ['type' => 'exit'])
            ->afterMaking(function (ItemMovement $movement) {
                $movement->company_id = null;
                $movement->company_name = null;
                $movement->company_invoice_number = null;
                $movement->invoice_date = null;
            });
    }

    /**
     * Associates an Item to the movement.
     */
    public function forItem(Item|int $item): static
    {
        return $this->state(fn () => [
            'item_id' => $item instanceof Item ? $item->id : $item,
        ]);
    }

    /**
     * Associates a Company to the movement. (For Entries)
     */
    public function fromCompany(Company|int $company): static
    {
        return $this->state(fn () => [
            'company_id' => $company instanceof Company ? $company->id : $company,
            'company_invoice_number' => $this->faker->bothify('INV-####'),
            'invoice_date' => $this->faker->date(),
        ]);
    }

    /**
     * Link the movement to a Maintenance via the polymorphic column. (For Exits)
     */
    public function forMaintenance(Maintenance|int $maintenance): static
    {
        return $this->state(fn () => [
            'movable_type' => Maintenance::class,
            'movable_id' => $maintenance instanceof Maintenance ? $maintenance->id : $maintenance,
        ]);
    }

    /**
     * Defines quantity and unit price for the movement.
     */
    public function withQuantity(int $quantity, float $unitPrice): static
    {
        $totalPrice = $quantity * $unitPrice;

        return $this->state(fn () => [
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
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
}
