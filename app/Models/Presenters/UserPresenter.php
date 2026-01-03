<?php

declare(strict_types=1);

namespace XetaSuite\Models\Presenters;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Cache;
use Laravolt\Avatar\Facade as Avatar;
use XetaSuite\Models\Session;

trait UserPresenter
{
    /**
     * Get the user's avatar.
     * Cached for 7 days, invalidated when name changes via UserObserver.
     */
    protected function avatar(): Attribute
    {
        return Attribute::make(
            get: fn () => Cache::remember(
                $this->getAvatarCacheKey(),
                now()->addDays(7),
                fn () => Avatar::create($this->full_name)->toBase64()
            )
        );
    }

    /**
     * Get the cache key for this user's avatar.
     */
    public function getAvatarCacheKey(): string
    {
        return "user:{$this->id}:avatar";
    }

    /**
     * Clear the cached avatar.
     */
    public function clearAvatarCache(): void
    {
        Cache::forget($this->getAvatarCacheKey());
    }

    /**
     * Get the status of the user : online or offline
     */
    protected function online(): Attribute
    {
        return Attribute::make(
            get: fn () => Session::expires()->where('user_id', $this->id)->exists()
        );
    }

    /**
     * Get the max role level of the user.
     */
    protected function level(): Attribute
    {
        return Attribute::make(
            get: fn () => ($role = $this->roles->sortByDesc('level')->first()) ? $role->level : 0
        );
    }

    /**
     * Get the account full name. Return the username if the user
     * has not set his first name and last name.
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: function () {
                $fullName = $this->first_name.' '.$this->last_name;

                if (empty(trim($fullName))) {
                    return $this->username;
                }

                return $fullName;
            }
        );
    }
}
