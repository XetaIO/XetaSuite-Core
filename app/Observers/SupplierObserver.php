<?php

declare(strict_types=1);

namespace XetaSuite\Observers;

use XetaSuite\Models\Item;
use XetaSuite\Models\ItemMovement;
use XetaSuite\Models\ItemPrice;
use XetaSuite\Models\Supplier;

class SupplierObserver
{
    /**
     * Handle the "deleting" event.
     *
     * Save the supplier name in related records before the FK is set to null.
     */
    public function deleting(Supplier $supplier): void
    {
        $name = $supplier->name;

        Item::query()
            ->where('supplier_id', $supplier->id)
            ->update(['supplier_name' => $name]);

        ItemMovement::query()
            ->where('supplier_id', $supplier->id)
            ->update(['supplier_name' => $name]);

        ItemPrice::query()
            ->where('supplier_id', $supplier->id)
            ->update(['supplier_name' => $name]);
    }
}
