<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Users;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use XetaSuite\Models\User;

class CreateUser
{
    /**
     * Create a new user.
     *
     * @param  array{
     *     username: string,
     *     email: string,
     *     first_name: string,
     *     last_name: string,
     *     password?: string,
     *     locale?: string,
     *     office_phone?: string,
     *     cell_phone?: string,
     *     sites?: array<int, array{id: int, roles?: array<string>, permissions?: array<string>}>,
     * }  $data
     */
    public function handle(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'username' => $data['username'],
                'email' => $data['email'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'password' => isset($data['password']) ? Hash::make($data['password']) : null,
                'locale' => $data['locale'] ?? 'fr',
                'office_phone' => $data['office_phone'] ?? null,
                'cell_phone' => $data['cell_phone'] ?? null
            ]);

            // Assign sites with roles and permissions
            if (! empty($data['sites'])) {
                $this->assignSitesWithRolesAndPermissions($user, $data['sites']);
            }

            return $user->fresh(['sites']);
        });
    }

    /**
     * Assign sites with their roles and permissions to the user.
     *
     * @param  array<int, array{id: int, roles?: array<string>, permissions?: array<string>}>  $sites
     */
    private function assignSitesWithRolesAndPermissions(User $user, array $sites): void
    {
        $siteIds = Arr::pluck($sites, 'id');
        $user->sites()->sync($siteIds);

        // Set the first site as current site
        if (! $user->current_site_id && count($siteIds) > 0) {
            $user->update(['current_site_id' => $siteIds[0]]);
        }

        $teamId = getPermissionsTeamId();

        foreach ($sites as $siteData) {
            $siteId = $siteData['id'];
            setPermissionsTeamId($siteId);

            // Assign roles for this site
            if (! empty($siteData['roles'])) {
                $user->syncRoles($siteData['roles']);
            }

            // Assign direct permissions for this site
            if (! empty($siteData['permissions'])) {
                $user->syncPermissions($siteData['permissions']);
            }
        }

        setPermissionsTeamId($teamId);
    }
}
