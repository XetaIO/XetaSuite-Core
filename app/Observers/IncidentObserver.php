<?php

declare(strict_types=1);

namespace XetaSuite\Observers;

use Illuminate\Support\Facades\Auth;
use XetaSuite\Models\Incident;

class IncidentObserver
{
    /**
     * Handle the "creating" event.
     */
    public function creating(Incident $incident): void
    {
        $incident->user_id = Auth::id();
        $incident->site_id = getPermissionsTeamId();
    }

    /**
     * Handle the "updating" event.
     */
    public function updating(Incident $incident): void
    {
        $incident->edited_by_id = Auth::id();
    }
}
