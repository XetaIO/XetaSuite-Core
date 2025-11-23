<?php

declare(strict_types=1);

namespace XetaSuite\Observers;

use XetaSuite\Models\Company;

class CompanyObserver
{
    /**
     * Handle the "deleted" event.
     */
    public function deleted(Company $company): void
    {
        $company->maintenances()->detach();
    }
}
