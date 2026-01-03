<?php

declare(strict_types=1);

namespace XetaSuite\Enums\Incidents;

enum IncidentSeverity: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::LOW => __('incidents.severity_low'),
            self::MEDIUM => __('incidents.severity_medium'),
            self::HIGH => __('incidents.severity_high'),
            self::CRITICAL => __('incidents.severity_critical'),
        };
    }
}
