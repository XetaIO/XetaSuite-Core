<?php

declare(strict_types=1);

namespace XetaSuite\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use XetaSuite\Enums\Cleanings\CleaningType;
use XetaSuite\Observers\CleaningObserver;

#[ObservedBy([CleaningObserver::class])]
class Cleaning extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'site_id',
        'material_id',
        'material_name',
        'created_by_id',
        'created_by_name',
        'edited_by_id',
        'description',
        'type'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'type' => CleaningType::class,
    ];

    /**
     * Get the site where belongs the cleaning.
     *
     * @return BelongsTo
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get the material where belongs the cleaning.
     *
     * @return BelongsTo
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * Get the user that created the cleaning.
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id', 'id');
    }

    /**
     * Get the user that edited the cleaning.
     *
     * @return HasOne
     */
    public function editor(): HasOne
    {
        return $this->hasOne(User::class, 'edited_by_id', 'id');
    }
}
