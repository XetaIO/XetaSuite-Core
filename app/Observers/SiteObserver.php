<?php

declare(strict_types=1);

namespace XetaSuite\Observers;

use XetaSuite\Events\Site\SiteCreatedEvent;
use XetaSuite\Models\Site;

class SiteObserver
{
    /**
     * Handle the "created" event.
     */
    public function created(Site $site): void
    {
        event(new SiteCreatedEvent($site));
    }
}
