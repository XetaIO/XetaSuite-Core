<?php

declare(strict_types=1);

namespace XetaSuite\Http\Resources\V1\Permissions;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionRoleResource extends JsonResource
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
            'users_count' => $this->users()->count(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
