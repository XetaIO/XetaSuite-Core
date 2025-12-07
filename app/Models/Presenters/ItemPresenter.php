<?php

declare(strict_types=1);

namespace XetaSuite\Models\Presenters;

use Illuminate\Database\Eloquent\Casts\Attribute;

trait ItemPresenter
{
    /**
     * Get the current stock quantity.
     */
    protected function currentStock(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->item_entry_total - $this->item_exit_total
        );
    }

    /**
     * Get the current price value as float.
     */
    protected function currentPriceValue(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->current_price?->price ?? $this->current_price ?? 0.00
        );
    }

    /**
     * Get the stock status.
     */
    protected function stockStatus(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->is_critical_stock) {
                    return 'critical';
                }

                if ($this->is_low_stock) {
                    return 'warning';
                }

                return $this->current_stock > 0 ? 'ok' : 'empty';
            }
        );
    }

    /**
     * Get the stock status color for UI.
     */
    protected function stockStatusColor(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->stock_status) {
                'critical' => 'error',
                'warning' => 'warning',
                'empty' => 'info',
                'ok' => 'success',
                default => 'dark',
            }
        );
    }

    /**
     * Check if stock is low.
     */
    protected function isLowStock(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->number_warning_enabled
                && $this->current_stock <= $this->number_warning_minimum && $this->current_stock >= $this->number_critical_minimum
        );
    }

    /**
     * Check if stock is critical.
     */
    protected function isCriticalStock(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->number_critical_enabled
                && $this->current_stock <= $this->number_critical_minimum && $this->current_stock > 0
        );
    }

    /**
     * Get full item name with reference.
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->reference) {
                    return "{$this->name} (Ref: {$this->reference})";
                }

                return $this->name;
            }
        );
    }

    /**
     * Check if item has supplier.
     */
    protected function hasSupplier(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->supplier_id !== null || $this->supplier_name !== null
        );
    }
}
