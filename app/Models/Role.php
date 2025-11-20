<?php

declare(strict_types=1);

namespace XetaSuite\Models;

use \Spatie\Permission\Models\Role as SpatieRole;
use XetaSuite\Models\Presenters\RolePresenter;

class Role extends SpatieRole
{
    use RolePresenter;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'formatted_color',
    ];

}
