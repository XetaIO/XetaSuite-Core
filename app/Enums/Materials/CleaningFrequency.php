<?php

declare(strict_types=1);

namespace XetaSuite\Enums\Materials;

use Carbon\CarbonImmutable;

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

    public function nextCleaningDate(CarbonImmutable $lastCleaning, int $frequencyRepeatedly): CarbonImmutable
    {
        return $lastCleaning->addDays($this->frequencyInDays() * $frequencyRepeatedly);
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
