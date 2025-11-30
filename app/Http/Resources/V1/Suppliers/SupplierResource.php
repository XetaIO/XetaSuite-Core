<?php

declare(strict_types=1);

namespace XetaSuite\Http\Resources\V1\Suppliers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    /**
     * Transform the resource into an array (for list view).
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'item_count' => $this->item_count,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
