<?php

declare(strict_types=1);

namespace XetaSuite\Http\Resources\V1\Cleanings;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \XetaSuite\Models\Cleaning
 */
class CleaningResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,

            // Enum
            'type' => $this->type->value,
            'type_label' => $this->type->label(),

            // Material info
            'material_id' => $this->material_id,
            'material_name' => $this->material?->name ?? $this->material_name,
            'material' => $this->when($this->relationLoaded('material') && $this->material, [
                'id' => $this->material?->id,
                'name' => $this->material?->name,
            ]),

            // Creator info
            'created_by_id' => $this->created_by_id,
            'created_by_name' => $this->creator?->full_name ?? $this->created_by_name,
            'creator' => $this->when($this->relationLoaded('creator') && $this->creator, [
                'id' => $this->creator?->id,
                'full_name' => $this->creator?->full_name,
            ]),

            // Timestamps
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
