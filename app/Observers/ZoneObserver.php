<?php

declare(strict_types=1);

namespace XetaSuite\Observers;

use XetaSuite\Models\Zone;

class ZoneObserver
{
    /**
     * Handle the "deleting" event.
     *
     * Prevent deletion if the zone has materials or child zones.
     */
    public function deleting(Zone $zone): bool
    {
        return $zone->materials()->doesntExist() && $zone->children()->doesntExist();
    }
}
