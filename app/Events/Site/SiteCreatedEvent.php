<?php

declare(strict_types=1);

namespace XetaSuite\Events\Site;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use XetaSuite\Models\Site;

class SiteCreatedEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Site $site)
    {
    }
}
