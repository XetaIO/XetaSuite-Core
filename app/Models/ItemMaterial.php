<?php

declare(strict_types=1);

namespace XetaSuite\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Xetaio\Counts\Concerns\HasCounts;

class ItemMaterial extends Pivot
{
    use HasCounts;

    /**
     * The relations to be counted.
     */
    protected static array $countsConfig = [
        'item' => 'material_count',
        'material' => 'item_count',
    ];

    /**
     * Get the item that owns the ItemMaterial.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get the material that owns the ItemMaterial
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
