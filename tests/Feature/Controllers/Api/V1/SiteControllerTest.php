<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use XetaSuite\Models\Site;
use XetaSuite\Models\Zone;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create headquarters site
    $this->headquarters = Site::factory()->create(['is_headquarters' => true]);

    // Create a regular site
    $this->regularSite = Site::factory()->create(['is_headquarters' => false]);

    // Create permissions
    $permissions = [
        'site.viewAny',
        'site.view',
        'site.create',
        'site.update',
        'site.delete',
    ];

    foreach ($permissions as $permission) {
        Permission::create(['name' => $permission, 'guard_name' => 'web']);
    }

    // Create role with all permissions
    $this->role = Role::create(['name' => 'site-manager', 'guard_name' => 'web']);
    $this->role->givePermissionTo($permissions);
});

// ============================================================================
// INDEX TESTS
// ============================================================================

describe('index', function () {
    it('returns paginated list of sites for authorized user on headquarters', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        Site::factory()->count(5)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/sites');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'is_headquarters', 'city', 'created_at'],
                ],
                'links',
                'meta',
            ]);
    });

    it('returns sites ordered by headquarters first then by name', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        Site::factory()->create(['name' => 'Zebra Site', 'is_headquarters' => false]);
        Site::factory()->create(['name' => 'Alpha Site', 'is_headquarters' => false]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/sites');

        $response->assertOk();
        $data = $response->json('data');
        // First should be headquarters
        expect($data[0]['is_headquarters'])->toBeTrue();
    });

    it('includes zones_count and users_count in response', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        Zone::factory()->count(3)->forSite($this->headquarters)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/sites');

        $response->assertOk();
        $hqData = collect($response->json('data'))->firstWhere('id', $this->headquarters->id);
        expect($hqData['zones_count'])->toBe(3);
    });

    it('filters sites by search term', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        Site::factory()->create(['name' => 'Paris Office', 'city' => 'Paris']);
        Site::factory()->create(['name' => 'Lyon Office', 'city' => 'Lyon']);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/sites?search=Paris');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name');
        expect($names)->toContain('Paris Office');
    });

    it('denies access for user not on headquarters', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/sites');

        $response->assertForbidden();
    });

    it('denies access for user without viewAny permission', function () {
        $roleWithoutViewAny = Role::create(['name' => 'limited', 'guard_name' => 'web']);
        $user = createUserOnHeadquarters($this->headquarters, $roleWithoutViewAny);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/sites');

        $response->assertForbidden();
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/sites');

        $response->assertUnauthorized();
    });
});

// ============================================================================
// SHOW TESTS
// ============================================================================

describe('show', function () {
    it('returns site details for authorized user', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $site = Site::factory()->create([
            'name' => 'Test Site',
            'email' => 'test@example.com',
            'city' => 'Paris',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/sites/{$site->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $site->id)
            ->assertJsonPath('data.name', 'Test Site')
            ->assertJsonPath('data.email', 'test@example.com')
            ->assertJsonPath('data.city', 'Paris')
            ->assertJsonStructure([
                'data' => [
                    'id', 'name', 'is_headquarters', 'email', 'office_phone',
                    'cell_phone', 'address_line_1', 'address_line_2', 'postal_code', 'city', 'country',
                    'zones_count', 'users_count', 'created_at', 'updated_at',
                ],
            ]);
    });

    it('denies access for user not on headquarters', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $site = Site::factory()->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/sites/{$site->id}");

        $response->assertForbidden();
    });

    it('denies access for user without view permission', function () {
        $roleWithoutView = Role::create(['name' => 'limited', 'guard_name' => 'web']);
        $user = createUserOnHeadquarters($this->headquarters, $roleWithoutView);

        $site = Site::factory()->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/sites/{$site->id}");

        $response->assertForbidden();
    });

    it('returns 404 for non-existent site', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/sites/99999');

        $response->assertNotFound();
    });
});

// ============================================================================
// STORE TESTS
// ============================================================================

