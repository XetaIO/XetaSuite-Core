<?php

declare(strict_types=1);

namespace XetaSuite\Http\Resources\V1\Users;

use Illuminate\Http\Resources\Json\JsonResource;

class UserDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'current_site_id' => $this->current_site_id ?? $this->getFirstSiteId(),
            'email' => $this->email,
            'locale' => $this->locale,
            'roles' => $this->roles->pluck('name')->toArray(),
            'permissions' => $this->getAllPermissions()->pluck('name')->toArray(),
            'sites' => $this->sites->map(function ($site) {
                return [
                    'id' => $site->id,
                    'name' => $site->name,
                    'is_headquarters' => $site->is_headquarters,
                ];
            })->toArray(),
        ];
    }
}
