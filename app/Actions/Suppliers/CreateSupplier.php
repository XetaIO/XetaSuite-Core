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
     * @param  array{name: string, description?: string|null}  $data
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
