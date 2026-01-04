<?php

declare(strict_types=1);

namespace XetaSuite\Models;

use Illuminate\Database\Eloquent\Builder;

class Session extends Model
{
    /**
     * Indicates if the ID are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'payload',
        'url',
        'method',
        'last_activity',
        'created_at'
    ];

    /**
     * Scope a query to only include non-expired session.
     *
     * @param  Builder  $query
     *
     * @return Builder
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function expires(Builder $query): Builder
    {
        $timeout = 5; // Timeout in minutes
        $expire = time() - (60 * $timeout);

        return $query->where('last_activity', '>=', $expire);
    }
}
