<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Companies;

use XetaSuite\Enums\Companies\CompanyType;
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
        // Validate and filter types to ensure they are valid CompanyType values
        $types = collect($data['types'] ?? [])
            ->filter(fn (string $type) => in_array($type, CompanyType::values()))
            ->values()
            ->toArray();

        return Company::create([
            'created_by_id' => $user->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'types' => $types,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
        ]);
    }
}
