<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Users;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use XetaSuite\Models\User;

class UpdateUser
{
    /**
     * Update an existing user.
     *
     * @param  array{
     *     username?: string,
     *     email?: string,
     *     first_name?: string,
     *     last_name?: string,
     *     password?: string,
     *     locale?: string,
     *     office_phone?: string,
     *     cell_phone?: string,
     *     end_employment_contract?: string,
     *     sites?: array<int, array{id: int, roles?: array<string>, permissions?: array<string>}>,
     * }  $data
     */
    public function handle(User $user, array $data): User
    {
        $updateData = [
            'username' => $data['username'] ?? $user->username,
            'email' => $data['email'] ?? $user->email,
            'first_name' => $data['first_name'] ?? $user->first_name,
            'last_name' => $data['last_name'] ?? $user->last_name,
            'locale' => $data['locale'] ?? $user->locale,
            'office_phone' => $data['office_phone'] ?? $user->office_phone,
            'cell_phone' => $data['cell_phone'] ?? $user->cell_phone,
            'end_employment_contract' => $data['end_employment_contract'] ?? $user->end_employment_contract,
        ];

        // Only update password if provided
        if (! empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);

        // Update sites with roles and permissions if provided
        if (array_key_exists('sites', $data)) {
            $this->updateSitesWithRolesAndPermissions($user, $data['sites'] ?? []);
        }

        return $user->fresh(['sites']);
    }

    /**
     * Update sites with their roles and permissions for the user.
     *
     * @param  array<int, array{id: int, roles?: array<string>, permissions?: array<string>}>  $sites
     */
    private function updateSitesWithRolesAndPermissions(User $user, array $sites): void
    {
        $siteIds = Arr::pluck($sites, 'id');

        // Get current site IDs to find removed sites
        $currentSiteIds = $user->sites()->pluck('sites.id')->toArray();
        $removedSiteIds = array_diff($currentSiteIds, $siteIds);

        $teamId = getPermissionsTeamId();

        // Remove roles and permissions from removed sites
        foreach ($removedSiteIds as $removedSiteId) {
            setPermissionsTeamId($removedSiteId);
            $user->syncRoles([]);
            $user->syncPermissions([]);
        }

        // Sync sites
        $user->sites()->sync($siteIds);

        // Update current_site_id if it was removed
        if (! empty($siteIds) && ! in_array($user->current_site_id, $siteIds, true)) {
            $user->update(['current_site_id' => $siteIds[0]]);
        } elseif (empty($siteIds)) {
            $user->update(['current_site_id' => null]);
        }

        // Assign roles and permissions for each site
        foreach ($sites as $siteData) {
            $siteId = $siteData['id'];
            setPermissionsTeamId($siteId);

            // Sync roles for this site
            $user->syncRoles($siteData['roles'] ?? []);

            // Sync direct permissions for this site
            $user->syncPermissions($siteData['permissions'] ?? []);
        }

        setPermissionsTeamId($teamId);
    }
}
