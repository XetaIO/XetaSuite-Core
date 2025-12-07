<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Suppliers;

use Illuminate\Support\Facades\DB;
use XetaSuite\Models\Supplier;

class DeleteSupplier
{
    /**
     * Delete a supplier.
     *
     * @param  Supplier  $supplier  The supplier to delete.
     * @return array{message: array|string|null, success: bool}
     */
    public function handle(Supplier $supplier): array
    {
        if ($supplier->items()->exists()) {
            return [
                'success' => false,
                'message' => __('suppliers.cannot_delete_has_items'),
            ];
        }

        return DB::transaction(function () use ($supplier) {
            $supplier->delete();

            return [
                'success' => true,
                'message' => __('suppliers.deleted'),
            ];
        });
    }
}
