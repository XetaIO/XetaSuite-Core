<?php

declare(strict_types=1);

namespace XetaSuite\Http\Resources\V1\Sites;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiteDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array (for detail view).
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_headquarters' => $this->is_headquarters,

            // Contact info
            'email' => $this->email,
            'office_phone' => $this->office_phone,
            'cell_phone' => $this->cell_phone,

            // Address
            'address_line_1' => $this->address_line_1,
            'address_line_2' => $this->address_line_2,
            'postal_code' => $this->postal_code,
            'city' => $this->city,
            'country' => $this->country,

            // Counts
            'zones_count' => $this->whenCounted('zones'),
            'users_count' => $this->whenCounted('users'),

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
