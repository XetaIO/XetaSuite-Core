<?php

declare(strict_types=1);

namespace XetaSuite\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use XetaSuite\Enums\Incidents\IncidentSeverity;
use XetaSuite\Enums\Incidents\IncidentStatus;
use XetaSuite\Observers\IncidentObserver;

#[ObservedBy([IncidentObserver::class])]
class Incident extends Model
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
        'maintenance_id',
        'reported_by_id',
        'reported_by_name',
        'edited_by_id',
        'description',
        'started_at',
        'resolved_at',
        'status',
        'severity'
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array
     */
    protected $casts = [
        'status' => IncidentStatus::class,
        'severity' => IncidentSeverity::class,
        'started_at' => 'datetime',
        'resolved_at' => 'datetime'
    ];

    /**
     * Get the site that owns the incident.
     *
     * @return BelongsTo
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get the material that owns the incident.
     *
     * @return BelongsTo
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * Get the maintenance that owns the incident.
     *
     * @return BelongsTo
     */
    public function maintenance(): BelongsTo
    {
        return $this->belongsTo(Maintenance::class);
    }

    /**
     * Get the user that reported the incident.
     *
     * @return BelongsTo
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_id', 'id');
    }

    /**
     * Get the user that edited the incident.
     *
     * @return HasOne
     */
    public function editor(): HasOne
    {
        return $this->hasOne(User::class, 'edited_by_id', 'id');
    }
}
