<?php

declare(strict_types=1);

namespace XetaSuite\Http\Resources\V1\Items;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemMovementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price ? (float) $this->unit_price : null,
            'total_price' => $this->total_price ? (float) $this->total_price : null,

            // Supplier info
            'supplier_id' => $this->supplier_id,
            'supplier_name' => $this->supplier?->name ?? $this->supplier_name,
            'supplier_invoice_number' => $this->supplier_invoice_number,
            'invoice_date' => $this->invoice_date?->toDateString(),

            // Creator info
            'created_by_id' => $this->created_by_id,
            'created_by_name' => $this->creator?->full_name ?? $this->created_by_name,

            // Related entity (maintenance, etc.)
            'movable_type' => $this->movable_type,
            'movable_id' => $this->movable_id,

            'notes' => $this->notes,
            'movement_date' => $this->movement_date?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
