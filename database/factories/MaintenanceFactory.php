<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use XetaSuite\Models\Company;
use XetaSuite\Models\Maintenance;
use XetaSuite\Models\Site;
use XetaSuite\Models\Material;
use XetaSuite\Models\User;
use XetaSuite\Enums\Maintenances\MaintenanceRealization;
use XetaSuite\Enums\Maintenances\MaintenanceStatus;
use XetaSuite\Enums\Maintenances\MaintenanceType;

class MaintenanceFactory extends Factory
{
    protected $model = Maintenance::class;

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Maintenance $maintenance) {
            $maintenance->material()->increment('maintenance_count');
        });
    }

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

            'created_by_id' => User::factory()->create()->id,
            'created_by_name' => null,
            'edited_by_id' => null,

            'description' => $this->faker->paragraph(),
            'reason' => $this->faker->sentence(),

            'type' => $this->faker->randomElement(MaintenanceType::cases())->value,
            'realization' => $this->faker->randomElement(MaintenanceRealization::cases())->value,
            'status' => $this->faker->randomElement(MaintenanceStatus::cases())->value,

            'started_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'resolved_at'=> $this->faker->optional()->dateTimeBetween('-1 year', 'now'),

            'incident_count' => $this->faker->numberBetween(0, 5),
            'company_count'  => $this->faker->numberBetween(0, 3)
        ];
    }

    /**
     * Assign the material to the specified site.
     *
     * Example:
     *   $site = Site::factory()->create();
     *   Maintenance::factory()->forSite($site)->create();
     *
     * @param Site|int $site
     *
     * @return MaintenanceFactory
     */
    public function forSite(Site|int $site): static
    {
        $siteId = $site instanceof Site ? $site->id : $site;

        return $this->state(fn () => [
            'site_id' => $siteId,
        ]);
    }

    /**
     * Assign a material or reset the fields.
     *
     * @param Material|int|null $material
     *
     * @return MaintenanceFactory
     */
    public function withMaterial(Material|int|null $material = null): static
    {
        if (is_null($material)) {
            return $this->state(fn () => [
                'material_id'   => null,
                'material_name' => null,
            ]);
        }

        $materialId   = $material instanceof Material ? $material->id : $material;
        $materialName = $material instanceof Material ? $material->name : null;

        return $this->state(fn () => [
            'material_id'   => $materialId,
            'material_name' => $materialName,
        ]);
    }

    /**
     * Defines the creator.
     *
     * @param User|int $user
     *
     * @return MaintenanceFactory
     */
    public function createdBy(User|int $user): static
    {
        return $this->state(fn () => [
            'created_by_id'   => $user instanceof User ? $user->id : $user
        ]);
    }

    /**
     * Defines the editor.
     *
     * @param User|int $user
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
     * @param MaintenanceStatus|string $status
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
     * @param MaintenanceType|string $type
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
     * @param MaintenanceRealization|string $realization
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
     * @param array $userIds
     *
     * @return MaintenanceFactory
     */
    public function withOperators(array $userIds): static
    {
        return $this->hasAttached(User::class, $userIds, 'operators');
    }

    /**
     * Adds companies to the maintenance.
     *
     * @param array $companyIds
     *
     * @return MaintenanceFactory
     */
    public function withCompanies(array $companyIds): static
    {
        return $this->hasAttached(Company::class, $companyIds, 'companies');
    }
}
