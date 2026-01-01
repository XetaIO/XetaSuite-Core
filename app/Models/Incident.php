<?php

declare(strict_types=1);

namespace XetaSuite\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Xetaio\Counts\Concerns\HasCounts;
use XetaSuite\Enums\Incidents\IncidentSeverity;
use XetaSuite\Enums\Incidents\IncidentStatus;
use XetaSuite\Models\Concerns\SiteScoped;

class Incident extends Model
{
    use HasCounts;
    use HasFactory;
    use SiteScoped;

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
        'severity',
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
        'resolved_at' => 'datetime',
    ];

    /**
     * The relations to be counted.
     */
    protected static array $countsConfig = [
        'maintenance' => 'incident_count',
        'material' => 'incident_count',
        'reporter' => 'incident_count',
    ];

    /**
     * Get the site that owns the incident.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get the material that owns the incident.
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * Get the maintenance that owns the incident.
     */
    public function maintenance(): BelongsTo
    {
        return $this->belongsTo(Maintenance::class);
    }

    /**
     * Get the user that reported the incident.
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_id');
    }

    /**
     * Get the user that edited the incident.
     */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by_id');
    }
}
