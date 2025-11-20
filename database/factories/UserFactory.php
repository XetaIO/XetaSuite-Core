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
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'office_phone' => $this->faker->optional()->phoneNumber(),
            'cell_phone' => $this->faker->optional()->phoneNumber(),
            'remember_token' => Str::random(10),
            'current_site_id' => null,
            'end_employment_contract' => null,
            'password_setup_at' => now(),
        ];
    }

    /**
     * Indicate that the model's password should be unset.
     */
    public function unset(): static
    {
        return $this->state(fn (array $attributes) => [
            'password_setup_at' => null,
        ]);
    }

    /**
     * Assign a role after creation (state for admin users)
     */
    public function admin(): static
    {
        return $this->afterCreating(function (User $user) {
            if (method_exists($user, 'assignRole')) {
                $user->assignRole('admin');
            }
        });
    }

    /**
     * State : User with a current site
     */
    public function withCurrentSite(): static
    {
        return $this->state(function () {
            return [
                'current_site_id' => Site::factory(),
            ];
        });
    }

    /**
     * State : User soft deleted
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
     */
    public function withSites(int $count = 2): static
    {
        return $this->afterCreating(function (User $user) use ($count) {
            $sites = Site::factory()->count($count)->create();
            $user->sites()->attach($sites->pluck('id')->toArray());
        });
    }
}
