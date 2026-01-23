<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SettingsSeeder::class,
            SiteSeeder::class,
            RolesAndPermissionsSeeder::class,
            UsersSeeder::class,
            UserRolesSeeder::class,

            ZonesSeeder::class,
            MaterialsSeeder::class,
            ItemsSeeder::class,
            ItemMovementsSeeder::class,
            ItemPricesSeeder::class,
            MaintenancesSeeder::class,
            IncidentsSeeder::class,
            CompaniesSeeder::class,
            CleaningsSeeder::class,

            EventCategoriesSeeder::class,
            CalendarEventsSeeder::class,
        ]);
    }
}
