<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Users;

use XetaSuite\Models\User;

class DeleteUser
{
    /**
     * Delete a user (soft delete).
     *
     * @return array{success: bool, message: string}
     */
    public function handle(User $user, User $deletedBy): array
    {
        // Check if user can be deleted (e.g., not the last admin)
        if ($this->isLastAdmin($user)) {
            return [
                'success' => false,
                'message' => __('users.cannot_delete_last_admin'),
            ];
        }

        // Store who deleted the user
        $user->deleted_by_id = $deletedBy->id;
        $user->save();

        // Soft delete
        $user->delete();

        return [
            'success' => true,
            'message' => __('users.deleted'),
        ];
    }

    /**
     * Check if the user is the last admin.
     */
    private function isLastAdmin(User $user): bool
    {
        // Get current team ID
        $teamId = getPermissionsTeamId();

        // Check if user has admin role on any site
        $hasAdminRole = false;
        foreach ($user->sites as $site) {
            setPermissionsTeamId($site->id);
            $user->unsetRelation('roles');

            if ($user->hasRole('admin')) {
                $hasAdminRole = true;

                // Count other admins on this site
                $adminCount = User::query()
                    ->where('id', '!=', $user->id)
                    ->whereNull('deleted_at')
                    ->role('admin')
                    ->count();

                if ($adminCount === 0) {
                    setPermissionsTeamId($teamId);

                    return true;
                }
            }
        }

        setPermissionsTeamId($teamId);

        return false;
    }
}
