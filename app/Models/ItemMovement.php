<?php

declare(strict_types=1);

namespace XetaSuite\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ItemMovement extends Model
{
    protected $fillable = [
        'item_id',
        'type',
        'quantity',
        'unit_price',
        'total_price',
        'supplier_id',
        'supplier_name',
        'supplier_invoice_number',
        'invoice_date',
        'material_id',
        'material_name',
        'movable_type',
        'movable_id',
        'created_by_id',
        'created_by_name',
        'notes',
        'movement_date',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'invoice_date' => 'date',
        'movement_date' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function movable(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    // Scopes
    public function scopeEntries($query)
    {
        return $query->where('type', 'entry');
    }

    public function scopeExits($query)
    {
        return $query->where('type', 'exit');
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('movement_date', [$startDate, $endDate]);
    }
}
