<?php

declare(strict_types=1);

namespace XetaSuite\Http\Resources\V1\Sites;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiteResource extends JsonResource
{
    /**
     * Transform the resource into an array (for list view).
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_headquarters' => $this->is_headquarters,
            'city' => $this->city,

            // Counts
            'zones_count' => $this->whenCounted('zones'),
            'users_count' => $this->whenCounted('users'),

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
