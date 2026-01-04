<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use XetaSuite\Models\Permission;
use XetaSuite\Models\Role;
use XetaSuite\Models\Site;
use XetaSuite\Models\User;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Create headquarters site
    $this->headquarters = Site::factory()->create(['is_headquarters' => true]);

    // Create a regular site
    $this->regularSite = Site::factory()->create(['is_headquarters' => false]);

    // Create permissions for role management
    $rolePermissions = [
        'role.viewAny',
        'role.view',
        'role.create',
        'role.update',
        'role.delete',
    ];

    // Create some extra permissions to assign to roles
    $otherPermissions = [
        'user.viewAny',
        'user.view',
        'user.create',
        'material.viewAny',
        'material.view',
    ];

    foreach (array_merge($rolePermissions, $otherPermissions) as $permission) {
        Permission::create(['name' => $permission, 'guard_name' => 'web']);
    }

    // Create role with all role management permissions
    $this->role = Role::create(['name' => 'role-manager', 'guard_name' => 'web']);
    $this->role->givePermissionTo($rolePermissions);
});

// ============================================================================
// INDEX TESTS
// ============================================================================

describe('index', function (): void {
    it('returns paginated list of roles for authorized HQ user', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        // Create additional roles
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'editor', 'guard_name' => 'web']);
        Role::create(['name' => 'viewer', 'guard_name' => 'web']);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/roles');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'guard_name', 'permissions_count', 'users_count', 'created_at'],
                ],
                'links',
                'meta',
            ]);
    });

    it('can search roles by name', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        Role::create(['name' => 'administrator', 'guard_name' => 'web']);
        Role::create(['name' => 'editor', 'guard_name' => 'web']);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/roles?search=admin');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'administrator');
    });

    it('denies access for non-HQ user', function (): void {
        $roleWithPermissions = Role::create(['name' => 'site-role-manager', 'guard_name' => 'web']);
        $roleWithPermissions->givePermissionTo('role.viewAny');

        $user = createUserOnRegularSite($this->regularSite, $roleWithPermissions);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/roles');

        $response->assertForbidden();
    });

    it('denies access for user without viewAny permission', function (): void {
        $roleWithoutViewAny = Role::create(['name' => 'limited', 'guard_name' => 'web']);
        $user = createUserOnHeadquarters($this->headquarters, $roleWithoutViewAny);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/roles');

        $response->assertForbidden();
    });

    it('requires authentication', function (): void {
        $response = $this->getJson('/api/v1/roles');

        $response->assertUnauthorized();
    });
});

// ============================================================================
// SHOW TESTS
// ============================================================================

describe('show', function (): void {
    it('returns role details with permissions for authorized HQ user', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $targetRole = Role::create(['name' => 'test-role', 'guard_name' => 'web']);
        $targetRole->givePermissionTo(['user.viewAny', 'user.view']);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/roles/{$targetRole->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'guard_name',
                    'permissions',
                    'permissions_count',
                    'users_count',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.name', 'test-role')
            ->assertJsonCount(2, 'data.permissions');
    });

    it('returns 404 for non-existent role', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/roles/99999');

        $response->assertNotFound();
    });

    it('denies access for non-HQ user', function (): void {
        $roleWithPermissions = Role::create(['name' => 'site-role-viewer', 'guard_name' => 'web']);
        $roleWithPermissions->givePermissionTo('role.view');

        $user = createUserOnRegularSite($this->regularSite, $roleWithPermissions);

        $targetRole = Role::create(['name' => 'test-role', 'guard_name' => 'web']);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/roles/{$targetRole->id}");

        $response->assertForbidden();
    });
});

// ============================================================================
// STORE TESTS
// ============================================================================

