<?php

declare(strict_types=1);

namespace XetaSuite\Http\Resources\V1\Users;

use Illuminate\Http\Resources\Json\JsonResource;
use XetaSuite\Services\UserService;

class UserDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        $userService = app(UserService::class);

        return [
            'id' => $this->id,
            'username' => $this->username,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'avatar' => $this->avatar,
            'current_site_id' => $this->current_site_id ?? $this->getFirstSiteId(),
            'email' => $this->email,
            'locale' => $this->locale,
            'office_phone' => $this->office_phone,
            'cell_phone' => $this->cell_phone,
            'incident_count' => $this->incident_count,
            'maintenance_count' => $this->maintenance_count,
            'cleaning_count' => $this->cleaning_count,
            'item_count' => $this->item_count,
            'item_exit_count' => $this->item_exit_count,
            'item_entry_count' => $this->item_entry_count,
            'last_login_date' => $this->last_login_date?->toISOString(),
            'last_login_ip' => $this->last_login_ip,
            'roles' => $this->roles->pluck('name')->toArray(),
            'permissions' => $this->getAllPermissions()->pluck('name')->toArray(),
            'sites' => $this->sites->map(fn ($site) => [
                'id' => $site->id,
                'name' => $site->name,
                'is_headquarters' => $site->is_headquarters,
            ])->toArray(),
            'sites_with_roles' => $userService->getUserRolesPerSite($this->resource),
            'sites_with_permissions' => $userService->getUserPermissionsPerSite($this->resource),
            'deleted_at' => $this->deleted_at?->toISOString(),
            'deleted_by_id' => $this->deleted_by_id,
            'deleted_by' => $this->whenLoaded('deleter', function () {
                return $this->deleter ? [
                    'id' => $this->deleter->id,
                    'full_name' => $this->deleter->full_name,
                ] : null;
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
