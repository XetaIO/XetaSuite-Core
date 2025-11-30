<?php

declare(strict_types=1);

use Spatie\Permission\Models\Role;
use XetaSuite\Models\Site;
use XetaSuite\Models\User;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
 // ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

pest()->extend(Tests\TestCase::class)
    ->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Helper to create authenticated user on headquarters with role
 */
function createUserOnHeadquarters(Site $headquarters, Role $role): User
{
    $user = User::factory()->withSite($headquarters)->create();

    // Assign role to user for this specific site (team)
    setPermissionsTeamId($headquarters->id);
    $user->assignRole($role);

    // Simulate middleware setting session
    session([
        'current_site_id' => $headquarters->id,
        'is_on_headquarters' => true,
    ]);

    return $user;
}

/**
 * Helper to create authenticated user on regular site with role
 */
function createUserOnRegularSite(Site $site, Role $role): User
{
    $user = User::factory()->withSite($site)->create();

    // Assign role to user for this specific site (team)
    setPermissionsTeamId($site->id);
    $user->assignRole($role);

    // Simulate middleware setting session
    session([
        'current_site_id' => $site->id,
        'is_on_headquarters' => false,
    ]);

    return $user;
}
