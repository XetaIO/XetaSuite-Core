<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use XetaSuite\Models\CalendarEvent;
use XetaSuite\Models\EventCategory;
use XetaSuite\Models\Site;
use XetaSuite\Models\User;

/**
 * @extends Factory<CalendarEvent>
 */
class CalendarEventFactory extends Factory
{
    protected $model = CalendarEvent::class;

    public function definition(): array
    {
        $startAt = fake()->dateTimeBetween('-1 month', '+1 month');
        $endAt = (clone $startAt)->modify('+' . fake()->numberBetween(1, 4) . ' hours');

        return [
            'site_id' => null,
            'event_category_id' => null,
            'created_by_id' => null,
            'created_by_name' => null,
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'color' => fake()->optional()->hexColor(),
            'start_at' => $startAt,
            'end_at' => $endAt,
            'all_day' => false,
        ];
    }

    public function forSite(Site|int $site): static
    {
        return $this->state(fn () => [
            'site_id' => $site instanceof Site ? $site->id : $site,
        ]);
    }

    public function withCategory(EventCategory $category): static
    {
        return $this->state(fn () => [
            'event_category_id' => $category->id,
            'site_id' => $category->site_id,
        ]);
    }

    public function createdBy(User $user): static
    {
        return $this->state(fn () => [
            'created_by_id' => $user->id,
        ]);
    }

    public function allDay(): static
    {
        return $this->state(fn () => [
            'all_day' => true,
            'end_at' => null,
        ]);
    }

    public function today(): static
    {
        $startAt = now()->setHour(fake()->numberBetween(8, 16))->setMinute(0);
        $endAt = (clone $startAt)->addHours(fake()->numberBetween(1, 3));

        return $this->state(fn () => [
            'start_at' => $startAt,
            'end_at' => $endAt,
        ]);
    }

    public function startsAt(\DateTimeInterface $dateTime): static
    {
        return $this->state(fn () => [
            'start_at' => $dateTime,
        ]);
    }

    public function endsAt(\DateTimeInterface $dateTime): static
    {
        return $this->state(fn () => [
            'end_at' => $dateTime,
        ]);
    }

    public function withColor(string $color): static
    {
        return $this->state(fn () => [
            'color' => $color,
        ]);
    }
}
