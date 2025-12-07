<?php

declare(strict_types=1);

return [
    // Model labels
    'item' => 'Item',
    'items' => 'Items',

    // Fields
    'name' => 'Name',
    'reference' => 'Reference',
    'description' => 'Description',
    'supplier' => 'Supplier',
    'supplier_reference' => 'Supplier Reference',
    'current_price' => 'Purchase Price',
    'stock' => 'Stock',
    'current_stock' => 'Current Stock',
    'stock_status' => 'Stock Status',
    'materials' => 'Materials',
    'recipients' => 'Alert Recipients',

    // Stock statuses
    'stock_ok' => 'OK',
    'stock_warning' => 'Warning',
    'stock_critical' => 'Critical',
    'stock_empty' => 'Empty',

    // Alerts
    'warning_enabled' => 'Warning Alert Enabled',
    'warning_minimum' => 'Warning Threshold',
    'critical_enabled' => 'Critical Alert Enabled',
    'critical_minimum' => 'Critical Threshold',

    // Movements
    'movement' => 'Movement',
    'movements' => 'Movements',
    'movement_type' => 'Type',
    'movement_entry' => 'Entry',
    'movement_exit' => 'Exit',
    'movement_quantity' => 'Quantity',
    'movement_date' => 'Date',
    'movement_notes' => 'Notes',
    'entry_total' => 'Total Entries',
    'exit_total' => 'Total Exits',
    'entry_count' => 'Entry Count',
    'exit_count' => 'Exit Count',
    'insufficient_stock' => 'Insufficient stock for :item. Current stock: :current.',

    // Invoice
    'invoice_number' => 'Invoice Number',
    'invoice_date' => 'Invoice Date',
    'unit_price' => 'Unit Price',
    'total_price' => 'Total Price',

    // Actions
    'create' => 'Create Item',
    'edit' => 'Edit Item',
    'delete' => 'Delete Item',
    'view' => 'View Item',
    'add_movement' => 'Add Movement',
    'add_entry' => 'Add Entry',
    'add_exit' => 'Add Exit',
    'generate_qr' => 'Generate QR Code',
    'download_qr' => 'Download QR Code',
    'print_qr' => 'Print QR Code',

    // Charts
    'monthly_movements' => 'Monthly Movements',
    'price_evolution' => 'Price Evolution',
    'entries' => 'Entries',
    'exits' => 'Exits',
    'price' => 'Price',

    // Messages
    'created' => 'Item created successfully.',
    'updated' => 'Item updated successfully.',
    'deleted' => 'Item deleted successfully.',
    'cannot_delete' => 'Cannot delete item with movements. Delete all movements first.',
    'movement_created' => 'Movement created successfully.',
    'stock_alert_critical' => 'Critical stock alert for :item',
    'stock_alert_warning' => 'Warning stock alert for :item',
    'stock_below_critical' => 'Stock is below critical threshold (:current/:minimum)',
    'stock_below_warning' => 'Stock is below warning threshold (:current/:minimum)',
    'initial_price' => 'Initial price set for the item.',
    'price_updated' => 'Item price updated.',
    'supplier_changed' => 'Item supplier changed.',

    // Validation
    'reference_unique' => 'This reference already exists for this site.',
    'quantity_exceeds_stock' => 'Quantity exceeds available stock.',
];
