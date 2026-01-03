<?php

declare(strict_types=1);

namespace XetaSuite\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Xetaio\Counts\Concerns\HasCounts;

class CompanyMaintenance extends Pivot
{
    use HasCounts;

    /**
     * The relations to be counted.
     */
    protected static array $countsConfig = [
        'company' => 'maintenance_count',
    ];

    /**
     * Get the company that owns the CompanyMaintenance
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the maintenance that owns the CompanyMaintenance
     */
    public function maintenance(): BelongsTo
    {
        return $this->belongsTo(Maintenance::class);
    }
}
