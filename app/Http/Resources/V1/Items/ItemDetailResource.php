<?php

declare(strict_types=1);

namespace XetaSuite\Http\Resources\V1\Items;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array (for detail view).
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'full_name' => $this->full_name,
            'reference' => $this->reference,
            'description' => $this->description,

            // Stock complet
            'stock' => [
                'current' => $this->current_stock,
                'status' => $this->stock_status,
                'status_color' => $this->stock_status_color,
                'status_label' => $this->stock_status_label,
                'in_stock' => $this->in_stock,
                'value' => $this->stock_value,
                'formatted_value' => $this->formatted_stock_value
            ],

            // Alertes
            'alerts' => [
                'is_low_stock' => $this->is_low_stock,
                'is_critical_stock' => $this->is_critical_stock,
                'needs_restock' => $this->needs_restock,
                'quantity_to_warning_level' => $this->quantity_to_warning_level,
                'warning_enabled' => $this->number_warning_enabled,
                'warning_minimum' => $this->number_warning_minimum,
                'critical_enabled' => $this->number_critical_enabled,
                'critical_minimum' => $this->number_critical_minimum,
            ],

            // Prices
            'pricing' => [
                'current_price' => $this->current_price_value,
                'formatted_price' => $this->formatted_price,
                'currency' => $this->currency,
            ],

            // Movement statistics
            'movements' => [
                'entry_total' => $this->item_entry_total,
                'exit_total' => $this->item_exit_total,
                'entry_count' => $this->item_entry_count,
                'exit_count' => $this->item_exit_count,
                'average_entry' => $this->average_entry_quantity,
                'average_exit' => $this->average_exit_quantity
            ],

            // Relations
            'site' => $this->whenLoaded('site', fn () => [
                'id' => $this->site->id,
                'name' => $this->site->name,
            ]),

            'supplier' => [
                'id' => $this->supplier_id,
                'name' => $this->supplier_display_name,
                'reference' => $this->supplier_reference,
            ],

            'created_by' => $this->when($this->created_by_id, [
                'id' => $this->created_by_id,
                'name' => $this->created_by_name,
            ]),

            // Price history (if loaded)
            'price_history' => $this->whenLoaded(
                'prices',
                fn () => ItemPriceResource::collection($this->prices)
            ),

            // History of movements
            'movement_history' => $this->whenLoaded(
                'movements',
                fn () => ItemMovementResource::collection($this->movements)
            ),

            // Linked materials
            'materials' => $this->whenLoaded(
                'materials',
                fn () => $this->materials->map(fn ($material) => [
                'id' => $material->id,
                'name' => $material->name,
            ])
            ),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
