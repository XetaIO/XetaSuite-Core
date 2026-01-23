<?php

declare(strict_types=1);

namespace XetaSuite\Actions\EventCategories;

use XetaSuite\Models\EventCategory;

class DeleteEventCategory
{
    /**
     * Delete an event category.
     */
    public function handle(EventCategory $category): void
    {
        $category->delete();
    }
}