describe('store', function () {
    it('creates a new site for authorized user', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/sites', [
                'name' => 'New Site',
                'email' => 'new@example.com',
                'city' => 'Lyon',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'New Site')
            ->assertJsonPath('data.email', 'new@example.com')
            ->assertJsonPath('data.city', 'Lyon')
            ->assertJsonPath('data.is_headquarters', false);

        $this->assertDatabaseHas('sites', [
            'name' => 'New Site',
            'email' => 'new@example.com',
        ]);
    });

    it('creates a regular site by default', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/sites', [
                'name' => 'Regular Site',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.is_headquarters', false);
    });

    it('prevents creating a second headquarters', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/sites', [
                'name' => 'Second HQ',
                'is_headquarters' => true,
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', __('sites.headquarters_already_exists'));
    });

    it('validates required fields', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/sites', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('validates email format', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/sites', [
                'name' => 'Test Site',
                'email' => 'invalid-email',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('denies access for user not on headquarters', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/sites', [
                'name' => 'New Site',
            ]);

        $response->assertForbidden();
    });

    it('denies access for user without create permission', function () {
        $roleWithoutCreate = Role::create(['name' => 'limited', 'guard_name' => 'web']);
        $user = createUserOnHeadquarters($this->headquarters, $roleWithoutCreate);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/sites', [
                'name' => 'New Site',
            ]);

        $response->assertForbidden();
    });
});

// ============================================================================
// UPDATE TESTS
// ============================================================================

describe('update', function () {
    it('updates a site for authorized user', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $site = Site::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($user)
            ->putJson("/api/v1/sites/{$site->id}", [
                'name' => 'New Name',
                'city' => 'Paris',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.city', 'Paris');

        $this->assertDatabaseHas('sites', [
            'id' => $site->id,
            'name' => 'New Name',
        ]);
    });

    it('prevents setting headquarters when one already exists', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $site = Site::factory()->create(['is_headquarters' => false]);

        $response = $this->actingAs($user)
            ->putJson("/api/v1/sites/{$site->id}", [
                'is_headquarters' => true,
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', __('sites.headquarters_already_exists'));
    });

    it('prevents removing headquarters status', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->putJson("/api/v1/sites/{$this->headquarters->id}", [
                'is_headquarters' => false,
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', __('sites.cannot_remove_headquarters_status'));
    });

    it('allows updating headquarters name without changing status', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->putJson("/api/v1/sites/{$this->headquarters->id}", [
                'name' => 'Updated HQ Name',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated HQ Name')
            ->assertJsonPath('data.is_headquarters', true);
    });

    it('denies access for user not on headquarters', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $response = $this->actingAs($user)
            ->putJson("/api/v1/sites/{$this->regularSite->id}", [
                'name' => 'New Name',
            ]);

        $response->assertForbidden();
    });

    it('denies access for user without update permission', function () {
        $roleWithoutUpdate = Role::create(['name' => 'limited', 'guard_name' => 'web']);
        $user = createUserOnHeadquarters($this->headquarters, $roleWithoutUpdate);

        $site = Site::factory()->create();

        $response = $this->actingAs($user)
            ->putJson("/api/v1/sites/{$site->id}", [
                'name' => 'New Name',
            ]);

        $response->assertForbidden();
    });
});

// ============================================================================
// DESTROY TESTS
// ============================================================================

describe('destroy', function () {
    it('deletes a site without zones for authorized user', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $site = Site::factory()->create(['is_headquarters' => false]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/sites/{$site->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('sites', ['id' => $site->id]);
    });

    it('prevents deleting headquarters site', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/sites/{$this->headquarters->id}");

        $response->assertForbidden();

        $this->assertDatabaseHas('sites', ['id' => $this->headquarters->id]);
    });

    it('prevents deleting site with zones', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $site = Site::factory()->create(['is_headquarters' => false]);
        Zone::factory()->forSite($site)->create();

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/sites/{$site->id}");

        $response->assertUnprocessable()
            ->assertJsonPath('message', __('sites.cannot_delete_has_zones'));

        $this->assertDatabaseHas('sites', ['id' => $site->id]);
    });

    it('denies access for user not on headquarters', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $site = Site::factory()->create(['is_headquarters' => false]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/sites/{$site->id}");

        $response->assertForbidden();
    });

    it('denies access for user without delete permission', function () {
        $roleWithoutDelete = Role::create(['name' => 'limited', 'guard_name' => 'web']);
        $user = createUserOnHeadquarters($this->headquarters, $roleWithoutDelete);

        $site = Site::factory()->create(['is_headquarters' => false]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/sites/{$site->id}");

        $response->assertForbidden();
    });
});
