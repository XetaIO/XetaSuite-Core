<?php

declare(strict_types=1);

namespace XetaSuite\Http\Resources\V1\Companies;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use XetaSuite\Http\Resources\V1\Users\UserResource;

class CompanyDetailResource extends JsonResource
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

            // Types
            'types' => $this->types,
            'is_item_provider' => $this->isItemProvider(),
            'is_maintenance_provider' => $this->isMaintenanceProvider(),

            // Contact info
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,

            // Creator
            'created_by_id' => $this->created_by_id,
            'created_by_name' => $this->created_by_name,

            'creator' => $this->whenLoaded('creator', fn () => new UserResource($this->creator)),

            // Counts
            'item_count' => $this->items_count ?? $this->item_count ?? 0,
            'maintenance_count' => $this->maintenances_count ?? $this->maintenance_count ?? 0,

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
