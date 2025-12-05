<?php

declare(strict_types=1);

namespace XetaSuite\Http\Resources\V1\Items;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use XetaSuite\Services\ItemService;

class ItemResource extends JsonResource
{
    /**
     * Transform the resource into an array (for list view).
     */
    public function toArray(Request $request): array
    {
        $itemService = app(ItemService::class);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'reference' => $this->reference,
            'description' => $this->description,

            // Supplier info
            'supplier_id' => $this->supplier_id,
            'supplier_name' => $this->supplier_name,
            'supplier_reference' => $this->supplier_reference,

            // Creator info
            'created_by_id' => $this->created_by_id,
            'created_by_name' => $this->created_by_name,

            // Pricing
            'purchase_price' => (float) $this->purchase_price,
            'currency' => $this->currency,

            // Stock counts
            'item_entry_total' => $this->item_entry_total,
            'item_exit_total' => $this->item_exit_total,
            'stock_level' => $this->item_entry_total - $this->item_exit_total,
            'stock_status' => $itemService->getStockStatus($this->resource),

            // Relation counts
            'material_count' => $this->material_count,
            'movement_count' => $this->item_entry_count + $this->item_exit_count,

            // Alerts
            'number_warning_enabled' => $this->number_warning_enabled,
            'number_warning_minimum' => $this->number_warning_minimum,
            'number_critical_enabled' => $this->number_critical_enabled,
            'number_critical_minimum' => $this->number_critical_minimum,

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
