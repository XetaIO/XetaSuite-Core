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

        $emailDomain = config('app.demo_mode', false) ? 'xetasuite.demo' : 'xetasuite.test';
        $user = User::firstWhere('email', "admin@{$emailDomain}");

        foreach ($maintenances as $maintenance) {
            $companies = Company::factory()
                ->createdBy($user)
                ->count(random_int(1, 2))
                ->create();
            $maintenance->companies()->attach($companies->pluck('id')->toArray());

            // Doing the same for operators
            if ($maintenance->realization === MaintenanceRealization::BOTH) {
                $operators = User::inRandomOrder()->limit(random_int(1, 2))->get();

                $maintenance->operators()->attach($operators->pluck('id')->toArray());
            }

        }
    }
}
