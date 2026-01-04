<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use XetaSuite\Models\Cleaning;
use XetaSuite\Models\Incident;
use XetaSuite\Models\Maintenance;
use XetaSuite\Models\Material;
use XetaSuite\Models\Site;
use XetaSuite\Models\User;
use XetaSuite\Models\Zone;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Create headquarters site
    $this->headquarters = Site::factory()->create(['is_headquarters' => true]);

    // Create regular sites
    $this->regularSite = Site::factory()->create(['is_headquarters' => false]);
    $this->secondSite = Site::factory()->create(['is_headquarters' => false]);

    // Create permissions
    $permissions = [
        'user.viewAny',
        'user.view',
        'user.create',
        'user.update',
        'user.delete',
        'user.restore',
        'user.assignDirectPermission',
        'user.assignSite',
    ];

    foreach ($permissions as $permission) {
        Permission::create(['name' => $permission, 'guard_name' => 'web']);
    }

    // Create role with all permissions
    $this->role = Role::create(['name' => 'user-manager', 'guard_name' => 'web']);
    $this->role->givePermissionTo($permissions);

    // Create a second role for assignment tests
    $this->operatorRole = Role::create(['name' => 'operator', 'guard_name' => 'web']);
});

// ============================================================================
// INDEX TESTS
// ============================================================================

describe('index', function (): void {
    it('returns paginated list of users for authorized user', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        User::factory()->count(25)->withSite($this->headquarters)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/users');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'username', 'first_name', 'last_name', 'full_name', 'email', 'avatar'],
                ],
                'links',
                'meta',
            ])
            ->assertJsonCount(20, 'data'); // Default pagination
    });

    it('can filter users by site_id', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        User::factory()->count(5)->withSite($this->headquarters)->create();
        User::factory()->count(3)->withSite($this->regularSite)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/users?site_id=' . $this->regularSite->id);

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('can search users by name or email', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        User::factory()->withSite($this->headquarters)->create(['first_name' => 'Zephyrin', 'last_name' => 'Doe']);
        User::factory()->withSite($this->headquarters)->create(['first_name' => 'Jane', 'last_name' => 'Smith']);
        User::factory()->withSite($this->headquarters)->create(['email' => 'unique@test.com']);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/users?search=Zephyrin');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.first_name', 'Zephyrin');
    });

    it('denies access for user without viewAny permission', function (): void {
        $roleWithoutViewAny = Role::create(['name' => 'limited', 'guard_name' => 'web']);
        $user = createUserOnHeadquarters($this->headquarters, $roleWithoutViewAny);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/users');

        $response->assertForbidden();
    });

    it('requires authentication', function (): void {
        $response = $this->getJson('/api/v1/users');

        $response->assertUnauthorized();
    });
});

// ============================================================================
// SHOW TESTS
// ============================================================================

describe('show', function (): void {
    it('returns user details for authorized user', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $targetUser = User::factory()->withSite($this->headquarters)->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/users/{$targetUser->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'username',
                    'first_name',
                    'last_name',
                    'full_name',
                    'email',
                    'locale',
                    'sites',
                    'sites_with_roles',
                    'sites_with_permissions',
                ],
            ])
            ->assertJsonPath('data.first_name', 'Test')
            ->assertJsonPath('data.last_name', 'User');
    });

    it('returns 404 for non-existent user', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/users/99999');

        $response->assertNotFound();
    });
});

// ============================================================================
// STORE TESTS
// ============================================================================

