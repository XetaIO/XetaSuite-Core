<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use XetaSuite\Enums\Incidents\IncidentSeverity;
use XetaSuite\Enums\Incidents\IncidentStatus;
use XetaSuite\Models\Incident;
use XetaSuite\Models\Maintenance;
use XetaSuite\Models\Material;
use XetaSuite\Models\Site;
use XetaSuite\Models\User;

class IncidentFactory extends Factory
{
    protected $model = Incident::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $status = \fake()->randomElement(IncidentStatus::cases());

        return [
            'site_id' => null,
            'material_id' => null,
            'material_name' => null,

            'reported_by_id' => null,
            'reported_by_name' => null,

            'edited_by_id' => null,

            'description' => \fake()->paragraph(),
            'started_at' => $status === IncidentStatus::OPEN
                || $status === IncidentStatus::IN_PROGRESS
                || $status === IncidentStatus::RESOLVED
                ? \fake()->dateTimeBetween('-3 months', '-1 month')
                : null,
            'resolved_at' => $status === IncidentStatus::RESOLVED
                ? \fake()->dateTimeBetween('-1 month', 'now')
                : null,

            'status' => $status->value,
            'severity' => \fake()->randomElement(IncidentSeverity::cases())->value,

            'maintenance_id' => null,
        ];
    }

    /**
     * Forces the use of an existing site (or site ID).
     *
     *
     * @return IncidentFactory
     */
    public function forSite(Site|int $site): static
    {
        return $this->state(fn () => [
            'site_id' => $site instanceof Site ? $site->id : $site,
        ]);
    }

    /**
     * Indicate that the incident is for a specific material.
     *
     *
     * @return IncidentFactory
     */
    public function forMaterial(Material|int $material): static
    {
        return $this->state(fn () => [
            'material_id' => $material instanceof Material ? $material->id : $material,
        ]);
    }

    /**
     * Indicate that the incident is associated with a specific maintenance.
     *
     *
     * @return IncidentFactory
     */
    public function withMaintenance(Maintenance|int $maintenance): static
    {
        return $this->state(fn () => [
            'maintenance_id' => $maintenance instanceof Maintenance ? $maintenance->id : $maintenance,
        ]);
    }

    /**
     * Force the status of an incident (open, in_progress, resolved, closed).
     *
     *
     * @return IncidentFactory
     */
    public function withStatus(IncidentStatus|string $status): static
    {
        $value = $status instanceof IncidentStatus
            ? $status->value
            : (string) $status;

        return $this->state(function (array $attributes) use ($value) {
            return [
                'status' => $value,
                'resolved_at' => $value === IncidentStatus::RESOLVED->value
                    ? \fake()->dateTimeBetween($attributes['started_at'], 'now')
                    : null,
            ];
        });
    }

    /**
     * Force the severity of an incident (low, medium, high, critical).
     *
     *
     * @return IncidentFactory
     */
    public function withSeverity(IncidentSeverity|string $severity): static
    {
        return $this->state(fn () => [
            'severity' => $severity instanceof IncidentSeverity ? $severity->value : (string) $severity,
        ]);
    }

    /**
     * Indicate that the incident was reported by a specific user.
     *
     *
     * @return IncidentFactory
     */
    public function reportedBy(User|int $user): static
    {
        return $this->state(fn () => [
            'reported_by_id' => $user instanceof User ? $user->id : $user,
        ]);
    }
}
