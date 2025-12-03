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
            'address' => $this->address,
            'zip_code' => $this->zip_code,
            'city' => $this->city,
            'country' => $this->country,

            // Relationships
            'managers' => $this->whenLoaded('managers', fn () => $this->managers->map(fn ($user) => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => $user->full_name,
                'avatar' => $user->avatar,
            ])),
            'users' => $this->whenLoaded('users', fn () => $this->users->map(fn ($user) => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => $user->full_name,
                'avatar' => $user->avatar,
            ])),

            // Counts
            'zone_count' => $this->whenCounted('zones'),
            'user_count' => $this->whenCounted('users'),

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
