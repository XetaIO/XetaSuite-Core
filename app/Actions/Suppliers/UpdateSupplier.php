<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Suppliers;

use XetaSuite\Models\Supplier;

class UpdateSupplier
{
    /**
     * Update an existing supplier.
     *
     * @param  Supplier  $supplier  The supplier to update.
     * @param  array  $data  The data to update the supplier with.
     */
    public function handle(Supplier $supplier, array $data): Supplier
    {
        $supplier->update($data);

        return $supplier->fresh();
    }
}
