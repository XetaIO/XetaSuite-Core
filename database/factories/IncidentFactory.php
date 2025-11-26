<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use XetaSuite\Models\Incident;
use XetaSuite\Models\Maintenance;
use XetaSuite\Models\Material;
use XetaSuite\Models\Site;
use XetaSuite\Models\User;
use XetaSuite\Enums\Incidents\IncidentSeverity;
use XetaSuite\Enums\Incidents\IncidentStatus;

class IncidentFactory extends Factory
{
    protected $model = Incident::class;

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Incident $incident) {
            $incident->maintenance()->increment('incident_count');
            $incident->maintenance->material()->increment('incident_count');
            $incident->reporter()->increment('incident_count');
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        $status = $this->faker->randomElement(IncidentStatus::cases());

        return [
            'site_id' => null,
            'material_id' => null,
            'material_name' => null,

            'reported_by_id' => null,
            'reported_by_name' => null,

            'edited_by_id' => null,

            'description' => $this->faker->paragraph(),
            'started_at' => $status === IncidentStatus::OPEN
                || $status === IncidentStatus::IN_PROGRESS
                || $status === IncidentStatus::RESOLVED
                ? $this->faker->dateTimeBetween('-3 months', '-1 month')
                : null,
            'resolved_at' => $status === IncidentStatus::RESOLVED
                ? $this->faker->dateTimeBetween('-1 month', 'now')
                : null,

            'status' => $status->value,
            'severity' => $this->faker->randomElement(IncidentSeverity::cases())->value,

            'maintenance_id' => null
        ];
    }

    /**
     * Forces the use of an existing site (or site ID).
     *
     * @param Site|int $site
     *
     * @return IncidentFactory
     */
    public function forSite(Site|int $site): static
    {
        return $this->state(fn () => [
            'site_id' => $site instanceof Site ? $site->id : $site
        ]);
    }

    /**
     * Indicate that the incident is for a specific material.
     *
     * @param Material|int $material
     *
     * @return IncidentFactory
     */
    public function forMaterial(Material|int $material): static
    {
        return $this->state(fn () => [
            'material_id' => $material instanceof Material ? $material->id : $material
        ]);
    }

    /**
     * Indicate that the incident is associated with a specific maintenance.
     *
     * @param Maintenance|int $maintenance
     *
     * @return IncidentFactory
     */
    public function withMaintenance(Maintenance|int $maintenance): static
    {
        return $this->state(fn () => [
            'maintenance_id' => $maintenance instanceof Maintenance ? $maintenance->id : $maintenance
        ]);
    }

    /**
     * Force the status of an incident (open, in_progress, resolved, closed).
     *
     * @param IncidentStatus|string $status
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
                    ? $this->faker->dateTimeBetween($attributes['started_at'], 'now')
                    : null
            ];
        });
    }

    /**
     * Force the severity of an incident (low, medium, high, critical).
     *
     * @param IncidentSeverity|string $severity
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
     * @param User|int $user
     *
     * @return IncidentFactory
     */
    public function reportedBy(User|int $user): static
    {
        return $this->state(fn () => [
            'reported_by_id'   => $user instanceof User ? $user->id : $user
        ]);
    }
}
