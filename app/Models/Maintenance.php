<?php

declare(strict_types=1);

namespace XetaSuite\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use XetaSuite\Enums\Maintenances\MaintenanceRealization;
use XetaSuite\Enums\Maintenances\MaintenanceStatus;
use XetaSuite\Enums\Maintenances\MaintenanceType;
use XetaSuite\Observers\MaintenanceObserver;

#[ObservedBy([MaintenanceObserver::class])]
class Maintenance extends Model
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
        'edited_user_id',
        'description',
        'reason',
        'type',
        'realization',
        'status',
        'started_at',
        'resolved_at',
        'incident_count'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'type' => MaintenanceType::class,
        'realization' => MaintenanceRealization::class,
        'status' => MaintenanceStatus::class,
        'started_at' => 'datetime',
        'resolved_at' => 'datetime'
    ];


    /**
     * Get the site that owns the maintenance.
     *
     * @return BelongsTo
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get the material that owns the maintenance.
     *
     * @return BelongsTo
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * Get the incidents for the maintenance.
     *
     * @return HasMany
     */
    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    /**
     * Get the user that created the maintenance.
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id', 'created_by_id');
    }

    /**
     * Get the user that edited the maintenance.
     *
     * @return BelongsTo
     */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id', 'edited_by_id');
    }

    /**
     * Get the operators involved in the maintenance.
     *
     * @return BelongsToMany
     */
    public function operators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'maintenance_user')
            ->withTimestamps();
    }

    /**
     * Get the companies involved in the maintenance.
     *
     * @return BelongsToMany
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)
            ->using(CompanyMaintenance::class)
            ->withTimestamps();
    }

    /**
     * Maintenance-related item movements (outgoing items only).
     *
     * @return MorphMany
     */
    public function itemMovements(): MorphMany
    {
        return $this->morphMany(ItemMovement::class, 'movable')
            ->where('type', 'exit');
    }
}
