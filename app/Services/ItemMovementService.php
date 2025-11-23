<?php

namespace XetaSuite\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use XetaSuite\Models\Item;
use XetaSuite\Models\ItemMovement;
use XetaSuite\Models\Material;
use XetaSuite\Models\Supplier;

/**
 * // 1. Changement de prix
 * $priceService = app(ItemPriceService::class);
 * $priceService->updatePrice(
 * item: $item,
 * newPrice: 25.50,
 * supplier: $supplier,
 * effectiveDate: '2025-11-20',
 * notes: 'Augmentation tarifaire fournisseur'
 * );
 *
 * // 2. Entrée de stock (achat)
 * $movementService = app(ItemMovementService::class);
 * $movementService->recordEntry(
 * item: $item,
 * quantity: 100,
 * unitPrice: 25.50,
 * supplier: $supplier,
 * invoiceNumber: 'INV-2025-001',
 * invoiceDate: now(),
 * notes: 'Commande trimestrielle'
 * );
 *
 * // 3. Sortie de stock (utilisation)
 * $movementService->recordExit(
 * item: $item,
 * quantity: 5,
 * material: $elevator,
 * relatedModel: $maintenance, // Optionnel
 * notes: 'Remplacement pièce suite à maintenance préventive'
 * );
 *
 * // 4. Rapport de mouvements
 * $report = $movementService->getMovementReport(
 * item: $item,
 * startDate: now()->startOfMonth(),
 * endDate: now()->endOfMonth()
 * );
 *
 * // 5. Valorisation du stock
 * $fifoValue = $movementService->getStockValuation($item, 'fifo');
 * $avgValue = $movementService->getStockValuation($item, 'weighted_average');
 *
 * // 6. Historique de prix
 * $history = $priceService->getPriceHistory($item, $supplier->id);
 *
 * // 7. Variation de prix
 * $variation = $priceService->getPriceVariation(
 * item: $item,
 * startDate: '2025-01-01',
 * endDate: '2025-11-20'
 * );
 * // Résultat : ['start_price' => 20.00, 'end_price' => 25.50, 'variation' => 5.50, 'variation_percent' => 27.5]
 */
class ItemMovementService
{
    /**
     * Record an entry (purchase/arrival) of items.
     */
    public function recordEntry(
        Item $item,
        int $quantity,
        float $unitPrice,
        ?Supplier $supplier = null,
        ?string $invoiceNumber = null,
        ?Carbon $invoiceDate = null,
        ?string $notes = null,
        ?Carbon $movementDate = null
    ): ItemMovement {
        return DB::transaction(function () use (
            $item,
            $quantity,
            $unitPrice,
            $supplier,
            $invoiceNumber,
            $invoiceDate,
            $notes,
            $movementDate
        ) {
            $totalPrice = $quantity * $unitPrice;

            $movement = ItemMovement::create([
                'item_id' => $item->id,
                'type' => 'entry',
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
                'supplier_id' => $supplier?->id,
                'supplier_name' => $supplier?->name,
                'supplier_invoice_number' => $invoiceNumber,
                'invoice_date' => $invoiceDate,
                'created_by_id' => auth()->id(),
                'created_by_name' => auth()->user()?->full_name,
                'notes' => $notes,
                'movement_date' => $movementDate ?? now(),
            ]);

            // Mettre à jour les compteurs
            $item->increment('item_entry_total', $quantity);
            $item->increment('item_entry_count');

            // Si le prix a changé, créer un nouvel historique de prix
            $currentPrice = $item->getCurrentPrice($supplier?->id);
            if (! $currentPrice || $currentPrice->price != $unitPrice) {
                app(ItemPriceService::class)->updatePrice(
                    $item,
                    $unitPrice,
                    $supplier,
                    $movementDate?->toDateString(),
                    'Prix mis à jour suite à une entrée de stock'
                );
            }

            return $movement;
        });
    }

