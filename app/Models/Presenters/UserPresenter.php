<?php

declare(strict_types=1);

namespace XetaSuite\Models\Presenters;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Laravolt\Avatar\Facade as Avatar;
use XetaSuite\Models\Session;

trait UserPresenter
{
    /**
     * Get the user's avatar.
     */
    protected function avatar(): Attribute
    {
        return Attribute::make(
            get: function () {
                return Avatar::create($this->full_name)->toBase64(); // TODO : Cache  the avatar or save it into database at creations
            }
        );
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

    /**
     * Get the user show url.
     */
    public function getShowUrlAttribute(): string
    {
        if ($this->getKey() === null) {
            return '';
        }

        return route('users.show', $this);
    }
}
