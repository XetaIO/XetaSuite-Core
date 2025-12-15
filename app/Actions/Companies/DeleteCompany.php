<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Companies;

use Illuminate\Support\Facades\DB;
use XetaSuite\Models\Company;

class DeleteCompany
{
    /**
     * Delete a company.
     *
     * @param  Company  $company  The company to delete.
     * @return array{message: array|string|null, success: bool}
     */
    public function handle(Company $company): array
    {
        // Check if company has maintenances
        if ($company->maintenances()->exists()) {
            return [
                'success' => false,
                'message' => __('companies.cannot_delete_has_maintenances'),
            ];
        }

        return DB::transaction(function () use ($company) {
            $company->delete();

            return [
                'success' => true,
                'message' => __('companies.deleted'),
            ];
        });
    }
}