describe('store', function (): void {
    it('creates a new user for authorized user', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/users', [
                'username' => 'newuser',
                'email' => 'newuser@example.com',
                'first_name' => 'New',
                'last_name' => 'User',
                'locale' => 'fr',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.username', 'newuser')
            ->assertJsonPath('data.email', 'newuser@example.com')
            ->assertJsonPath('data.first_name', 'New')
            ->assertJsonPath('data.last_name', 'User');

        $this->assertDatabaseHas('users', [
            'username' => 'newuser',
            'email' => 'newuser@example.com',
        ]);
    });

    it('creates user with sites and roles', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/users', [
                'username' => 'newuser',
                'email' => 'newuser@example.com',
                'first_name' => 'New',
                'last_name' => 'User',
                'sites' => [
                    [
                        'id' => $this->headquarters->id,
                        'roles' => ['operator'],
                    ],
                    [
                        'id' => $this->regularSite->id,
                        'roles' => ['operator'],
                    ],
                ],
            ]);

        $response->assertCreated();

        $newUser = User::where('username', 'newuser')->first();
        expect($newUser->sites)->toHaveCount(2);

        // Check roles are assigned per site
        setPermissionsTeamId($this->headquarters->id);
        $newUser->unsetRelation('roles');
        expect($newUser->hasRole('operator'))->toBeTrue();
    });

    it('validates required fields', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/users', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['username', 'email', 'first_name', 'last_name']);
    });

    it('validates unique username and email', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        User::factory()->create(['username' => 'existing', 'email' => 'existing@example.com']);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/users', [
                'username' => 'existing',
                'email' => 'existing@example.com',
                'first_name' => 'Test',
                'last_name' => 'User',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['username', 'email']);
    });

    it('denies access for user without create permission', function (): void {
        $roleWithoutCreate = Role::create(['name' => 'viewer', 'guard_name' => 'web']);
        $roleWithoutCreate->givePermissionTo('user.viewAny');
        $user = createUserOnHeadquarters($this->headquarters, $roleWithoutCreate);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/users', [
                'username' => 'newuser',
                'email' => 'newuser@example.com',
                'first_name' => 'New',
                'last_name' => 'User',
            ]);

        $response->assertForbidden();
    });
});

// ============================================================================
// UPDATE TESTS
// ============================================================================

describe('update', function (): void {
    it('updates user for authorized user', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);
        $targetUser = User::factory()->withSite($this->headquarters)->create();

        $response = $this->actingAs($user)
            ->putJson("/api/v1/users/{$targetUser->id}", [
                'first_name' => 'Updated',
                'last_name' => 'Name',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.first_name', 'Updated')
            ->assertJsonPath('data.last_name', 'Name');
    });

    it('updates user sites and roles', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);
        $targetUser = User::factory()->withSite($this->headquarters)->create();

        $response = $this->actingAs($user)
            ->putJson("/api/v1/users/{$targetUser->id}", [
                'sites' => [
                    [
                        'id' => $this->regularSite->id,
                        'roles' => ['operator'],
                    ],
                ],
            ]);

        $response->assertOk();

        $targetUser->refresh();
        expect($targetUser->sites)->toHaveCount(1);
        expect($targetUser->sites->first()->id)->toBe($this->regularSite->id);
    });

    it('validates unique username and email on update', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);
        $existingUser = User::factory()->create(['username' => 'taken', 'email' => 'taken@example.com']);
        $targetUser = User::factory()->withSite($this->headquarters)->create();

        $response = $this->actingAs($user)
            ->putJson("/api/v1/users/{$targetUser->id}", [
                'username' => 'taken',
                'email' => 'taken@example.com',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['username', 'email']);
    });

    it('allows user to keep their own username and email', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);
        $targetUser = User::factory()->withSite($this->headquarters)->create([
            'username' => 'myusername',
            'email' => 'myemail@example.com',
        ]);

        $response = $this->actingAs($user)
            ->putJson("/api/v1/users/{$targetUser->id}", [
                'username' => 'myusername',
                'email' => 'myemail@example.com',
                'first_name' => 'Updated',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.first_name', 'Updated');
    });
});

// ============================================================================
// DELETE TESTS
// ============================================================================

