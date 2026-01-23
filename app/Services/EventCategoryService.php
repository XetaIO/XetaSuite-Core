<?php

declare(strict_types=1);

namespace XetaSuite\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use XetaSuite\Models\EventCategory;

class EventCategoryService
{
    /**
     * Get paginated event categories for the current user's site.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<EventCategory>
     */
    public function getPaginatedCategories(array $filters = []): LengthAwarePaginator
    {
        $user = auth()->user();

        $query = EventCategory::query()
            ->where('site_id', $user->current_site_id)
            ->orderBy($filters['sort_by'] ?? 'name', $filters['sort_direction'] ?? 'asc');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('description', 'ILIKE', "%{$search}%");
            });
        }

        return $query->paginate(request('per_page', 15));
    }
}
