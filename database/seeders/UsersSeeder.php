<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use XetaSuite\Models\User;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::factory()->admin()->create([
            'email' => 'admin@xetasuite.test',
        ]);

        // Staff
        User::factory()->count(3)->create();
    }
}
