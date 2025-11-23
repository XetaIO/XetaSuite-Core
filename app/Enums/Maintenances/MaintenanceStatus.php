<?php

declare(strict_types=1);

namespace XetaSuite\Enums\Maintenances;

enum MaintenanceStatus: string
{
    case PLANNED = 'planned';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::PLANNED => 'Planned',
            self::IN_PROGRESS => 'In progress',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Canceled'
        };
    }
}
