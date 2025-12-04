<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use XetaSuite\Models\Cleaning;
use XetaSuite\Models\Maintenance;
use XetaSuite\Models\Site;
use XetaSuite\Models\User;

class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'username' => $this->faker->unique()->userName(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'office_phone' => $this->faker->optional()->phoneNumber(),
            'cell_phone' => $this->faker->optional()->phoneNumber(),
            'remember_token' => Str::random(10),
            'current_site_id' => null,
            'end_employment_contract' => null,
            'password_setup_at' => now(),
            'locale' => 'en',
        ];
    }

    /**
     * Indicate that the model's password should be unset.
     *
     * @return UserFactory
     */
    public function unset(): static
    {
        return $this->state(fn (array $attributes) => [
            'password_setup_at' => null,
        ]);
    }

    /**
     * Assign a role after creation (state for admin users)
     *
     * @return UserFactory
     */
    public function admin(): static
    {
        return $this->afterCreating(function (User $user) {
            if (method_exists($user, 'assignRole')) {
                $user->assignRolesToSites('admin', Site::all()->pluck('id')->toArray());
            }
        });
    }

    /**
     * Forces the use of an existing site (or site ID).
     *
     *
     * @return UserFactory
     */
    public function withSite(Site|int $site): static
    {
        $siteId = $site instanceof Site ? $site->id : $site;

        return $this->state(fn () => [
            'current_site_id' => $siteId,
        ])->afterCreating(function (User $user) use ($siteId) {
            $user->sites()->syncWithoutDetaching([$siteId]);
        });
    }

    /**
     * User soft deleted
     *
     * @return UserFactory
     */
    public function deleted(): static
    {
        return $this->state(function () {
            return [
                'deleted_at' => now(),
            ];
        });
    }

    /**
     * State : User with cleanings
     *
     *
     * @return UserFactory
     */
    public function withCleanings(int $count = 5): static
    {
        return $this->afterCreating(function (User $user) use ($count) {
            $user->cleanings()->createMany(
                Cleaning::factory()->count($count)->make()->toArray()
            );
        });
    }

    /**
     * State : User with incidents created by him
     *
     *
     * @return UserFactory
     */
    public function withIncidents(int $count = 3): static
    {
        return $this->afterCreating(function (User $user) use ($count) {
            $user->incidents()->createMany(
                Incident::factory()->count($count)->make()->toArray()
            );
        });
    }

    /**
     * State : User with maintenances created by him
     *
     *
     * @return UserFactory
     */
    public function withMaintenances(int $count = 3): static
    {
        return $this->afterCreating(function (User $user) use ($count) {
            $user->maintenances()->createMany(
                Maintenance::factory()->count($count)->make()->toArray()
            );
        });
    }

    /**
     * State : User acting as operator (many-to-many)
     *
     *
     * @return UserFactory
     */
    public function withMaintenanceOperators(int $count = 3): static
    {
        return $this->afterCreating(function (User $user) use ($count) {
            $maintenances = Maintenance::factory()->count($count)->create();
            $user->maintenancesOperators()->attach($maintenances->pluck('id')->toArray());
        });
    }

    /**
     * State : User assigned to multiple sites (BelongsToMany)
     *
     *
     * @return UserFactory
     */
    public function withSites(int $count = 2): static
    {
        return $this->afterCreating(function (User $user) use ($count) {
            $sites = Site::factory()->count($count)->create();
            $user->sites()->attach($sites->pluck('id')->toArray());
        });
    }
}
