<?php

declare(strict_types=1);

namespace XetaSuite\Http\Resources\V1\Users;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'email'       => $this->email,
            //'roles'       => $this->roles->pluck('name'),
            //'permissions' => $this->getAllPermissions()->pluck('name'),
        ];
    }
}
