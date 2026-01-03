<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use XetaSuite\Models\Permission;
use XetaSuite\Models\Role;
use XetaSuite\Models\Setting;
use XetaSuite\Models\Site;
use XetaSuite\Models\User;

uses(RefreshDatabase::class);

describe('Login when login_enabled setting', function () {
    it('allows login when login_enabled is true', function () {
        // Create login_enabled setting with true
        Setting::factory()->create([
            'key' => 'login_enabled',
            'value' => true,
            'model_type' => null,
            'model_id' => null,
        ]);

        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertOk();
    });

    it('denies login when login_enabled is false', function () {
        // Create login_enabled setting with false
        Setting::factory()->create([
            'key' => 'login_enabled',
            'value' => false,
            'model_type' => null,
            'model_id' => null,
        ]);

        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email'])
            ->assertJsonPath('errors.email.0', __('auth.login_disabled'));
    });

    it('allows login when login_enabled setting does not exist (default behavior)', function () {
        // No login_enabled setting exists - should default to allowing login
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertOk();
    });

    it('still validates credentials when login is enabled', function () {
        Setting::factory()->create([
            'key' => 'login_enabled',
            'value' => true,
            'model_type' => null,
            'model_id' => null,
        ]);

        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('allows login with bypass.login permission when login_enabled is false', function () {
        // Create login_enabled setting with false
        Setting::factory()->create([
            'key' => 'login_enabled',
            'value' => false,
            'model_type' => null,
            'model_id' => null,
        ]);

        // Create bypass.login permission
        $permission = Permission::create(['name' => 'bypass.login', 'guard_name' => 'web']);

        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        // Assign permission directly to user with site_id = 0 (global)
        setPermissionsTeamId(0);
        $user->givePermissionTo($permission);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertOk();
    });

    it('denies login without bypass.login permission when login_enabled is false', function () {
        // Create login_enabled setting with false
        Setting::factory()->create([
            'key' => 'login_enabled',
            'value' => false,
            'model_type' => null,
            'model_id' => null,
        ]);

        // Create a site and a role WITHOUT bypass.login permission
        $site = Site::factory()->create();
        $role = Role::create(['name' => 'user', 'guard_name' => 'web']);

        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        // Attach user to site with the role (no bypass.login permission)
        $user->sites()->attach($site->id);
        setPermissionsTeamId($site->id);
        $user->assignRole($role);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email'])
            ->assertJsonPath('errors.email.0', __('auth.login_disabled'));
    });
});
