<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Sites;

use XetaSuite\Models\Site;

class DeleteSite
{
    /**
     * Delete a site.
     *
     * @param  Site  $site  The site to delete.
     *
     * @return array{message: array|string|null, success: bool}
     */
    public function handle(Site $site): array
    {

        if ($site->is_headquarters) {
            return [
                'success' => false,
                'message' => __('sites.cannot_delete_headquarters'),
            ];
        }

        if ($site->zones()->exists()) {
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
