<?php

declare(strict_types=1);

namespace XetaSuite\Http\Resources\V1\Materials;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaterialResource extends JsonResource
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

            // Zone info
            'zone_id' => $this->zone_id,
            'zone' => $this->whenLoaded('zone', fn () => [
                'id' => $this->zone->id,
                'name' => $this->zone->name,
            ]),

            // Creator info
            'created_by_id' => $this->created_by_id,
            'created_by_name' => $this->created_by_name,
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'full_name' => $this->creator->full_name,
            ]),

            // Cleaning alert info
            'cleaning_alert' => $this->cleaning_alert,
            'cleaning_alert_email' => $this->cleaning_alert_email,
            'last_cleaning_at' => $this->last_cleaning_at?->toISOString(),

            // Counts
            'incident_count' => $this->incident_count,
            'maintenance_count' => $this->maintenance_count,
            'cleaning_count' => $this->cleaning_count,
            'item_count' => $this->item_count,

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
