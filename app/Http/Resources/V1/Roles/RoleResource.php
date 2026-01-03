<?php

declare(strict_types=1);

namespace XetaSuite\Http\Resources\V1\Roles;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Spatie\Permission\Models\Role
 */
class RoleResource extends JsonResource
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
            'name' => $this->name,
            'guard_name' => $this->guard_name,
            'level' => $this->level,
            'site_id' => $this->site_id,
            'site' => $this->when($this->site_id !== null, function () {
                return $this->site ? [
                    'id' => $this->site->id,
                    'name' => $this->site->name,
                ] : null;
            }),
            'permissions_count' => $this->whenCounted('permissions', $this->permissions_count ?? $this->permissions->count()),
            'users_count' => $this->whenCounted('users', $this->users_count ?? 0),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
