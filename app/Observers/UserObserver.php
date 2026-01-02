<?php

declare(strict_types=1);

namespace XetaSuite\Observers;

use Illuminate\Support\Facades\Auth;
use XetaSuite\Models\Cleaning;
use XetaSuite\Models\Company;
use XetaSuite\Models\Incident;
use XetaSuite\Models\Item;
use XetaSuite\Models\ItemMovement;
use XetaSuite\Models\ItemPrice;
use XetaSuite\Models\Maintenance;
use XetaSuite\Models\Material;
use XetaSuite\Models\Supplier;
use XetaSuite\Models\User;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $user->sendEmailRegisteredNotification();
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        //Invalidate avatar cache when name changes.
        if ($user->wasChanged(['first_name', 'last_name'])) {
            $user->clearAvatarCache();
        }
    }

    /**
     * Handle the User "deleting" event.
     */
    public function deleting(User $user): void
    {
        if (Auth::check()) {
            $user->deleted_by_id = Auth::id();
            $user->saveQuietly();
        }
    }

    /**
     * Handle the User "restoring" event.
     */
    public function restoring(User $user): void
    {
        $user->deleted_by_id = null;
        $user->saveQuietly();
    }

    /**
     * Handle the User "forceDeleting" event.
     *
     * Save the user's full name in related records before the FK is set to null.
     */
    public function forceDeleting(User $user): void
    {
        $name = $user->full_name;

        // Tables with created_by_id
        Item::query()
            ->where('created_by_id', $user->id)
            ->update(['created_by_name' => $name]);

        ItemMovement::query()
            ->where('created_by_id', $user->id)
            ->update(['created_by_name' => $name]);

        ItemPrice::query()
            ->where('created_by_id', $user->id)
            ->update(['created_by_name' => $name]);

        Cleaning::query()
            ->where('created_by_id', $user->id)
            ->update(['created_by_name' => $name]);

        Maintenance::query()
            ->where('created_by_id', $user->id)
            ->update(['created_by_name' => $name]);

        Material::query()
            ->where('created_by_id', $user->id)
            ->update(['created_by_name' => $name]);

        Supplier::query()
            ->where('created_by_id', $user->id)
            ->update(['created_by_name' => $name]);

        Company::query()
            ->where('created_by_id', $user->id)
            ->update(['created_by_name' => $name]);

        // Tables with reported_by_id
        Incident::query()
            ->where('reported_by_id', $user->id)
            ->update(['reported_by_name' => $name]);
    }
}
