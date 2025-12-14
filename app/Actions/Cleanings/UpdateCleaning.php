<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Cleanings;

use XetaSuite\Models\Cleaning;
use XetaSuite\Models\Material;
use XetaSuite\Models\User;

class UpdateCleaning
{
    /**
     * Update an existing cleaning.
     *
     * @param  Cleaning  $cleaning  The cleaning to update.
     * @param  User  $user  The user updating the cleaning.
     * @param  array  $data  The data to update.
     */
    public function handle(Cleaning $cleaning, User $user, array $data): Cleaning
    {
        $updateData = [
            'edited_by_id' => $user->id,
        ];

        // Update material if provided
        if (isset($data['material_id']) && $data['material_id'] !== $cleaning->material_id) {
            $material = Material::findOrFail($data['material_id']);
            $updateData['material_id'] = $material->id;
        }

        if (isset($data['description'])) {
            $updateData['description'] = $data['description'];
        }

        if (isset($data['type'])) {
            $updateData['type'] = $data['type'];
        }

        $cleaning->update($updateData);

        return $cleaning->fresh(['material', 'creator', 'editor']);
    }
}
