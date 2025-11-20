<?php

declare(strict_types=1);

namespace XetaSuite\Services;

use Illuminate\Support\Facades\DB;
use XetaSuite\Models\Item;
use XetaSuite\Models\ItemPrice;
use XetaSuite\Models\Supplier;

class ItemPriceService
{
    /**
     * Update or create a new price for an item.
     */
    public function updatePrice(
        Item $item,
        float $newPrice,
        ?Supplier $supplier = null,
        ?string $effectiveDate = null,
        ?string $notes = null
    ): ItemPrice {
        return DB::transaction(function () use ($item, $newPrice, $supplier, $effectiveDate, $notes) {
            $itemPrice = ItemPrice::create([
                'item_id' => $item->id,
                'supplier_id' => $supplier?->id,
                'supplier_name' => $supplier?->name,
                'created_by_id' => auth()->id(),
                'created_by_name' => auth()->user()?->full_name,
                'price' => $newPrice,
                'effective_date' => $effectiveDate ?? now()->toDateString(),
                'notes' => $notes,
            ]);

            // Mettre à jour le prix actuel dans la table items
            $item->update([
                'purchase_price' => $newPrice,
                'supplier_id' => $supplier?->id,
                'supplier_name' => $supplier?->name,
            ]);

            return $itemPrice;
        });
    }

    /**
     * Get price history for an item.
     */
    public function getPriceHistory(Item $item, ?int $supplierId = null)
    {
        $query = $item->prices();

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        return $query->get();
    }

    /**
     * Calculate price variation between two dates.
     */
    public function getPriceVariation(Item $item, string $startDate, string $endDate): array
    {
        $startPrice = $item->prices()
            ->where('effective_date', '<=', $startDate)
            ->first();

        $endPrice = $item->prices()
            ->where('effective_date', '<=', $endDate)
            ->first();

        if (! $startPrice || ! $endPrice) {
            return [
                'start_price' => $startPrice?->price ?? 0,
                'end_price' => $endPrice?->price ?? 0,
                'variation' => 0,
                'variation_percent' => 0,
            ];
        }

        $variation = $endPrice->price - $startPrice->price;
        $variationPercent = $startPrice->price > 0
            ? ($variation / $startPrice->price) * 100
            : 0;

        return [
            'start_price' => $startPrice->price,
            'end_price' => $endPrice->price,
            'variation' => $variation,
            'variation_percent' => round($variationPercent, 2),
        ];
    }
}
