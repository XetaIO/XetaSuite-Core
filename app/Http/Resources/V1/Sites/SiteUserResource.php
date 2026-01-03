<?php

declare(strict_types=1);

namespace XetaSuite\Http\Resources\V1\Sites;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiteUserResource extends JsonResource
{
    /**
     * The site ID to get roles for.
     */
    public static ?int $siteId = null;

    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        // Get roles for the specific site (site-scoped)
        $roles = [];
        if (self::$siteId) {
            setPermissionsTeamId(self::$siteId);
            $roles = $this->roles()->pluck('name')->toArray();
        }

        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'roles' => $roles,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
