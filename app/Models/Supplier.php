<?php

declare(strict_types=1);

namespace XetaSuite\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use XetaSuite\Observers\SupplierObserver;

#[ObservedBy([SupplierObserver::class])]
class Supplier extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'site_id',
        'created_by_id',
        'created_by_name',
        'name',
        'description',
    ];

    /**
     * Get all the items from the supplier.
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    /**
     * Get the user that created the supplier.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
