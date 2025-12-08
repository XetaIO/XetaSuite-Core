<?php

declare(strict_types=1);

namespace XetaSuite\Enums\Incidents;

enum IncidentStatus: string
{
    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => __('incidents.status_open'),
            self::IN_PROGRESS => __('incidents.status_in_progress'),
            self::RESOLVED => __('incidents.status_resolved'),
            self::CLOSED => __('incidents.status_closed'),
        };
    }
}
