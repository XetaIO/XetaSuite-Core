<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use XetaSuite\Models\Site;
use XetaSuite\Models\User;
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
        expect($hqData['zone_count'])->toBe(3);
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
                    'cell_phone', 'address', 'zip_code', 'city', 'country',
                    'zone_count', 'user_count', 'created_at', 'updated_at',
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

        $response->assertConflict();

        $this->assertDatabaseHas('sites', ['id' => $this->headquarters->id]);
    });

    it('prevents deleting site with zones', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $site = Site::factory()->create(['is_headquarters' => false]);
        Zone::factory()->forSite($site)->create();

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/sites/{$site->id}");

        $response->assertConflict()
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

// ============================================================================
// USERS ENDPOINT TESTS
// ============================================================================

describe('users', function () {
    it('returns users for a site for authorized user', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        // Create users and attach them to the headquarters
        $siteUsers = User::factory()->count(3)->create();
        $this->headquarters->users()->attach($siteUsers->pluck('id'));

        $response = $this->actingAs($user)
            ->getJson("/api/v1/sites/{$this->headquarters->id}/users");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'full_name', 'email', 'avatar', 'roles', 'created_at'],
                ],
            ]);

        expect(count($response->json('data')))->toBeGreaterThanOrEqual(3);
    });

    it('returns only users attached to the site', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        // Create users - some attached to HQ, some not
        $attachedUsers = User::factory()->count(2)->create();
        $notAttachedUsers = User::factory()->count(2)->create();

        $this->headquarters->users()->attach($attachedUsers->pluck('id'));

        $response = $this->actingAs($user)
            ->getJson("/api/v1/sites/{$this->headquarters->id}/users");

        $response->assertOk();

        $returnedIds = collect($response->json('data'))->pluck('id')->toArray();
        foreach ($attachedUsers as $attachedUser) {
            expect($returnedIds)->toContain($attachedUser->id);
        }
        foreach ($notAttachedUsers as $notAttached) {
            expect($returnedIds)->not->toContain($notAttached->id);
        }
    });

    it('filters users by search term', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $john = User::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
        $jane = User::factory()->create(['first_name' => 'Jane', 'last_name' => 'Smith']);

        $this->headquarters->users()->attach([$john->id, $jane->id]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/sites/{$this->headquarters->id}/users?search=John");

        $response->assertOk();

        $names = collect($response->json('data'))->pluck('full_name');
        expect($names)->toContain('John Doe');
        expect($names)->not->toContain('Jane Smith');
    });

    it('limits results to 15 users', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        // Create 20 users and attach them to the headquarters
        $siteUsers = User::factory()->count(20)->create();
        $this->headquarters->users()->attach($siteUsers->pluck('id'));

        $response = $this->actingAs($user)
            ->getJson("/api/v1/sites/{$this->headquarters->id}/users");

        $response->assertOk();

        expect(count($response->json('data')))->toBeLessThanOrEqual(15);
    });

    it('includes user roles for the site', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $testRole = Role::create(['name' => 'test-role', 'guard_name' => 'web']);
        $siteUser = User::factory()->create();
        $this->headquarters->users()->attach($siteUser->id);

        // Assign role to user for this site
        setPermissionsTeamId($this->headquarters->id);
        $siteUser->assignRole($testRole);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/sites/{$this->headquarters->id}/users");

        $response->assertOk();

        $userData = collect($response->json('data'))->firstWhere('id', $siteUser->id);
        expect($userData['roles'])->toContain('test-role');
    });

    it('denies access for user not on headquarters', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/sites/{$this->headquarters->id}/users");

        $response->assertForbidden();
    });

    it('denies access for user without view permission', function () {
        $roleWithoutView = Role::create(['name' => 'limited-users', 'guard_name' => 'web']);
        $user = createUserOnHeadquarters($this->headquarters, $roleWithoutView);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/sites/{$this->headquarters->id}/users");

        $response->assertForbidden();
    });

    it('requires authentication', function () {
        $response = $this->getJson("/api/v1/sites/{$this->headquarters->id}/users");

        $response->assertUnauthorized();
    });
});

// ============================================================================
// MANAGERS TESTS
// ============================================================================

