<?php

declare(strict_types=1);

namespace XetaSuite\Enums\Materials;

use Illuminate\Support\Carbon;

enum CleaningFrequency: string
{
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';

    public function label(): string
    {
        return match ($this) {
            self::DAILY => 'Daily',
            self::WEEKLY => 'Weekly',
            self::MONTHLY => 'Monthly'
        };
    }

    public function nextCleaningDate(Carbon $lastCleaning): Carbon
    {
        return match ($this) {
            self::DAILY => $lastCleaning->addDay(),
            self::WEEKLY => $lastCleaning->addWeek(),
            self::MONTHLY => $lastCleaning->addMonth(),
        };
    }

    public function frequencyInDays(): int
    {
        return match ($this) {
            self::DAILY => 1,
            self::WEEKLY => 7,
            self::MONTHLY => 30,
        };
    }
}
