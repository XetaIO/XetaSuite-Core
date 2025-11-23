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
            self::CORRECTIVE => 'Corrective',
            self::PREVENTIVE => 'Preventive',
            self::INSPECTION => 'Inspection',
            self::IMPROVEMENT => 'Improvement'
        };
    }
}
