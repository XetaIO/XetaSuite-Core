<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Suppliers;

use XetaSuite\Models\Supplier;
use XetaSuite\Services\SupplierService;

class DeleteSupplier
{
    public function __construct(
        private readonly SupplierService $supplierService
    ) {}

    /**
     * Delete a supplier.
     *
     * @return array{success: bool, message: string}
     */
    public function handle(Supplier $supplier): array
    {
        if (! $this->supplierService->canDelete($supplier)) {
            return [
                'success' => false,
                'message' => __('suppliers.cannot_delete_has_items'),
            ];
        }

        $supplier->delete();

        return [
            'success' => true,
            'message' => __('suppliers.deleted'),
        ];
    }
}
