<?php

declare(strict_types=1);

namespace XetaSuite\Services;

use XetaSuite\Models\Item;

class ItemPriceService
{
    /**
     * Get price history with statistics for an item.
     * Returns history entries and computed stats for display.
     */
    public function getPriceHistoryWithStats(Item $item, int $limit = 20): array
    {
        $history = $item->prices()
            ->orderBy('effective_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        // Sort ascending for chart display
        $sortedHistory = $history->sortBy('effective_date')->values();

        // Calculate statistics
        $prices = $sortedHistory->pluck('price')->filter();

        if ($prices->isEmpty()) {
            return [
                'history' => [],
                'stats' => [
                    'current_price' => $item->current_price ?? 0,
                    'average_price' => 0,
                    'min_price' => 0,
                    'max_price' => 0,
                    'price_change' => 0,
                    'price_change_percent' => 0,
                    'total_entries' => 0,
                ],
            ];
        }

        $firstPrice = $prices->first();
        $lastPrice = $prices->last();
        $priceChange = $lastPrice - $firstPrice;
        $priceChangePercent = $firstPrice > 0
            ? round(($priceChange / $firstPrice) * 100, 2)
            : 0;

        return [
            'history' => $sortedHistory->map(fn ($price) => [
                'id' => $price->id,
                'price' => (float) $price->price,
                'effective_date' => $price->effective_date->toDateString(),
                'notes' => $price->notes,
                'created_at' => $price->created_at->toISOString(),
            ])->values()->toArray(),
            'stats' => [
                'current_price' => (float) ($item->current_price ?? $lastPrice),
                'average_price' => round($prices->avg(), 2),
                'min_price' => (float) $prices->min(),
                'max_price' => (float) $prices->max(),
                'price_change' => round($priceChange, 2),
                'price_change_percent' => $priceChangePercent,
                'total_entries' => $prices->count(),
            ],
        ];
    }
}
