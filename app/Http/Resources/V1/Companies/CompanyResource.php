<?php

declare(strict_types=1);

namespace XetaSuite\Http\Resources\V1\Companies;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
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
            'types' => $this->types,
            'is_item_provider' => $this->isItemProvider(),
            'is_maintenance_provider' => $this->isMaintenanceProvider(),
            'item_count' => $this->items_count ?? $this->item_count ?? 0,
            'maintenance_count' => $this->maintenances_count ?? $this->maintenance_count ?? 0,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
