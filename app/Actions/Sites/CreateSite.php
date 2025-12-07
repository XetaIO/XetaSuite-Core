<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Sites;

use Illuminate\Support\Facades\DB;
use XetaSuite\Models\Site;
use XetaSuite\Services\SiteService;

class CreateSite
{
    public function __construct(
        private readonly SiteService $siteService
    ) {
    }

    /**
     * Create a new site.
     *
     * @param array $data The data for the new site.
     *
     * @return array
     */
    public function handle(array $data): array
    {
        return DB::transaction(function () use ($data) {
            // Check if trying to create a headquarters when one already exists
            if (($data['is_headquarters'] ?? false) && $this->siteService->headquartersExists()) {
                return [
                    'success' => false,
                    'message' => __('sites.headquarters_already_exists'),
                ];
            }

            $site = Site::create([
                'name' => $data['name'],
                'is_headquarters' => $data['is_headquarters'] ?? false,
                'email' => $data['email'] ?? null,
                'office_phone' => $data['office_phone'] ?? null,
                'cell_phone' => $data['cell_phone'] ?? null,
                'address' => $data['address'] ?? null,
                'zip_code' => $data['zip_code'] ?? null,
                'city' => $data['city'] ?? null,
                'country' => $data['country'] ?? null,
            ]);

            // Sync managers if provided
            if (isset($data['manager_ids']) && is_array($data['manager_ids'])) {
                $this->syncManagers($site, $data['manager_ids']);
            }

            return [
                'success' => true,
                'site' => $site,
            ];
        });
    }

    /**
     * Sync managers for the site.
     *
     * @param  array $managerIds The IDs of the users to be set as managers.
     */
    private function syncManagers(Site $site, array $managerIds): void
    {
        // Build pivot data with manager flag
        $syncData = [];
        foreach ($managerIds as $userId) {
            $syncData[$userId] = ['manager' => true];
        }

        // Sync only managers (preserving non-manager users)
        $currentManagerIds = $site->managers()->pluck('users.id')->toArray();

        // Remove old managers
        $site->users()->detach($currentManagerIds);

        // Attach new managers
        $site->users()->attach($syncData);
    }
}
