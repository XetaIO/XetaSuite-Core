<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Companies;

use XetaSuite\Models\Company;
use XetaSuite\Models\User;

class CreateCompany
{
    /**
     * Create a new company.
     *
     * @param  User  $user  The user creating the company.
     * @param  array  $data  The data for the new company.
     */
    public function handle(User $user, array $data): Company
    {
        return Company::create([
            'created_by_id' => $user->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);
    }
}
