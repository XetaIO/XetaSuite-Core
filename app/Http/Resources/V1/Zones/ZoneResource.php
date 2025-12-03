<?php

declare(strict_types=1);

namespace XetaSuite\Http\Resources\V1\Zones;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ZoneResource extends JsonResource
{
    /**
     * Transform the resource into an array (for list view).
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'allow_material' => $this->allow_material,

            // Parent info
            'parent_id' => $this->parent_id,
            'parent' => $this->whenLoaded('parent', fn () => [
                'id' => $this->parent->id,
                'name' => $this->parent->name,
            ]),

            // Site info
            'site_id' => $this->site_id,
            'site' => $this->whenLoaded('site', fn () => [
                'id' => $this->site->id,
                'name' => $this->site->name,
            ]),

            // Counts
            'children_count' => $this->whenCounted('children'),
            'materials_count' => $this->whenCounted('materials'),

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
