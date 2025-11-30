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
     * Get the current price for the item.
     */
    /**
     * Get the current price (without supplier filter).
     */
    protected function currentPrice(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->prices()
                ->where('effective_date', '<=', now())
                ->first()
        );
    }

    /**
     * Get the current price value as float.
     */
    protected function currentPriceValue(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->current_price?->price ?? $this->purchase_price ?? 0.00
        );
    }

    /**
     * Get the stock value based on current price.
     */
    protected function stockValue(): Attribute
    {
        return Attribute::make(
            get: fn () => round($this->current_stock * $this->current_price_value, 2)
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
                'critical' => 'red',
                'warning' => 'orange',
                'empty' => 'gray',
                'ok' => 'green',
                default => 'gray',
            }
        );
    }

    /**
     * Get the stock status label.
     */
    protected function stockStatusLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->stock_status) {
                'critical' => 'Stock critical',
                'warning' => 'Stock low',
                'empty' => 'Out of stock',
                'ok' => 'In stock',
                default => 'Inconnu',
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
                && $this->current_stock <= $this->number_warning_minimum
        );
    }

    /**
     * Check if stock is critical.
     */
    protected function isCriticalStock(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->number_critical_enabled
                && $this->current_stock <= $this->number_critical_minimum
        );
    }

    /**
     * Check if item is in stock.
     */
    protected function inStock(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->current_stock > 0
        );
    }

    /**
     * Get formatted price with currency.
     */
    protected function formattedPrice(): Attribute
    {
        return Attribute::make(
            get: fn () => number_format($this->purchase_price, 2, ',', ' ').' €'
        );
    }

    /**
     * Get formatted stock value with currency.
     */
    protected function formattedStockValue(): Attribute
    {
        return Attribute::make(
            get: fn () => number_format($this->stock_value, 2, ',', ' ').' €'
        );
    }

    /**
     * Get the quantity to reach warning minimum.
     */
    protected function quantityToWarningLevel(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->number_warning_enabled) {
                    return 0;
                }

                $diff = $this->number_warning_minimum - $this->current_stock;

                return max(0, $diff);
            }
        );
    }

    /**
     * Get average movements per entry.
     */
    protected function averageEntryQuantity(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->item_entry_count == 0) {
                    return 0;
                }

                return round($this->item_entry_total / $this->item_entry_count, 2);
            }
        );
    }

    /**
     * Get average movements per exit.
     */
    protected function averageExitQuantity(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->item_exit_count == 0) {
                    return 0;
                }

                return round($this->item_exit_total / $this->item_exit_count, 2);
            }
        );
    }

    /**
     * Check if item needs restock.
     */
    protected function needsRestock(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->is_low_stock || $this->is_critical_stock
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

    /**
     * Get supplier display name (current or archived).
     */
    protected function supplierDisplayName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->load('supplier')->supplier?->name ?? $this->supplier_name ?? 'No supplier'
        );
    }
}