describe('managers', function () {
    it('creates a site with managers', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        // Create users to be managers
        $managers = User::factory()->count(2)->create();

        // Note: For creation, we cannot assign managers yet because the site doesn't exist
        // and users must be attached to the site first. This test validates the structure.
        $response = $this->actingAs($user)
            ->postJson('/api/v1/sites', [
                'name' => 'Site With Managers',
                'manager_ids' => $managers->pluck('id')->toArray(),
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Site With Managers')
            ->assertJsonStructure([
                'data' => ['id', 'name', 'managers'],
            ]);
    });

    it('updates a site to add managers', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $site = Site::factory()->create(['is_headquarters' => false]);

        // Create and attach users to the site
        $potentialManagers = User::factory()->count(3)->create();
        $site->users()->attach($potentialManagers->pluck('id'));

        // Update site to make some users managers
        $managerIds = $potentialManagers->take(2)->pluck('id')->toArray();

        $response = $this->actingAs($user)
            ->putJson("/api/v1/sites/{$site->id}", [
                'manager_ids' => $managerIds,
            ]);

        $response->assertOk()
            ->assertJsonCount(2, 'data.managers');

        // Verify managers are set correctly in database
        $site->refresh();
        expect($site->managers()->count())->toBe(2);
        foreach ($managerIds as $managerId) {
            expect($site->managers()->pluck('users.id')->toArray())->toContain($managerId);
        }
    });

    it('updates a site to remove managers', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $site = Site::factory()->create(['is_headquarters' => false]);

        // Create users and set them as managers
        $managers = User::factory()->count(3)->create();
        foreach ($managers as $manager) {
            $site->users()->attach($manager->id, ['manager' => true]);
        }

        expect($site->managers()->count())->toBe(3);

        // Update to remove all managers
        $response = $this->actingAs($user)
            ->putJson("/api/v1/sites/{$site->id}", [
                'manager_ids' => [],
            ]);

        $response->assertOk()
            ->assertJsonCount(0, 'data.managers');

        $site->refresh();
        expect($site->managers()->count())->toBe(0);
    });

    it('updates a site to change managers', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $site = Site::factory()->create(['is_headquarters' => false]);

        // Create users - 2 current managers, 2 potential new managers
        $currentManagers = User::factory()->count(2)->create();
        $newManagers = User::factory()->count(2)->create();

        // Attach all users to site
        foreach ($currentManagers as $manager) {
            $site->users()->attach($manager->id, ['manager' => true]);
        }
        $site->users()->attach($newManagers->pluck('id'));

        // Update to swap managers
        $response = $this->actingAs($user)
            ->putJson("/api/v1/sites/{$site->id}", [
                'manager_ids' => $newManagers->pluck('id')->toArray(),
            ]);

        $response->assertOk()
            ->assertJsonCount(2, 'data.managers');

        $site->refresh();

        // Verify new managers are set
        foreach ($newManagers as $newManager) {
            expect($site->managers()->pluck('users.id')->toArray())->toContain($newManager->id);
        }

        // Verify old managers are no longer managers
        foreach ($currentManagers as $oldManager) {
            expect($site->managers()->pluck('users.id')->toArray())->not->toContain($oldManager->id);
        }
    });

    it('returns managers in site detail response', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $site = Site::factory()->create(['is_headquarters' => false]);

        // Create and set managers
        $managers = User::factory()->count(2)->create();
        foreach ($managers as $manager) {
            $site->users()->attach($manager->id, ['manager' => true]);
        }

        $response = $this->actingAs($user)
            ->getJson("/api/v1/sites/{$site->id}");

        $response->assertOk()
            ->assertJsonCount(2, 'data.managers')
            ->assertJsonStructure([
                'data' => [
                    'managers' => [
                        '*' => ['id', 'first_name', 'last_name', 'full_name', 'avatar'],
                    ],
                ],
            ]);
    });

    it('validates manager_ids is an array', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/sites', [
                'name' => 'Test Site',
                'manager_ids' => 'not-an-array',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['manager_ids']);
    });

    it('validates manager_ids contains existing user ids', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/sites', [
                'name' => 'Test Site',
                'manager_ids' => [99999, 99998],
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['manager_ids.0', 'manager_ids.1']);
    });
});
