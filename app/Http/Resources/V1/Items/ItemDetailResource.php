<?php

declare(strict_types=1);

namespace XetaSuite\Http\Resources\V1\Items;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use XetaSuite\Services\ItemService;

class ItemDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array (for detail view).
     */
    public function toArray(Request $request): array
    {
        $itemService = app(ItemService::class);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'reference' => $this->reference,
            'description' => $this->description,

            // Site info
            'site_id' => $this->site_id,

            // Supplier info
            'supplier_id' => $this->supplier_id,
            'supplier_name' => $this->supplier?->name ?? $this->supplier_name,
            'supplier_reference' => $this->supplier_reference,
            'supplier' => $this->whenLoaded('supplier', fn () => [
                'id' => $this->supplier->id,
                'name' => $this->supplier->name,
            ]),

            // Creator info
            'created_by_id' => $this->created_by_id,
            'created_by_name' => $this->created_by_name,
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'full_name' => $this->creator->full_name,
                'email' => $this->creator->email,
            ]),

            // Editor info
            'edited_by_id' => $this->edited_by_id,
            'editor' => $this->whenLoaded('editor', fn () => $this->editor ? [
                'id' => $this->editor->id,
                'full_name' => $this->editor->full_name,
            ] : null),

            // Pricing
            'purchase_price' => (float) $this->purchase_price,
            'currency' => $this->currency,

            // Stock counts
            'item_entry_total' => $this->item_entry_total,
            'item_exit_total' => $this->item_exit_total,
            'item_entry_count' => $this->item_entry_count,
            'item_exit_count' => $this->item_exit_count,
            'stock_level' => $this->item_entry_total - $this->item_exit_total,
            'stock_status' => $itemService->getStockStatus($this->resource),

            // Relation counts
            'material_count' => $this->material_count,
            'qrcode_flash_count' => $this->qrcode_flash_count,

            // Stock alerts
            'number_warning_enabled' => $this->number_warning_enabled,
            'number_warning_minimum' => $this->number_warning_minimum,
            'number_critical_enabled' => $this->number_critical_enabled,
            'number_critical_minimum' => $this->number_critical_minimum,

            // Materials (many-to-many)
            'materials' => $this->whenLoaded('materials', fn () => $this->materials->map(fn ($material) => [
                'id' => $material->id,
                'name' => $material->name,
            ])),

            // Recipients for critical alerts
            'recipients' => $this->whenLoaded('recipients', fn () => $this->recipients->map(fn ($recipient) => [
                'id' => $recipient->id,
                'full_name' => $recipient->full_name,
                'email' => $recipient->email,
            ])),

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
