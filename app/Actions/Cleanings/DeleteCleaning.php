<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Cleanings;

use XetaSuite\Models\Cleaning;

class DeleteCleaning
{
    /**
     * Delete a cleaning.
     *
     * @param  Cleaning  $cleaning  The cleaning to delete.
     */
    public function handle(Cleaning $cleaning): void
    {
        $cleaning->delete();
    }
}
