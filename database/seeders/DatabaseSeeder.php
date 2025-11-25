<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SiteSeeder::class,
            RolesAndPermissionsSeeder::class,
            UsersSeeder::class,
            UserRolesSeeder::class,

            ZonesSeeder::class,
            MaterialsSeeder::class,
            ItemsSeeder::class,
            ItemMovementsSeeder::class,
            MaintenancesSeeder::class,
            IncidentsSeeder::class,
        ]);
    }
}
