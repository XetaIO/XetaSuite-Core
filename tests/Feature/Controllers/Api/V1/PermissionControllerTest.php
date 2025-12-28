<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use XetaSuite\Models\Permission;
use XetaSuite\Models\Role;
use XetaSuite\Models\Site;

uses(RefreshDatabase::class);

/*
 |--------------------------------------------------------------------------
 | Permission Controller Test
 |--------------------------------------------------------------------------
 |
 | Tests for the Permission API endpoints.
 | Permissions are headquarters-only resources.
 |
 */

beforeEach(function () {
    // Create headquarters site
    $this->headquarters = Site::factory()->create(['is_headquarters' => true]);

    // Create regular site for testing HQ-only restriction
    $this->regularSite = Site::factory()->create(['is_headquarters' => false]);

    // Create permissions for permission management
    $permissionPermissions = [
        'permission.viewAny',
        'permission.view',
        'permission.create',
        'permission.update',
        'permission.delete',
    ];

    // Create some extra permissions
    $otherPermissions = [
        'user.viewAny',
        'user.view',
        'user.create',
        'material.viewAny',
        'material.view',
        'role.viewAny',
        'role.view',
    ];

    foreach (array_merge($permissionPermissions, $otherPermissions) as $permission) {
        Permission::create(['name' => $permission, 'guard_name' => 'web']);
    }

    // Create role with all permission management permissions
    $this->role = Role::create(['name' => 'permission-manager', 'guard_name' => 'web']);
    $this->role->givePermissionTo($permissionPermissions);
});

// ============================================================================
// INDEX ENDPOINT TESTS
// ============================================================================

describe('index', function () {
    it('returns paginated list of permissions for HQ user', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/permissions');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'guard_name', 'roles_count', 'created_at'],
                ],
                'links',
                'meta',
            ]);
    });

    it('returns permissions ordered by name by default', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/permissions');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name')->toArray();
        $sorted = $names;
        sort($sorted);
        expect($names)->toBe($sorted);
    });

    it('filters permissions by search term', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/permissions?search=role');

        $response->assertOk();
        $data = $response->json('data');
        foreach ($data as $permission) {
            expect(strtolower($permission['name']))->toContain('role');
        }
    });

    it('denies access for regular site user', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/permissions');

        $response->assertForbidden();
    });

    it('denies access for user without viewAny permission', function () {
        $limitedRole = Role::create(['name' => 'limited-role', 'guard_name' => 'web']);
        $user = createUserOnHeadquarters($this->headquarters, $limitedRole);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/permissions');

        $response->assertForbidden();
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/permissions');

        $response->assertUnauthorized();
    });
});

// ============================================================================
// SHOW ENDPOINT TESTS
// ============================================================================

describe('show', function () {
    it('returns permission details for HQ user', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);
        $permission = Permission::where('guard_name', 'web')->first();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/permissions/{$permission->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'guard_name',
                    'roles_count',
                    'roles',
                    'created_at',
                ],
            ]);
    });

    it('includes roles relationship', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);
        $permission = Permission::where('guard_name', 'web')->first();

        // Ensure role has this permission
        $this->role->givePermissionTo($permission);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/permissions/{$permission->id}");

        $response->assertOk();
        expect($response->json('data.roles'))->toBeArray();
    });

    it('denies access for regular site user', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);
        $permission = Permission::where('guard_name', 'web')->first();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/permissions/{$permission->id}");

        $response->assertForbidden();
    });

    it('denies access for user without view permission', function () {
        $limitedRole = Role::create(['name' => 'limited-role-view', 'guard_name' => 'web']);
        $user = createUserOnHeadquarters($this->headquarters, $limitedRole);
        $permission = Permission::where('guard_name', 'web')->first();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/permissions/{$permission->id}");

        $response->assertForbidden();
    });

    it('returns 404 for non-existent permission', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/permissions/99999');

        $response->assertNotFound();
    });

    it('requires authentication', function () {
        $permission = Permission::where('guard_name', 'web')->first();

        $response = $this->getJson("/api/v1/permissions/{$permission->id}");

        $response->assertUnauthorized();
    });
});

