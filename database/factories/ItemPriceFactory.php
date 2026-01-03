<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use XetaSuite\Models\Company;
use XetaSuite\Models\Item;
use XetaSuite\Models\ItemPrice;
use XetaSuite\Models\User;

class ItemPriceFactory extends Factory
{
    protected $model = ItemPrice::class;

    /**
     * Configure the model factory.
     */
    public function definition(): array
    {
        return [
            'item_id' => null,

            'company_id' => null,
            'company_name' => null,
            'created_by_id' => null,
            'created_by_name' => null,

            'price' => $this->faker->randomFloat(2, 0.5, 5000),
            'effective_date' => $this->faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Link to a specific item.
     */
    public function forItem(Item|int $item): static
    {
        return $this->state(fn () => [
            'item_id' => $item instanceof Item ? $item->id : $item,
        ]);
    }

    /**
     * Link to a specific company (or remove it).
     */
    public function fromCompany(Company|int|null $company = null): static
    {
        if (is_null($company)) {
            return $this->state(fn () => [
                'company_id' => null,
                'company_name' => null,
            ]);
        }

        return $this->state(fn () => [
            'company_id' => $company instanceof Company ? $company->id : $company,
        ]);
    }

    /**
     * Link to a specific user.
     */
    public function createdBy(User|int $user): static
    {
        return $this->state(fn () => [
            'created_by_id' => $user instanceof User ? $user->id : $user,
        ]);
    }

    /**
     * Defines a specific currency (EUR by default).
     */
    public function withCurrency(string $currency = 'EUR'): static
    {
        return $this->state(fn () => ['currency' => strtoupper($currency)]);
    }

    /**
     * Force the effective date.
     */
    public function effectiveOn(string $date): static
    {
        return $this->state(fn () => ['effective_date' => $date]);
    }

    /**
     * Force the price.
     *
     * @param  float  $price  The base price to set.
     * @param  bool  $variation  Whether to apply a random variation to the price (negative or positive)
     */
    public function withPrice(float $price, bool $variation = false): static
    {
        if ($variation) {
            $priceChoices = [
                'positive' => $price * random_int(10, 50) / 100,
                'negative' => $price * random_int(-50, -10) / 100,
            ];
            $price = $price + $priceChoices[array_rand($priceChoices, 1)];
        }

        return $this->state(fn () => [
            'price' => $price,
        ]);
    }

    /**
     * Add some notes.
     */
    public function withNotes(string $notes): static
    {
        return $this->state(fn () => ['notes' => $notes]);
    }
}
