<?php

declare(strict_types=1);

namespace XetaSuite\Models\Presenters;

use Illuminate\Database\Eloquent\Casts\Attribute;

trait RolePresenter
{
    /**
     * The default color used for a role without color.
     */
    protected string $defaultColor = '';

    /**
     * Get the color of the role.
     */
    protected function formattedColor(): Attribute
    {
        return Attribute::make(
            get: function () {
                $color = $this->color ?: $this->defaultColor;

                return 'color:'.$color.';';
            }
        );
    }
}
