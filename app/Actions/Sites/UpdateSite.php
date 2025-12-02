<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Sites;

use XetaSuite\Models\Site;
use XetaSuite\Services\SiteService;

class UpdateSite
{
    public function __construct(
        private readonly SiteService $siteService
    ) {
    }

    /**
     * Update an existing site.
     *
     * @param  array{name?: string, is_headquarters?: bool, email?: string|null, office_phone?: string|null, cell_phone?: string|null, address_line_1?: string|null, address_line_2?: string|null, postal_code?: string|null, city?: string|null, country?: string|null, manager_ids?: array<int>|null}  $data
     * @return array{success: bool, site?: Site, message?: string}
     */
    public function handle(Site $site, array $data): array
    {
        // Check if trying to set headquarters when one already exists (excluding current site)
        if (($data['is_headquarters'] ?? false) && ! $site->is_headquarters) {
            if ($this->siteService->headquartersExists($site->id)) {
                return [
                    'success' => false,
                    'message' => __('sites.headquarters_already_exists'),
                ];
            }
        }

        // Prevent removing headquarters status from the only headquarters
        if ($site->is_headquarters && isset($data['is_headquarters']) && ! $data['is_headquarters']) {
            return [
                'success' => false,
                'message' => __('sites.cannot_remove_headquarters_status'),
            ];
        }

        // Extract manager_ids before updating site
        $managerIds = $data['manager_ids'] ?? null;
        unset($data['manager_ids']);

        $site->update($data);

        // Sync managers if provided
        if ($managerIds !== null) {
            $this->syncManagers($site, $managerIds);
        }

        return [
            'success' => true,
            'site' => $site->fresh(),
        ];
    }

    /**
     * Sync managers for the site.
     *
     * @param  array<int>  $managerIds
     */
    private function syncManagers(Site $site, array $managerIds): void
    {
        // Get current manager IDs
        $currentManagerIds = $site->managers()->allRelatedIds();

        // Find managers to remove (were managers, now shouldn't be)
        $toRemove = $currentManagerIds->diff($managerIds);

        // Update existing pivot records
        $site->managers()->updateExistingPivot($toRemove, ['manager' => false]);
        $site->managers()->updateExistingPivot($managerIds, ['manager' => true]);
    }
}
