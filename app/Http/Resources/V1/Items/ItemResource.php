<?php

declare(strict_types=1);

namespace XetaSuite\Http\Resources\V1\Items;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'reference' => $this->reference,
            'description' => $this->description,

            // Stock basique
            'current_stock' => $this->current_stock,
            'stock_status' => $this->stock_status,
            'stock_status_color' => $this->stock_status_color,

            // Prix basique
            'purchase_price' => $this->purchase_price,
            'currency' => $this->currency,

            // Relations (only if loaded)
            'site' => $this->whenLoaded('site', fn() => [
                'id' => $this->site->id,
                'name' => $this->site->name,
            ]),

            'supplier' => $this->when($this->has_supplier, [
                'id' => $this->supplier_id,
                'name' => $this->supplier_display_name,
            ]),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
