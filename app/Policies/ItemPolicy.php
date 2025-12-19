<?php

declare(strict_types=1);

namespace XetaSuite\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use XetaSuite\Models\Item;
use XetaSuite\Models\User;

class ItemPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): bool|null
    {
        // Disallow any creation, modification and deletion from HQ
        if (isOnHeadquarters() && in_array($ability, ['create', 'update', 'delete'], true)) {
            return false;
        }

        // HQ : can see an item
        if (isOnHeadquarters() && $ability === 'view') {
            return $user->can('item.view');
        }

        return null;
    }

    /**
     * Determine whether the user can view the list of items.
     * Items are filtered by current_site_id in the controller.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('item.viewAny');
    }

    /**
     * Determine whether the user can view an item.
     * User must be on the same site as the item.
     */
    public function view(User $user, Item $item): bool
    {
        return $user->can('item.viewAny')
            && $item->site_id === $user->current_site_id;
    }

    /**
     * Determine whether the user can create items.
     */
    public function create(User $user): bool
    {
        return $user->can('item.create');
    }

    /**
     * Determine whether the user can update the item.
     * User must be on the same site as the item.
     */
    public function update(User $user, Item $item): bool
    {
        if (isOnHeadquarters()) {
            // Disallow any modification from HQ
            return false;
        }
        return $user->can('item.update')
            && $item->site_id === $user->current_site_id;
    }

    /**
     * Determine whether the user can delete the item.
     * User must be on the same site as the item.
     */
    public function delete(User $user, Item $item): bool
    {
        if (isOnHeadquarters()) {
            // Disallow any modification from HQ
            return false;
        }
        return $user->can('item.delete')
            && $item->site_id === $user->current_site_id;
    }

    /**
     * Determine whether the user can generate a QR code for the item.
     * User must be on the same site as the item.
     */
    public function generateQrCode(User $user, Item $item): bool
    {
        return $user->can('item.generateQrCode')
            && $item->site_id === $user->current_site_id;
    }

    /**
     * Determine whether the user can scan QrCode for the model.
     */
    public function scanQrCode(User $user): bool
    {
        return $user->can('item.scanQrCode');
    }
}
