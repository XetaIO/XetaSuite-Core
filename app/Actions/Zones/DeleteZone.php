<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Zones;

use XetaSuite\Models\Zone;
use XetaSuite\Services\ZoneService;

class DeleteZone
{
    public function __construct(
        private readonly ZoneService $zoneService
    ) {
    }

    /**
     * Delete a zone.
     *
     * @return array{success: bool, message: string}
     */
    public function handle(Zone $zone): array
    {
        if (! $this->zoneService->canDelete($zone)) {
            if ($zone->children()->exists()) {
                return [
                    'success' => false,
                    'message' => __('zones.cannot_delete_has_children'),
                ];
            }

            return [
                'success' => false,
                'message' => __('zones.cannot_delete_has_materials'),
            ];
        }

        $zone->delete();

        return [
            'success' => true,
            'message' => __('zones.deleted'),
        ];
    }
}
