<?php

declare(strict_types=1);

namespace XetaSuite\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use XetaSuite\Models\Supplier;
use XetaSuite\Models\User;

class SupplierPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user is on the headquarters site before checking specific abilities.
     * Suppliers can only be managed from the headquarters site.
     */
    public function before(User $user, string $ability): ?bool
    {
        // Suppliers can only be managed from the headquarters site
        if (! isOnHeadquarters()) {
            return false;
        }

        return null;
    }

    /**
     * Determine whether the user can view the list of suppliers.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('supplier.viewAny'); // && settings('supplier_manage_enabled', true);
    }

    /**
     * Determine whether the user can view the supplier.
     */
    public function view(User $user, Supplier $supplier): bool
    {
        return $user->can('supplier.view');
    }

    /**
     * Determine whether the user can create zones.
     */
    public function create(User $user): bool
    {
        return $user->can('supplier.create'); // && settings('supplier_create_enabled', true);
    }

    /**
     * Determine whether the user can update the supplier.
     */
    public function update(User $user, Supplier $supplier): bool
    {
        return $user->can('supplier.update');
    }

    /**
     * Determine whether the user can delete the supplier.
     */
    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->can('supplier.delete');
    }
}
