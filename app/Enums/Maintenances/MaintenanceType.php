<?php

declare(strict_types=1);

namespace XetaSuite\Enums\Maintenances;

enum MaintenanceType: string
{
    case CORRECTIVE = 'corrective';
    case PREVENTIVE = 'preventive';
    case INSPECTION = 'inspection';
    case IMPROVEMENT = 'improvement';

    public function label(): string
    {
        return match ($this) {
            self::CORRECTIVE => __('maintenances.type_corrective'),
            self::PREVENTIVE => __('maintenances.type_preventive'),
            self::INSPECTION => __('maintenances.type_inspection'),
            self::IMPROVEMENT => __('maintenances.type_improvement'),
        };
    }
}
