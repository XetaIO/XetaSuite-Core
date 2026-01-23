<?php

declare(strict_types=1);

namespace XetaSuite\Models\Presenters;

use Illuminate\Database\Eloquent\Casts\Attribute;

trait CalendarEventPresenter
{
    /**
     * Get the color (event color or category color, with fallback).
     */
    protected function color(): Attribute
    {
        return Attribute::make(
            get: function () {
                // Prioritize event's own color if set
                if (! empty($this->attributes['color'])) {
                    return $this->attributes['color'];
                }

                // Fall back to category color, then default
                return $this->eventCategory?->color ?? '#465fff';
            }
        );
    }
}
