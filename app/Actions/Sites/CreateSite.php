<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Sites;

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
     * @param  array{name: string, is_headquarters?: bool, email?: string|null, office_phone?: string|null, cell_phone?: string|null, address_line_1?: string|null, address_line_2?: string|null, postal_code?: string|null, city?: string|null, country?: string|null, manager_ids?: array<int>|null}  $data
     * @return array{success: bool, site?: Site, message?: string}
     */
    public function handle(array $data): array
    {
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
            'address_line_1' => $data['address_line_1'] ?? null,
            'address_line_2' => $data['address_line_2'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
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
    }

    /**
     * Sync managers for the site.
     *
     * @param  array<int>  $managerIds
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