describe('store', function (): void {
    it('creates a new role for authorized HQ user', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/roles', [
                'name' => 'new-role',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'new-role');

        $this->assertDatabaseHas('roles', [
            'name' => 'new-role',
            'guard_name' => 'web',
        ]);
    });

    it('creates role with permissions', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $permissions = Permission::whereIn('name', ['user.viewAny', 'user.view'])->pluck('id')->toArray();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/roles', [
                'name' => 'role-with-permissions',
                'permissions' => $permissions,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'role-with-permissions')
            ->assertJsonCount(2, 'data.permissions');

        $createdRole = Role::where('name', 'role-with-permissions')->first();
        expect($createdRole->permissions()->count())->toBe(2);
    });

    it('validates unique role name', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        Role::create(['name' => 'existing-role', 'guard_name' => 'web']);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/roles', [
                'name' => 'existing-role',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('validates required name', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/roles', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('denies access for non-HQ user', function (): void {
        $roleWithPermissions = Role::create(['name' => 'site-role-creator', 'guard_name' => 'web']);
        $roleWithPermissions->givePermissionTo('role.create');

        $user = createUserOnRegularSite($this->regularSite, $roleWithPermissions);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/roles', [
                'name' => 'new-role',
            ]);

        $response->assertForbidden();
    });

    it('denies access for user without create permission', function (): void {
        $roleWithoutCreate = Role::create(['name' => 'viewer-only', 'guard_name' => 'web']);
        $roleWithoutCreate->givePermissionTo('role.viewAny');

        $user = createUserOnHeadquarters($this->headquarters, $roleWithoutCreate);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/roles', [
                'name' => 'new-role',
            ]);

        $response->assertForbidden();
    });
});

// ============================================================================
// UPDATE TESTS
// ============================================================================

describe('update', function (): void {
    it('updates role name for authorized HQ user', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $targetRole = Role::create(['name' => 'old-name', 'guard_name' => 'web']);

        $response = $this->actingAs($user)
            ->putJson("/api/v1/roles/{$targetRole->id}", [
                'name' => 'new-name',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'new-name');

        $this->assertDatabaseHas('roles', [
            'id' => $targetRole->id,
            'name' => 'new-name',
        ]);
    });

    it('updates role permissions', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $targetRole = Role::create(['name' => 'test-role', 'guard_name' => 'web']);
        $targetRole->givePermissionTo(['user.viewAny']);

        $newPermissions = Permission::whereIn('name', ['material.viewAny', 'material.view'])->pluck('id')->toArray();

        $response = $this->actingAs($user)
            ->putJson("/api/v1/roles/{$targetRole->id}", [
                'permissions' => $newPermissions,
            ]);

        $response->assertOk()
            ->assertJsonCount(2, 'data.permissions');

        $targetRole->refresh();
        expect($targetRole->hasPermissionTo('material.viewAny'))->toBeTrue();
        expect($targetRole->hasPermissionTo('material.view'))->toBeTrue();
        expect($targetRole->hasPermissionTo('user.viewAny'))->toBeFalse();
    });

    it('validates unique name on update', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        Role::create(['name' => 'existing-role', 'guard_name' => 'web']);
        $targetRole = Role::create(['name' => 'target-role', 'guard_name' => 'web']);

        $response = $this->actingAs($user)
            ->putJson("/api/v1/roles/{$targetRole->id}", [
                'name' => 'existing-role',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('allows keeping same name on update', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $targetRole = Role::create(['name' => 'my-role', 'guard_name' => 'web']);

        $response = $this->actingAs($user)
            ->putJson("/api/v1/roles/{$targetRole->id}", [
                'name' => 'my-role',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'my-role');
    });

    it('denies access for non-HQ user', function (): void {
        $roleWithPermissions = Role::create(['name' => 'site-role-updater', 'guard_name' => 'web']);
        $roleWithPermissions->givePermissionTo('role.update');

        $user = createUserOnRegularSite($this->regularSite, $roleWithPermissions);

        $targetRole = Role::create(['name' => 'test-role', 'guard_name' => 'web']);

        $response = $this->actingAs($user)
            ->putJson("/api/v1/roles/{$targetRole->id}", [
                'name' => 'updated-name',
            ]);

        $response->assertForbidden();
    });
});

// ============================================================================
// DESTROY TESTS
// ============================================================================

describe('destroy', function (): void {
    it('deletes role without users for authorized HQ user', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $targetRole = Role::create(['name' => 'to-delete', 'guard_name' => 'web']);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/roles/{$targetRole->id}");

        $response->assertOk();

        $this->assertDatabaseMissing('roles', [
            'id' => $targetRole->id,
        ]);
    });

    it('prevents deletion of role with assigned users', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $targetRole = Role::create(['name' => 'role-with-users', 'guard_name' => 'web']);

        // Assign role to a user
        $anotherUser = User::factory()->withSite($this->headquarters)->create();
        setPermissionsTeamId($this->headquarters->id);
        $anotherUser->assignRole($targetRole);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/roles/{$targetRole->id}");

        $response->assertUnprocessable();

        $this->assertDatabaseHas('roles', [
            'id' => $targetRole->id,
        ]);
    });

    it('denies access for non-HQ user', function (): void {
        $roleWithPermissions = Role::create(['name' => 'site-role-deleter', 'guard_name' => 'web']);
        $roleWithPermissions->givePermissionTo('role.delete');

        $user = createUserOnRegularSite($this->regularSite, $roleWithPermissions);

        $targetRole = Role::create(['name' => 'test-role', 'guard_name' => 'web']);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/roles/{$targetRole->id}");

        $response->assertForbidden();
    });

    it('denies access for user without delete permission', function (): void {
        $roleWithoutDelete = Role::create(['name' => 'viewer-only', 'guard_name' => 'web']);
        $roleWithoutDelete->givePermissionTo(['role.viewAny', 'role.view']);

        $user = createUserOnHeadquarters($this->headquarters, $roleWithoutDelete);

        $targetRole = Role::create(['name' => 'test-role', 'guard_name' => 'web']);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/roles/{$targetRole->id}");

        $response->assertForbidden();
    });
});

