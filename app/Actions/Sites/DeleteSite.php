<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Sites;

use XetaSuite\Models\Site;
use XetaSuite\Services\SiteService;

class DeleteSite
{
    public function __construct(
        private readonly SiteService $siteService
    ) {
    }

    /**
     * Delete a site.
     *
     * @return array{success: bool, message: string}
     */
    public function handle(Site $site): array
    {
        if (! $this->siteService->canDelete($site)) {
            if ($site->is_headquarters) {
                return [
                    'success' => false,
                    'message' => __('sites.cannot_delete_headquarters'),
                ];
            }

            return [
                'success' => false,
                'message' => __('sites.cannot_delete_has_zones'),
            ];
        }

        $site->delete();

        return [
            'success' => true,
            'message' => __('sites.deleted'),
        ];
    }
}
