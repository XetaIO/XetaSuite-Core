<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use XetaSuite\Models\CalendarEvent;
use XetaSuite\Models\EventCategory;
use XetaSuite\Models\User;

class CalendarEventsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $eventCategories = EventCategory::all();

        $user = User::firstWhere('email', "admin@xetasuite.demo");

        foreach ($eventCategories as $eventCategory) {
            for ($i = 0; $i <= 2; $i++) {
                $stated_at = fake()->dateTimeBetween('-6 months', 'now');

                CalendarEvent::factory()
                    ->withCategory($eventCategory)
                    ->createdBy($user)
                    ->withColor('#' . dechex(rand(0x000000, 0xFFFFFF)))
                    ->startsAt($stated_at)
                    ->endsAt((clone $stated_at)->modify('+' . fake()->numberBetween(1, 4) . ' hours'))
                    ->create();
            }
        }
    }
}
