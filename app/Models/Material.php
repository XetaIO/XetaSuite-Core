<?php

declare(strict_types=1);

namespace XetaSuite\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Xetaio\Counts\Concerns\HasCounts;
use XetaSuite\Enums\Materials\CleaningFrequency;
use XetaSuite\Observers\MaterialObserver;

#[ObservedBy([MaterialObserver::class])]
class Material extends Model
{
    use HasCounts;
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
        'zone_id',
        'name',
        'description',
        'cleaning_alert',
        'cleaning_alert_email',
        'cleaning_alert_frequency_repeatedly',
        'cleaning_alert_frequency_type',
        'last_cleaning_at',
        'last_cleaning_alert_send_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'cleaning_alert_frequency_type' => CleaningFrequency::class,
        'cleaning_alert' => 'boolean',
        'cleaning_alert_email' => 'boolean',
        'last_cleaning_at' => 'datetime',
        'last_cleaning_alert_send_at' => 'datetime',
    ];

    /**
     * The relations to be counted.
     */
    protected static array $countsConfig = [
        'zone' => 'material_count',
    ];

    /**
     * Get the site that owns the material.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get the zone that owns the material.
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    /**
     * Get the user that created the material.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Get the incidents for the material.
     */
    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    /**
     * Get the maintenances for the material.
     */
    public function maintenances(): HasMany
    {
        return $this->hasMany(Maintenance::class);
    }

    /**
     * The items that belong to the material.
     */
    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class)
            ->using(ItemMaterial::class)
            ->withTimestamps();
    }

    /**
     * Get the cleanings for the material.
     */
    public function cleanings(): HasMany
    {
        return $this->hasMany(Cleaning::class);
    }

    /**
     * The users that receive notifications for the material.
     */
    public function recipients(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withTimestamps();
    }
}
