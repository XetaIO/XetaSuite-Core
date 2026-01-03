<?php

declare(strict_types=1);

namespace XetaSuite\Http\Resources\V1\Roles;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use XetaSuite\Models\User;

/**
 * @mixin User
 */
class RoleUserResource extends JsonResource
{
    /**
     * Site names cache, keyed by site ID.
     *
     * @var array<int, string>
     */
    public static array $siteNames = [];

    /**
     * Set the site names cache for bulk operations.
     *
     * @param  array<int, string>  $siteNames
     */
    public static function withSiteNames(array $siteNames): void
    {
        static::$siteNames = $siteNames;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $siteId = $this->pivot?->site_id;
        $siteName = $siteId ? (static::$siteNames[$siteId] ?? null) : null;

        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'site' => $siteId ? [
                'id' => $siteId,
                'name' => $siteName,
            ] : null,
        ];
    }
}
