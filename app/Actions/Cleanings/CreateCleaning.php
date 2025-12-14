<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Cleanings;

use Illuminate\Support\Facades\DB;
use XetaSuite\Enums\Cleanings\CleaningType;
use XetaSuite\Models\Cleaning;
use XetaSuite\Models\Material;
use XetaSuite\Models\User;

class CreateCleaning
{
    /**
     * Create a new cleaning.
     *
     * @param  User  $user  The user creating the cleaning.
     * @param  array  $data  The data for the new cleaning.
     */
    public function handle(User $user, array $data): Cleaning
    {
        return DB::transaction(function () use ($user, $data) {
            $siteId = $user->current_site_id;
            $material = Material::findOrFail($data['material_id']);

            $cleaning = Cleaning::create([
                'site_id' => $siteId,
                'material_id' => $material->id,
                'created_by_id' => $user->id,
                'description' => $data['description'],
                'type' => $data['type'] ?? CleaningType::CASUAL,
            ]);

            return $cleaning->load(['material', 'creator']);
        });
    }
}
