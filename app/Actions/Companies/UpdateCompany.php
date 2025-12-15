<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Companies;

use XetaSuite\Models\Company;

class UpdateCompany
{
    /**
     * Update an existing company.
     *
     * @param  Company  $company  The company to update.
     * @param  array  $data  The data to update the company with.
     */
    public function handle(Company $company, array $data): Company
    {
        $company->update($data);

        return $company->fresh();
    }
}
