<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use XetaSuite\Models\Site;
use XetaSuite\Models\User;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        $user = User::factory()->admin()->create([
            'email' => 'admin@xetasuite.test',
        ]);
        $user->sites()->sync(Site::get()->pluck('id')->toArray());

        // Other users
        $users = User::factory()->count(3)->create();
    }
}
