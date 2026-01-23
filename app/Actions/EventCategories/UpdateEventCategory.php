<?php

declare(strict_types=1);

namespace XetaSuite\Actions\EventCategories;

use XetaSuite\Models\EventCategory;
use XetaSuite\Models\User;

class UpdateEventCategory
{
    /**
     * Update an event category.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(EventCategory $category, User $user, array $data): EventCategory
    {
        $category->update([
            'name' => $data['name'] ?? $category->name,
            'color' => $data['color'] ?? $category->color,
            'description' => $data['description'] ?? $category->description,
        ]);

        return $category->fresh();
    }
}
