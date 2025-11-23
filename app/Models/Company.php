<?php

declare(strict_types=1);

namespace XetaSuite\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use XetaSuite\Observers\CompanyObserver;

#[ObservedBy([CompanyObserver::class])]
class Company extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'created_by_id',
        'created_by_name',
        'name',
        'description'
    ];

    /**
     * The maintenances that belong to the company.
     *
     * @return BelongsToMany
     */
    public function maintenances(): BelongsToMany
    {
        return $this->belongsToMany(Maintenance::class)
            ->using(CompanyMaintenance::class)
            ->withTimestamps();
    }

    /**
     * Get the creator of the company.
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id', 'created_by_id');
    }
}