// ============================================================================
// AVAILABLE PERMISSIONS TESTS
// ============================================================================

describe('availablePermissions', function (): void {
    it('returns limited list of permissions by default', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/roles/available-permissions');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name'],
                ],
            ]);
    });

    it('can search permissions by name', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/roles/available-permissions?search=user');

        $response->assertOk();

        // All returned permissions should contain 'user' in name
        $permissions = $response->json('data');
        foreach ($permissions as $permission) {
            expect(str_contains(strtolower($permission['name']), 'user'))->toBeTrue();
        }
    });

    it('denies access for non-HQ user', function (): void {
        $roleWithPermissions = Role::create(['name' => 'site-role-viewer', 'guard_name' => 'web']);
        $roleWithPermissions->givePermissionTo('role.viewAny');

        $user = createUserOnRegularSite($this->regularSite, $roleWithPermissions);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/roles/available-permissions');

        $response->assertForbidden();
    });
});

// ============================================================================
// USERS TESTS
// ============================================================================

describe('users', function (): void {
    it('returns users assigned to a role', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $targetRole = Role::create(['name' => 'test-role', 'guard_name' => 'web']);

        // Assign role to users
        $assignedUsers = User::factory()->count(3)->withSite($this->headquarters)->create();
        foreach ($assignedUsers as $assignedUser) {
            setPermissionsTeamId($this->headquarters->id);
            $assignedUser->assignRole($targetRole);
        }

        $response = $this->actingAs($user)
            ->getJson("/api/v1/roles/{$targetRole->id}/users");

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('denies access for non-HQ user', function (): void {
        $roleWithPermissions = Role::create(['name' => 'site-role-viewer', 'guard_name' => 'web']);
        $roleWithPermissions->givePermissionTo('role.view');

        $user = createUserOnRegularSite($this->regularSite, $roleWithPermissions);

        $targetRole = Role::create(['name' => 'test-role', 'guard_name' => 'web']);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/roles/{$targetRole->id}/users");

        $response->assertForbidden();
    });
});
