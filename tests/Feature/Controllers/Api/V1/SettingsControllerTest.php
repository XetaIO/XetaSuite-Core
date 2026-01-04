<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use XetaSuite\Models\Setting;
use XetaSuite\Models\Site;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Create headquarters site
    $this->headquarters = Site::factory()->create(['is_headquarters' => true]);

    // Create a regular site
    $this->regularSite = Site::factory()->create(['is_headquarters' => false]);

    // Create permissions
    $permissions = [
        'setting.viewAny',
        'setting.view',
        'setting.update',
    ];

    foreach ($permissions as $permission) {
        Permission::create(['name' => $permission, 'guard_name' => 'web']);
    }

    // Create role with all permissions
    $this->role = Role::create(['name' => 'settings-manager', 'guard_name' => 'web']);
    $this->role->givePermissionTo($permissions);

    // Create test settings
    $this->currencySetting = Setting::factory()->create([
        'key' => 'currency',
        'value' => 'EUR',
        'model_type' => null,
        'model_id' => null,
        'label' => 'Currency',
    ]);

    $this->loginEnabledSetting = Setting::factory()->create([
        'key' => 'login_enabled',
        'value' => true,
        'model_type' => null,
        'model_id' => null,
        'label' => 'Login Enabled',
    ]);
});

// ============================================================================
// PUBLIC ENDPOINT TESTS
// ============================================================================

describe('public', function (): void {
    it('returns settings as key-value pairs', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/settings');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [],
            ])
            ->assertJsonPath('data.currency', 'EUR')
            ->assertJsonPath('data.login_enabled', true);
    });

    it('only returns global settings (null model_type and model_id)', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        // Create a user-scoped setting
        Setting::factory()->withModel('XetaSuite\Models\User', 1)->create([
            'key' => 'user_preference',
            'value' => 'test',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/settings');

        $response->assertOk();
        expect($response->json('data'))->not->toHaveKey('user_preference');
    });
});

// ============================================================================
// INDEX (MANAGE) TESTS
// ============================================================================

describe('index', function (): void {
    it('returns all settings with full details for authorized user on headquarters', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/settings/manage');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'key', 'value', 'label', 'created_at', 'updated_at'],
                ],
            ]);
    });

    it('returns settings ordered by key', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/settings/manage');

        $response->assertOk();
        $keys = collect($response->json('data'))->pluck('key')->toArray();
        expect($keys)->toBe(['currency', 'login_enabled']);
    });

    it('denies access for user without viewAny permission', function (): void {
        $roleWithoutViewAny = Role::create(['name' => 'limited', 'guard_name' => 'web']);
        $user = createUserOnHeadquarters($this->headquarters, $roleWithoutViewAny);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/settings/manage');

        $response->assertForbidden();
    });

    it('requires authentication', function (): void {
        $response = $this->getJson('/api/v1/settings/manage');

        $response->assertUnauthorized();
    });
});

// ============================================================================
// SHOW TESTS
// ============================================================================

describe('show', function (): void {
    it('returns setting details for authorized user', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/settings/{$this->currencySetting->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'key', 'value', 'label', 'created_at', 'updated_at'],
            ])
            ->assertJsonPath('data.key', 'currency')
            ->assertJsonPath('data.value', 'EUR');
    });

    it('denies access for user without view permission', function (): void {
        $roleWithoutView = Role::create(['name' => 'limited', 'guard_name' => 'web']);
        $user = createUserOnHeadquarters($this->headquarters, $roleWithoutView);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/settings/{$this->currencySetting->id}");

        $response->assertForbidden();
    });

    it('returns 404 for non-existent setting', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/settings/99999');

        $response->assertNotFound();
    });
});

// ============================================================================
// UPDATE TESTS
// ============================================================================

describe('update', function (): void {
    it('updates currency setting from headquarters', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->putJson("/api/v1/settings/{$this->currencySetting->id}", [
                'value' => 'USD',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.value', 'USD');

        $this->assertDatabaseHas('settings', [
            'id' => $this->currencySetting->id,
            'key' => 'currency',
            'updated_by_id' => $user->id,
        ]);
    });

    it('updates login_enabled setting from headquarters', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->putJson("/api/v1/settings/{$this->loginEnabledSetting->id}", [
                'value' => false,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.value', false);
    });

    it('denies update from regular site', function (): void {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $response = $this->actingAs($user)
            ->putJson("/api/v1/settings/{$this->currencySetting->id}", [
                'value' => 'USD',
            ]);

        $response->assertForbidden();
    });

    it('denies update for user without update permission', function (): void {
        $roleWithoutUpdate = Role::create(['name' => 'viewer', 'guard_name' => 'web']);
        $roleWithoutUpdate->givePermissionTo(['setting.viewAny', 'setting.view']);
        $user = createUserOnHeadquarters($this->headquarters, $roleWithoutUpdate);

        $response = $this->actingAs($user)
            ->putJson("/api/v1/settings/{$this->currencySetting->id}", [
                'value' => 'USD',
            ]);

        $response->assertForbidden();
    });

    it('validates currency setting must be 3 characters', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->putJson("/api/v1/settings/{$this->currencySetting->id}", [
                'value' => 'EURO', // 4 characters - invalid
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['value']);
    });

    it('validates login_enabled must be boolean', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->putJson("/api/v1/settings/{$this->loginEnabledSetting->id}", [
                'value' => 'not-a-boolean',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['value']);
    });

    it('clears cache after update', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        // First, access the setting to cache it
        $settings = app(\XetaSuite\Settings\Settings::class);
        $cachedValue = $settings->withoutContext()->get('currency');
        expect($cachedValue)->toBe('EUR');

        // Update the setting
        $this->actingAs($user)
            ->putJson("/api/v1/settings/{$this->currencySetting->id}", [
                'value' => 'USD',
            ])
            ->assertOk();

        // Verify cache was cleared and new value is returned
        $newValue = $settings->withoutContext()->get('currency');
        expect($newValue)->toBe('USD');
    });
});
