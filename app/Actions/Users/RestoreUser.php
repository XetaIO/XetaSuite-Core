<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Users;

use XetaSuite\Models\User;

class RestoreUser
{
    /**
     * Restore a soft-deleted user.
     *
     * @return array{success: bool, message: string}
     */
    public function handle(User $user): array
    {
        if (! $user->trashed()) {
            return [
                'success' => false,
                'message' => __('users.not_deleted'),
            ];
        }

        // Clear deletion info
        $user->deleted_by_id = null;
        $user->save();

        // Restore user
        $user->restore();

        return [
            'success' => true,
            'message' => __('users.restored'),
        ];
    }
}