describe('destroy', function (): void {
    it('soft deletes user for authorized user', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);
        $targetUser = User::factory()->withSite($this->headquarters)->create();

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/users/{$targetUser->id}");

        $response->assertOk()
            ->assertJsonPath('message', __('users.deleted'));

        $this->assertSoftDeleted('users', ['id' => $targetUser->id]);
    });

    it('records who deleted the user', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);
        $targetUser = User::factory()->withSite($this->headquarters)->create();

        $this->actingAs($user)
            ->deleteJson("/api/v1/users/{$targetUser->id}");

        $targetUser->refresh();
        expect($targetUser->deleted_by_id)->toBe($user->id);
    });

    it('returns 404 for non-existent user', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/users/99999');

        $response->assertNotFound();
    });
});

// ============================================================================
// AVAILABLE OPTIONS TESTS
// ============================================================================

describe('available options', function (): void {
    it('returns available sites for user assignment', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/users/available-sites');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'is_headquarters'],
                ],
            ]);
    });

    it('returns available roles', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/users/available-roles');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name'],
                ],
            ]);
    });

    it('returns available permissions for user with assignDirectPermission', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/users/available-permissions');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name'],
                ],
            ]);
    });

    it('denies permissions list for user without assignDirectPermission', function (): void {
        $roleWithoutAssign = Role::create(['name' => 'basic', 'guard_name' => 'web']);
        $roleWithoutAssign->givePermissionTo(['user.viewAny', 'user.view']);
        $user = createUserOnHeadquarters($this->headquarters, $roleWithoutAssign);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/users/available-permissions');

        $response->assertForbidden();
    });
});

// ============================================================================
// USER ACTIVITIES TESTS
// ============================================================================

describe('user activities', function (): void {
    it('returns user cleanings', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);
        $targetUser = User::factory()->withSite($this->headquarters)->create();
        $zone = Zone::factory()->forSite($this->headquarters)->create();
        $material = Material::factory()->forZone($zone)->create();

        // Create cleanings for target user
        Cleaning::factory()
            ->count(5)
            ->forSite($this->headquarters)
            ->forMaterial($material)
            ->createdBy($targetUser)
            ->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/users/{$targetUser->id}/cleanings");

        $response->assertOk()
            ->assertJsonCount(5, 'data');
    });

    it('returns user maintenances', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);
        $targetUser = User::factory()->withSite($this->headquarters)->create();
        $zone = Zone::factory()->forSite($this->headquarters)->create();
        $material = Material::factory()->forZone($zone)->create();

        // Create maintenances for target user
        Maintenance::factory()
            ->count(3)
            ->forSite($this->headquarters)
            ->forMaterial($material)
            ->createdBy($targetUser)
            ->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/users/{$targetUser->id}/maintenances");

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('returns user incidents', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);
        $targetUser = User::factory()->withSite($this->headquarters)->create();
        $zone = Zone::factory()->forSite($this->headquarters)->create();
        $material = Material::factory()->forZone($zone)->create();

        // Create incidents for target user
        Incident::factory()
            ->count(4)
            ->forSite($this->headquarters)
            ->forMaterial($material)
            ->reportedBy($targetUser)
            ->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/users/{$targetUser->id}/incidents");

        $response->assertOk()
            ->assertJsonCount(4, 'data');
    });

    it('returns roles per site for a user', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);
        $targetUser = User::factory()->create();
        $targetUser->sites()->attach([$this->headquarters->id, $this->regularSite->id]);

        // Assign roles per site
        setPermissionsTeamId($this->headquarters->id);
        $targetUser->assignRole('operator');
        setPermissionsTeamId($this->regularSite->id);
        $targetUser->assignRole('user-manager');

        $response = $this->actingAs($user)
            ->getJson("/api/v1/users/{$targetUser->id}/roles-per-site");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'roles_per_site' => [
                        '*' => [
                            'site' => ['id', 'name', 'is_headquarters'],
                            'roles',
                        ],
                    ],
                    'permissions_per_site',
                ],
            ]);
    });
});
