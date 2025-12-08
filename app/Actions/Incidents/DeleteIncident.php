<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Incidents;

use XetaSuite\Models\Incident;

class DeleteIncident
{
    /**
     * Delete an incident.
     *
     * @param  Incident  $incident  The incident to delete.
     */
    public function handle(Incident $incident): bool
    {
        return $incident->delete();
    }
}