// ============================================================================
// AVAILABLE ROLES ENDPOINT TESTS
// ============================================================================

describe('availableRoles', function () {
    it('returns list of available roles for HQ user', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/permissions/available-roles');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name'],
                ],
            ]);
    });

    it('filters roles by search term', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        // Create a specific role to search for
        $testRole = Role::create(['name' => 'Test Admin Role', 'guard_name' => 'web']);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/permissions/available-roles?search=Admin');

        $response->assertOk();
        $roleNames = collect($response->json('data'))->pluck('name')->toArray();
        expect($roleNames)->toContain('Test Admin Role');
    });

    it('denies access for regular site user', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/permissions/available-roles');

        $response->assertForbidden();
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/permissions/available-roles');

        $response->assertUnauthorized();
    });
});

// ============================================================================
// ROLES ENDPOINT TESTS
// ============================================================================

describe('roles', function () {
    it('returns paginated roles for a permission', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);
        $permission = Permission::where('guard_name', 'web')->first();

        // Create roles with this permission
        $roles = collect();
        for ($i = 1; $i <= 3; $i++) {
            $role = Role::create(['name' => "test-role-{$i}", 'guard_name' => 'web']);
            $role->givePermissionTo($permission);
            $roles->push($role);
        }

        $response = $this->actingAs($user)
            ->getJson("/api/v1/permissions/{$permission->id}/roles");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'guard_name', 'users_count', 'created_at'],
                ],
                'links',
                'meta',
            ]);
    });

    it('filters roles by search term', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);
        $permission = Permission::where('guard_name', 'web')->first();

        $specificRole = Role::create(['name' => 'Searchable Role', 'guard_name' => 'web']);
        $specificRole->givePermissionTo($permission);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/permissions/{$permission->id}/roles?search=Searchable");

        $response->assertOk();
        expect($response->json('data.0.name'))->toBe('Searchable Role');
    });

    it('denies access for regular site user', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);
        $permission = Permission::where('guard_name', 'web')->first();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/permissions/{$permission->id}/roles");

        $response->assertForbidden();
    });

    it('requires authentication', function () {
        $permission = Permission::where('guard_name', 'web')->first();

        $response = $this->getJson("/api/v1/permissions/{$permission->id}/roles");

        $response->assertUnauthorized();
    });
});

// ============================================================================
// STORE ENDPOINT TESTS
// ============================================================================

describe('store', function () {
    it('creates a new permission for HQ user with create permission', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/permissions', [
                'name' => 'test.newPermission',
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => ['id', 'name', 'guard_name', 'created_at'],
            ]);

        expect($response->json('data.name'))->toBe('test.newPermission');
        expect(Permission::where('name', 'test.newPermission')->exists())->toBeTrue();
    });

    it('validates required name field', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/permissions', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('validates unique name constraint', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/permissions', [
                'name' => 'permission.viewAny', // Already exists
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('denies access for regular site user', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/permissions', [
                'name' => 'test.permission',
            ]);

        $response->assertForbidden();
    });

    it('denies access for user without create permission', function () {
        $limitedRole = Role::create(['name' => 'limited-role-create', 'guard_name' => 'web']);
        $limitedRole->givePermissionTo(['permission.viewAny', 'permission.view']);
        $user = createUserOnHeadquarters($this->headquarters, $limitedRole);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/permissions', [
                'name' => 'test.permission',
            ]);

        $response->assertForbidden();
    });

    it('requires authentication', function () {
        $response = $this->postJson('/api/v1/permissions', [
            'name' => 'test.permission',
        ]);

        $response->assertUnauthorized();
    });
});

// ============================================================================
// UPDATE ENDPOINT TESTS
// ============================================================================

