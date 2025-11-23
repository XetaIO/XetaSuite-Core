<?php

declare(strict_types=1);

namespace XetaSuite\Enums\Maintenances;

enum MaintenanceRealization: string
{
    case INTERNAL = 'internal';
    case EXTERNAL = 'external';
    case BOTH = 'both';

    public function label(): string
    {
        return match ($this) {
            self::INTERNAL => 'Internal',
            self::EXTERNAL => 'External',
            self::BOTH => 'Both'
        };
    }
}
