<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use XetaSuite\Enums\Maintenances\MaintenanceRealization;
use XetaSuite\Enums\Maintenances\MaintenanceStatus;
use XetaSuite\Enums\Maintenances\MaintenanceType;
use XetaSuite\Models\Maintenance;
use XetaSuite\Models\Material;
use XetaSuite\Models\Site;
use XetaSuite\Models\User;

class MaintenanceFactory extends Factory
{
    protected $model = Maintenance::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $status = $this->faker->randomElement(MaintenanceStatus::cases());

        return [
            'site_id' => null,
            'material_id' => null,
            'material_name' => null,

            'created_by_id' => null,
            'created_by_name' => null,
            'edited_by_id' => null,

            'description' => $this->faker->paragraph(),
            'reason' => $this->faker->sentence(),

            'type' => $this->faker->randomElement(MaintenanceType::cases())->value,
            'realization' => $this->faker->randomElement(MaintenanceRealization::cases())->value,
            'status' => $status->value,

            'started_at' => $status === MaintenanceStatus::IN_PROGRESS || $status === MaintenanceStatus::COMPLETED
                ? $this->faker->dateTimeBetween('-3 months', '-1 month')
                : null,
            'resolved_at' => $status === MaintenanceStatus::COMPLETED
                ? $this->faker->dateTimeBetween('-1 month', 'now')
                : null,

            'incident_count' => 0,
        ];
    }

    /**
     * Assign the material to the specified site.
     *
     * Example:
     *   $site = Site::factory()->create();
     *   Maintenance::factory()->forSite($site)->create();
     *
     *
     * @return MaintenanceFactory
     */
    public function forSite(Site|int $site): static
    {
        return $this->state(fn () => [
            'site_id' => $site instanceof Site ? $site->id : $site,
        ]);
    }

    /**
     * Assign a material or reset the fields.
     *
     *
     * @return MaintenanceFactory
     */
    public function forMaterial(Material|int|null $material = null): static
    {
        if (is_null($material)) {
            return $this->state(fn () => [
                'material_id' => null,
            ]);
        }

        return $this->state(fn () => [
            'material_id' => $material instanceof Material ? $material->id : $material
        ]);
    }

    /**
     * Defines the creator.
     *
     *
     * @return MaintenanceFactory
     */
    public function createdBy(User|int $user): static
    {
        return $this->state(fn () => [
            'created_by_id' => $user instanceof User ? $user->id : $user,
        ]);
    }

    /**
     * Defines the editor.
     *
     *
     * @return MaintenanceFactory
     */
    public function editedBy(User|int $user): static
    {
        return $this->state(fn () => [
            'edited_by_id' => $user instanceof User ? $user->id : $user,
        ]);
    }

    /**
     * Force the status of a maintenance (planned, in_progress, completed, canceled).
     *
     *
     * @return MaintenanceFactory
     */
    public function withStatus(MaintenanceStatus|string $status): static
    {
        $value = $status instanceof MaintenanceStatus
            ? $status->value
            : (string) $status;

        return $this->state(fn () => ['status' => $value]);
    }

    /**
     * Force the type of a maintenance (corrective, preventive, inspection, improvement).
     *
     *
     * @return MaintenanceFactory
     */
    public function withType(MaintenanceType|string $type): static
    {
        $value = $type instanceof MaintenanceType
            ? $type->value
            : (string) $type;

        return $this->state(fn () => ['type' => $value]);
    }

    /**
     * Force the maintenance realization method (internal, external, both).
     *
     *
     * @return MaintenanceFactory
     */
    public function withRealization(MaintenanceRealization|string $realization): static
    {
        $value = $realization instanceof MaintenanceRealization
            ? $realization->value
            : (string) $realization;

        return $this->state(fn () => ['realization' => $value]);
    }

    /**
     * Adds operators (users) to the maintenance.
     *
     * Example:
     *   $ops = User::factory()->count(3)->create();
     *   Maintenance::factory()
     *       ->withOperators($ops->pluck('id')->toArray())
     *       ->create();
     *
     *
     * @return MaintenanceFactory
     */
    public function withOperators(array $userIds): static
    {
        return $this->hasAttached($userIds, [], 'operators');
    }

    /**
     * Adds companies to the maintenance.
     *
     *
     * @return MaintenanceFactory
     */
    public function withCompanies(array $companyIds): static
    {
        return $this->hasAttached($companyIds, [], 'companies');
    }
}
