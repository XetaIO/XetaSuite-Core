<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use XetaSuite\Enums\Maintenances\MaintenanceRealization;
use XetaSuite\Models\Company;
use XetaSuite\Models\Maintenance;
use XetaSuite\Models\User;

class CompaniesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $maintenances = Maintenance::whereIn('realization', [MaintenanceRealization::BOTH->value, MaintenanceRealization::EXTERNAL->value])->get();

        $user = User::firstWhere('email', 'admin@xetasuite.test');

        foreach ($maintenances as $maintenance) {
            $companies = Company::factory()
                    ->createdBy($user)
                    ->count(random_int(1, 2))
                    ->create();

            $maintenance->companies()->attach($companies->pluck('id')->toArray());

            $companies->each(function (Company $company) {
                $company->increment('maintenance_count');
            });

            // Doing the same for operators
            if ($maintenance->realization === MaintenanceRealization::BOTH) {
                $operators = User::inRandomOrder()->limit(random_int(1, 2))->get();

                $maintenance->operators()->attach($operators->pluck('id')->toArray());
            }

        }
    }
}
