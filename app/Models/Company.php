<?php

declare(strict_types=1);

namespace XetaSuite\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use XetaSuite\Enums\Companies\CompanyType;

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
        'description',
        'types',
        'email',
        'phone',
        'address',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'types' => AsCollection::class,
        ];
    }

    /**
     * Scope to filter companies that are item providers.
     */
    public function scopeItemProviders(Builder $query): Builder
    {
        return $query->whereJsonContains('types', CompanyType::ITEM_PROVIDER->value);
    }

    /**
     * Scope to filter companies that are maintenance providers.
     */
    public function scopeMaintenanceProviders(Builder $query): Builder
    {
        return $query->whereJsonContains('types', CompanyType::MAINTENANCE_PROVIDER->value);
    }

    /**
     * Check if the company is an item provider.
     */
    public function isItemProvider(): bool
    {
        return $this->types->contains(CompanyType::ITEM_PROVIDER->value);
    }

    /**
     * Check if the company is a maintenance provider.
     */
    public function isMaintenanceProvider(): bool
    {
        return $this->types->contains(CompanyType::MAINTENANCE_PROVIDER->value);
    }

    /**
     * Get the types as CompanyType enum instances.
     *
     * @return Collection<int, CompanyType>
     */
    public function getTypesAsEnums(): Collection
    {
        return $this->types->map(fn (string $type) => CompanyType::from($type));
    }

    /**
     * Get all items from this company (when item provider).
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    /**
     * The maintenances that belong to the company.
     */
    public function maintenances(): BelongsToMany
    {
        return $this->belongsToMany(Maintenance::class)
            ->using(CompanyMaintenance::class)
            ->withTimestamps();
    }

    /**
     * Get the creator of the company.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
