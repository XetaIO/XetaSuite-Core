<?php

declare(strict_types=1);

namespace XetaSuite\Observers;

use Illuminate\Support\Facades\Auth;
use XetaSuite\Models\User;

class UserObserver
{
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
}