describe('update', function () {
    it('updates a permission for HQ user with update permission', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);
        $permission = Permission::create(['name' => 'test.updateMe', 'guard_name' => 'web']);

        $response = $this->actingAs($user)
            ->putJson("/api/v1/permissions/{$permission->id}", [
                'name' => 'test.updated',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'test.updated');

        $permission->refresh();
        expect($permission->name)->toBe('test.updated');
    });

    it('validates unique name constraint on update', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);
        $permission = Permission::create(['name' => 'test.original', 'guard_name' => 'web']);

        $response = $this->actingAs($user)
            ->putJson("/api/v1/permissions/{$permission->id}", [
                'name' => 'permission.viewAny', // Already exists
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('allows updating with same name', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);
        $permission = Permission::create(['name' => 'test.sameName', 'guard_name' => 'web']);

        $response = $this->actingAs($user)
            ->putJson("/api/v1/permissions/{$permission->id}", [
                'name' => 'test.sameName',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'test.sameName');
    });

    it('denies access for regular site user', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);
        $permission = Permission::create(['name' => 'test.noUpdate', 'guard_name' => 'web']);

        $response = $this->actingAs($user)
            ->putJson("/api/v1/permissions/{$permission->id}", [
                'name' => 'test.updated',
            ]);

        $response->assertForbidden();
    });

    it('denies access for user without update permission', function () {
        $limitedRole = Role::create(['name' => 'limited-role-update', 'guard_name' => 'web']);
        $limitedRole->givePermissionTo(['permission.viewAny', 'permission.view']);
        $user = createUserOnHeadquarters($this->headquarters, $limitedRole);
        $permission = Permission::create(['name' => 'test.noAccess', 'guard_name' => 'web']);

        $response = $this->actingAs($user)
            ->putJson("/api/v1/permissions/{$permission->id}", [
                'name' => 'test.updated',
            ]);

        $response->assertForbidden();
    });

    it('returns 404 for non-existent permission', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->putJson('/api/v1/permissions/99999', [
                'name' => 'test.notFound',
            ]);

        $response->assertNotFound();
    });

    it('requires authentication', function () {
        $permission = Permission::create(['name' => 'test.noAuth', 'guard_name' => 'web']);

        $response = $this->putJson("/api/v1/permissions/{$permission->id}", [
            'name' => 'test.updated',
        ]);

        $response->assertUnauthorized();
    });
});

// ============================================================================
// DESTROY ENDPOINT TESTS
// ============================================================================

describe('destroy', function () {
    it('deletes a permission for HQ user with delete permission', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);
        $permission = Permission::create(['name' => 'test.deleteMe', 'guard_name' => 'web']);
        $permissionId = $permission->id;

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/permissions/{$permissionId}");

        $response->assertOk()
            ->assertJsonStructure(['message']);

        expect(Permission::find($permissionId))->toBeNull();
    });

    it('cannot delete a permission assigned to roles', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);
        $permission = Permission::create(['name' => 'test.hasRoles', 'guard_name' => 'web']);
        $this->role->givePermissionTo($permission);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/permissions/{$permission->id}");

        $response->assertUnprocessable();
        expect(Permission::find($permission->id))->not->toBeNull();
    });

    it('denies access for regular site user', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);
        $permission = Permission::create(['name' => 'test.noDelete', 'guard_name' => 'web']);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/permissions/{$permission->id}");

        $response->assertForbidden();
    });

    it('denies access for user without delete permission', function () {
        $limitedRole = Role::create(['name' => 'limited-role-delete', 'guard_name' => 'web']);
        $limitedRole->givePermissionTo(['permission.viewAny', 'permission.view']);
        $user = createUserOnHeadquarters($this->headquarters, $limitedRole);
        $permission = Permission::create(['name' => 'test.noDeleteAccess', 'guard_name' => 'web']);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/permissions/{$permission->id}");

        $response->assertForbidden();
    });

    it('returns 404 for non-existent permission', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/permissions/99999');

        $response->assertNotFound();
    });

    it('requires authentication', function () {
        $permission = Permission::create(['name' => 'test.noAuthDelete', 'guard_name' => 'web']);

        $response = $this->deleteJson("/api/v1/permissions/{$permission->id}");

        $response->assertUnauthorized();
    });
});
