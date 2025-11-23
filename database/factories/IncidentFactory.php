<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use XetaSuite\Models\Incident;
use XetaSuite\Models\Material;
use XetaSuite\Models\Site;
use XetaSuite\Models\User;
use XetaSuite\Enums\Incidents\IncidentSeverity;
use XetaSuite\Enums\Incidents\IncidentStatus;

class IncidentFactory extends Factory
{
    protected $model = Incident::class;

    /**
     * Define the model's default state.
     *
     * @return array<string,mixed>
     */
    public function definition(): array
    {
        $site = Site::factory()->create();
        $material = Material::factory()->forSite($site)->create();

        return [
            'site_id' => $site->id,
            'material_id' => $material->id,
            'material_name' => null,

            'reported_by_id' => User::factory()->create()->id,
            'reported_by_name' => null,

            'edited_by_id' => $this->faker->optional(0.5)->randomElement(User::pluck('id')),

            'description' => $this->faker->paragraph(),
            'started_at' => $this->faker->optional(0.7)->dateTimeBetween('-6 months', 'now'),
            'resolved_at' => null,

            'status' => $this->faker->randomElement(IncidentStatus::cases())->value,
            'severity' => $this->faker->randomElement(IncidentSeverity::cases())->value,
        ];
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
                'resolved_at' => $value === IncidentStatus::RESOLVED->value ? $this->faker->dateTimeBetween($attributes['started_at'], 'now') : null
            ];
        });
    }

    /**
     * Force the severity of an incident (open, in_progress, resolved, closed).
     *
     * @param IncidentSeverity|string $status
     *
     * @return IncidentFactory
     */
    public function withSeverity(IncidentSeverity|string $status): static
    {
        $value = $status instanceof IncidentSeverity
            ? $status->value
            : (string) $status;

        return $this->state(fn () => [
            'severity' => $value,
        ]);
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

