<?php

declare(strict_types=1);

namespace XetaSuite\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use XetaSuite\Models\Company;
use XetaSuite\Models\User;

class CompanyPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the list of companies.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('company.viewAny');
    }

    /**
     * Determine whether the user can view the company.
     */
    public function view(User $user, Company $company): bool
    {
        return $user->can('company.view');
    }

    /**
     * Determine whether the user can create companies.
     */
    public function create(User $user): bool
    {
        // Only HQ can create companies
        if (isOnHeadquarters()) {
            return $user->can('company.create');
        }
        return false;
    }

    /**
     * Determine whether the user can update the company.
     */
    public function update(User $user, Company $company): bool
    {
        // Only HQ can update companies
        if (isOnHeadquarters()) {
            return $user->can('company.update');
        }
        return false;
    }

    /**
     * Determine whether the user can delete the company.
     */
    public function delete(User $user, Company $company): bool
    {
        // Only HQ can delete companies
        if (isOnHeadquarters()) {
            return $user->can('company.delete');
        }
        return false;
    }
}
