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
            self::PLANNED => __('maintenances.status_planned'),
            self::IN_PROGRESS => __('maintenances.status_in_progress'),
            self::COMPLETED => __('maintenances.status_completed'),
            self::CANCELLED => __('maintenances.status_canceled'),
        };
    }
}
