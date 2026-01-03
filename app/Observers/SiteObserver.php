<?php

declare(strict_types=1);

namespace XetaSuite\Observers;

use XetaSuite\Models\Site;

class SiteObserver
{
    /**
     * Handle the "deleting" event.
     *
     * Prevent deletion if the site has related records.
     */
    public function deleting(Site $site): bool
    {
        return $site->zones()->doesntExist();
    }
}