    /**
     * Record an exit (usage/consumption) of items.
     */
    public function recordExit(
        Item $item,
        int $quantity,
        ?Material $material = null,
        $relatedModel = null, // Maintenance, Incident, etc.
        ?string $notes = null,
        ?Carbon $movementDate = null
    ): ItemMovement {
        return DB::transaction(function () use (
            $item,
            $quantity,
            $material,
            $relatedModel,
            $notes,
            $movementDate
        ) {
            // Vérifier le stock disponible
            if ($item->current_stock < $quantity) {
                throw new \Exception("Insufficient stock for the item {$item->name}. Stock available: {$item->current_stock}");
            }

            // Utiliser le prix actuel pour valoriser la sortie
            $currentPrice = $item->getCurrentPrice();
            $unitPrice = $currentPrice?->price ?? $item->purchase_price;
            $totalPrice = $quantity * $unitPrice;

            $movement = ItemMovement::create([
                'item_id' => $item->id,
                'type' => 'exit',
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
                'material_id' => $material?->id,
                'material_name' => $material?->name,
                'movable_type' => $relatedModel ? get_class($relatedModel) : null,
                'movable_id' => $relatedModel?->id ?? null,
                'created_by_id' => auth()->id(),
                'created_by_name' => auth()->user()?->full_name,
                'notes' => $notes,
                'movement_date' => $movementDate ?? now(),
            ]);

            // Mettre à jour les compteurs
            $item->increment('item_exit_total', $quantity);
            $item->increment('item_exit_count');

            // Si le matériel est spécifié, incrémenter son compteur
            if ($material) {
                $material->increment('item_count');
            }

            return $movement;
        });
    }

    /**
     * Get movement report for a period.
     */
    public function getMovementReport(Item $item, Carbon $startDate, Carbon $endDate): array
    {
        $movements = $item->movements()
            ->whereBetween('movement_date', [$startDate, $endDate])
            ->get();

        $entries = $movements->where('type', 'entry');
        $exits = $movements->where('type', 'exit');

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'entries' => [
                'count' => $entries->count(),
                'quantity' => $entries->sum('quantity'),
                'total_value' => $entries->sum('total_price'),
            ],
            'exits' => [
                'count' => $exits->count(),
                'quantity' => $exits->sum('quantity'),
                'total_value' => $exits->sum('total_price'),
            ],
            'net_movement' => [
                'quantity' => $entries->sum('quantity') - $exits->sum('quantity'),
                'value' => $entries->sum('total_price') - $exits->sum('total_price'),
            ],
        ];
    }

    /**
     * Get stock valuation using different methods.
     */
    public function getStockValuation(Item $item, string $method = 'current'): float
    {
        $currentStock = $item->getCurrentStock();

        return match ($method) {
            // Méthode du prix actuel
            'current' => $currentStock * ($item->getCurrentPrice()?->price ?? 0),

            // Méthode FIFO (First In First Out)
            'fifo' => $this->calculateFifoValue($item, $currentStock),

            // Méthode du coût moyen pondéré
            'weighted_average' => $this->calculateWeightedAverageValue($item, $currentStock),

            default => 0.00,
        };
    }

    private function calculateFifoValue(Item $item, int $stockToValue): float
    {
        $remainingStock = $stockToValue;
        $totalValue = 0;

        $entries = $item->movements()
            ->where('type', 'entry')
            ->orderBy('movement_date', 'asc')
            ->get();

        foreach ($entries as $entry) {
            if ($remainingStock <= 0) {
                break;
            }

            $quantityToTake = min($remainingStock, $entry->quantity);
            $totalValue += $quantityToTake * $entry->unit_price;
            $remainingStock -= $quantityToTake;
        }

        return $totalValue;
    }

    private function calculateWeightedAverageValue(Item $item, int $currentStock): float
    {
        $entries = $item->movements()
            ->where('type', 'entry')
            ->get();

        if ($entries->isEmpty()) {
            return 0;
        }

        $totalQuantity = $entries->sum('quantity');
        $totalValue = $entries->sum('total_price');

        $averagePrice = $totalQuantity > 0 ? $totalValue / $totalQuantity : 0;

        return $currentStock * $averagePrice;
    }
}
