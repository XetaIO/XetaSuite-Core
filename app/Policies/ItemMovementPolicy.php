<?php

declare(strict_types=1);

namespace XetaSuite\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use XetaSuite\Models\ItemMovement;
use XetaSuite\Models\User;

class ItemMovementPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any movements.
     * Movements are scoped to items, which are scoped to sites.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('item-movement.viewAny');
    }

    /**
     * Determine whether the user can view the movement.
     * User must be on the same site as the item.
     */
    public function view(User $user, ItemMovement $movement): bool
    {
        return $user->can('item-movement.view')
            && $movement->item?->site_id === $user->current_site_id;
    }

    /**
     * Determine whether the user can create movements.
     * Uses item.update permission since movements modify stock.
     */
    public function create(User $user): bool
    {
        return $user->can('item-movement.create')
            && $user->can('item.update');
    }

    /**
     * Determine whether the user can update the movement.
     * User must be on the same site as the item.
     */
    public function update(User $user, ItemMovement $movement): bool
    {
        return $user->can('item-movement.update')
            && $movement->item?->site_id === $user->current_site_id;
    }

    /**
     * Determine whether the user can delete the movement.
     * User must be on the same site as the item.
     */
    public function delete(User $user, ItemMovement $movement): bool
    {
        return $user->can('item-movement.delete')
            && $movement->item?->site_id === $user->current_site_id;
    }
}
