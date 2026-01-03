<?php

declare(strict_types=1);

namespace XetaSuite\Enums\Companies;

enum CompanyType: string
{
    case ITEM_PROVIDER = 'item_provider';
    case MAINTENANCE_PROVIDER = 'maintenance_provider';

    public function label(): string
    {
        return match ($this) {
            self::ITEM_PROVIDER => __('companies.type_item_provider'),
            self::MAINTENANCE_PROVIDER => __('companies.type_maintenance_provider'),
        };
    }

    /**
     * Get all available types as an array of values.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
