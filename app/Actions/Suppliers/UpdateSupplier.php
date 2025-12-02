<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Suppliers;

use XetaSuite\Models\Supplier;

class UpdateSupplier
{
    /**
     * Update an existing supplier.
     *
     * @param  array{name?: string, description?: string|null}  $data
     */
    public function handle(Supplier $supplier, array $data): Supplier
    {
        $supplier->update($data);

        return $supplier->fresh();
    }
}
