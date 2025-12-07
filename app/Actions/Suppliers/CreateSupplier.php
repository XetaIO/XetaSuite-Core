<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Suppliers;

use XetaSuite\Models\Supplier;
use XetaSuite\Models\User;

class CreateSupplier
{
    /**
     * Create a new supplier.
     *
     * @param  User  $user  The user creating the supplier.
     * @param  array  $data  The data for the new supplier.
     */
    public function handle(User $user, array $data): Supplier
    {
        return Supplier::create([
            'created_by_id' => $user->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);
    }
}
