<?php

declare(strict_types=1);

namespace XetaSuite\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use XetaSuite\Models\Presenters\ItemPresenter;
use XetaSuite\Observers\ItemObserver;

#[ObservedBy([ItemObserver::class])]
class Item extends Model
{
    use ItemPresenter;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'site_id',
        'created_by_id',
        'created_by_name',
        'supplier_id',
        'supplier_name',
        'supplier_reference',
        'edited_user_id',
        'name',
        'description',
        'reference',
        'purchase_price',
        'currency',
        'number_warning_enabled',
        'number_warning_minimum',
        'number_critical_enabled',
        'number_critical_minimum',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array
     */
    protected $casts = [
        'purchase_price' => 'decimal:2',
        'number_warning_enabled' => 'boolean',
        'number_warning_minimum' => 'integer',
        'number_critical_enabled' => 'boolean',
        'number_critical_minimum' => 'integer'
    ];

    /**
     * Get the site that owns the item.
     *
     * @return BelongsTo
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get the supplier for the item.
     *
     * @return BelongsTo
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the materials for the item.
     *
     * @return BelongsToMany
     */
    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(Material::class)
            ->using(MaterialItem::class)
            ->withTimestamps();
    }

    /**
     * Get the movement history for the item.
     */
    public function movements(): HasMany
    {
        return $this->hasMany(ItemMovement::class)
            ->orderBy('movement_date', 'desc');
    }

    /**
     * Get the price history for the item.
     */
    public function prices(): HasMany
    {
        return $this->hasMany(ItemPrice::class)
            ->orderBy('effective_date', 'desc');
    }

    /**
     * Get the recipients that will get the alert for the item.
     *
     * @return BelongsToMany
     */
    public function recipients(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'item_user')
            ->withTimestamps();
    }

    /**
     * Get the user that created the item.
     *
     * @return BelongsTo
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Get the user that edited the part.
     *
     * @return HasOne
     */
    public function editedBy(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'edited_user_id');
    }

    /**
     * Get the current price for the item, optionally filtered by supplier.
     *
     * @param int|null $supplierId
     *
     * @return ItemPrice|null
     */
    public function getCurrentPrice(?int $supplierId = null): ?ItemPrice
    {
        if ($supplierId === null) {
            return $this->current_price;
        }

        // Sinon, query avec filtre
        return $this->prices()
            ->where('effective_date', '<=', now())
            ->where('supplier_id', $supplierId)
            ->first();
    }
}
