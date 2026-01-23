<?php

declare(strict_types=1);

namespace XetaSuite\Actions\EventCategories;

use Illuminate\Support\Facades\DB;
use XetaSuite\Models\EventCategory;
use XetaSuite\Models\User;

class CreateEventCategory
{
    /**
     * Create a new event category.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(User $user, array $data): EventCategory
    {
        return DB::transaction(function () use ($user, $data) {
            return EventCategory::create([
                'site_id' => $user->current_site_id,
                'name' => $data['name'],
                'color' => $data['color'] ?? '#465fff',
                'description' => $data['description'] ?? null,
            ]);
        });
    }
}
