<?php

declare(strict_types=1);

namespace XetaSuite\Http\Resources\V1\Suppliers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use XetaSuite\Http\Resources\V1\Users\UserResource;

class SupplierDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array (for detail view).
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,

            // Creator
            'created_by_id' => $this->created_by_id,
            'created_by_name' => $this->created_by_name,

            'creator' => $this->whenLoaded('creator', fn () => new UserResource($this->creator)),

            // Items count (items loaded separately via /suppliers/{id}/items)
            'item_count' => $this->item_count,

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
