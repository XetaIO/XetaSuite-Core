<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use XetaSuite\Models\ItemPrice;
use XetaSuite\Models\Item;
use XetaSuite\Models\Supplier;
use XetaSuite\Models\User;

class ItemPriceFactory extends Factory
{
    protected $model = ItemPrice::class;

    /**
     * Configure the model factory.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'item_id' => Item::factory()->create()->id,

            'supplier_id' => Supplier::factory()->create()->id,
            'supplier_name' => null,
            'created_by_id' => User::factory(),
            'created_by_name' => null,

            'price' => $this->faker->randomFloat(2, 0.5, 5000),
            'effective_date' => $this->faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'currency' => $this->faker->currencyCode, // ex. EUR, USD, …
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Link to a specific item.
     *
     * @param Item|int $item
     *
     * @return ItemPriceFactory
     */
    public function forItem(Item|int $item): static
    {
        $itemId = $item instanceof Item ? $item->id : $item;

        return $this->state(fn () => ['item_id' => $itemId]);
    }

    /**
     * Link to a specific supplier (or remove it).
     *
     * @param Supplier|int|null $supplier
     *
     * @return ItemPriceFactory
     */
    public function withSupplier(Supplier|int|null $supplier = null): static
    {
        if (is_null($supplier)) {
            return $this->state(fn () => [
                'supplier_id'   => null,
                'supplier_name' => null,
            ]);
        }

        $supplierId = $supplier instanceof Supplier ? $supplier->id : $supplier;

        return $this->state(fn () => [
            'supplier_id'   => $supplierId,
        ]);
    }

    /**
     * Link to a specific user.
     *
     * @param User|int $user
     *
     * @return ItemPriceFactory
     */
    public function createdBy(User|int $user): static
    {
        return $this->state(fn () => [
            'created_by_id'   => $user instanceof User ? $user->id : $user
        ]);
    }

    /**
     * Defines a specific currency (EUR by default).
     *
     * @param string $currency
     *
     * @return ItemPriceFactory
     */
    public function withCurrency(string $currency = 'EUR'): static
    {
        return $this->state(fn () => ['currency' => strtoupper($currency)]);
    }

    /**
     * Force the effective date.
     *
     * @param string $date
     *
     * @return ItemPriceFactory
     */
    public function effectiveOn(string $date): static
    {
        return $this->state(fn () => ['effective_date' => $date]);
    }

    /**
     * Force the price.
     *
     * @param float $price
     *
     * @return ItemPriceFactory
     */
    public function withPrice(float $price): static
    {
        return $this->state(fn () => ['price' => $price]);
    }

    /**
     * Add some notes.
     *
     * @param string $notes
     *
     * @return ItemPriceFactory
     */
    public function withNotes(string $notes): static
    {
        return $this->state(fn () => ['notes' => $notes]);
    }
}
