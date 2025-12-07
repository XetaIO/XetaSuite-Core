<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Sites;

use Illuminate\Support\Facades\DB;
use XetaSuite\Models\Site;
use XetaSuite\Services\SiteService;

class UpdateSite
{
    public function __construct(
        private readonly SiteService $siteService
    ) {
    }

    /**
     * Update a site.
     *
     * @param  Site  $site  The site to update.
     * @param  array  $data  The data to update the site with.
     * @return array{message: array|string|null, success: bool|array{site: Site|null, success: bool}}
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

        return DB::transaction(function () use ($site, $data) {
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
        });
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
