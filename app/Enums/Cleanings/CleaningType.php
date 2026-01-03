<?php

declare(strict_types=1);

namespace XetaSuite\Enums\Cleanings;

enum CleaningType: string
{
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case BIMONTHLY = 'bimonthly';
    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';
    case BIANNUAL = 'biannual';
    case ANNUEL = 'annual';
    case CASUAL = 'casual';

    public function label(): string
    {
        return match ($this) {
            self::DAILY => 'Daily',
            self::WEEKLY => 'Weekly',
            self::BIMONTHLY => 'Bi-monthly',
            self::MONTHLY => 'Monthly',
            self::QUARTERLY => 'Quarterly',
            self::BIANNUAL => 'Bi-annual',
            self::ANNUEL => 'Annual',
            self::CASUAL => 'Casual',
        };
    }
}
