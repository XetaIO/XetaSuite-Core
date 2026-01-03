<?php

declare(strict_types=1);

namespace XetaSuite\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Xetaio\Counts\Concerns\HasCounts;
use XetaSuite\Observers\ItemMovementObserver;

#[ObservedBy([ItemMovementObserver::class])]
class ItemMovement extends Model
{
    use HasCounts;
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'item_id',
        'type',
        'quantity',
        'unit_price',
        'total_price',
        'company_id',
        'company_name',
        'company_invoice_number',
        'invoice_date',
        'movable_type',
        'movable_id',
        'created_by_id',
        'created_by_name',
        'notes',
        'movement_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'invoice_date' => 'datetime',
        'movement_date' => 'datetime',
    ];

    /**
     * Get the counts configuration for this movement.
     *
     * @return array<string, string>
     */
    public function getCountsConfig(): array
    {
        return [
            'item' => $this->type === 'entry' ? 'item_entry_count' : 'item_exit_count',
            'creator' => $this->type === 'entry' ? 'item_entry_count' : 'item_exit_count',
        ];
    }

    /**
     * Get the item associated with the item movement.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get the company (item provider) associated with the item movement. (Entries)
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * The movable model associated with the item movement.
     */
    public function movable(): MorphTo
    {
        return $this->morphTo(); // Maintenance
    }

    /**
     * The creator of the item movement.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Get the maintenance associated with the item movement. (Exits)
     */
    public function maintenance(): BelongsTo
    {
        return $this->belongsTo(Maintenance::class, 'movable_id')
            ->where('movable_type', Maintenance::class);
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
