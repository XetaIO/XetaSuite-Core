<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use XetaSuite\Enums\Cleanings\CleaningType;
use XetaSuite\Models\Cleaning;
use XetaSuite\Models\Material;
use XetaSuite\Models\Site;
use XetaSuite\Models\Zone;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create headquarters site
    $this->headquarters = Site::factory()->create(['is_headquarters' => true]);

    // Create a regular site
    $this->regularSite = Site::factory()->create(['is_headquarters' => false]);

    // Create another regular site
    $this->otherSite = Site::factory()->create(['is_headquarters' => false]);

    // Create permissions
    $permissions = [
        'cleaning.viewAny',
        'cleaning.view',
        'cleaning.create',
        'cleaning.update',
        'cleaning.delete',
    ];

    foreach ($permissions as $permission) {
        Permission::create(['name' => $permission, 'guard_name' => 'web']);
    }

    // Create role with all permissions
    $this->role = Role::create(['name' => 'cleaning-manager', 'guard_name' => 'web']);
    $this->role->givePermissionTo($permissions);

    // Create zones and materials for the regular site
    $this->zone = Zone::factory()->forSite($this->regularSite)->create([
        'name' => 'Main Zone',
        'allow_material' => true,
    ]);

    $this->material = Material::factory()->forZone($this->zone)->create([
        'name' => 'Test Material',
    ]);

    $this->otherSiteZone = Zone::factory()->forSite($this->otherSite)->create([
        'name' => 'Other Site Zone',
        'allow_material' => true,
    ]);

    $this->otherSiteMaterial = Material::factory()->forZone($this->otherSiteZone)->create([
        'name' => 'Other Site Material',
    ]);
});

// ============================================================================
// INDEX TESTS
// ============================================================================

describe('index', function () {
    it('returns only cleanings for user current site', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        Cleaning::factory()->count(3)->forSite($this->regularSite)->forMaterial($this->material)->createdBy($user)->create();
        Cleaning::factory()->count(2)->forSite($this->otherSite)->forMaterial($this->otherSiteMaterial)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/cleanings');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('returns paginated list with proper structure', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        Cleaning::factory()->count(5)->forSite($this->regularSite)->forMaterial($this->material)->createdBy($user)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/cleanings');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'description',
                        'type',
                        'type_label',
                        'material_id',
                        'material_name',
                        'material',
                        'created_by_id',
                        'created_by_name',
                        'creator',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links',
                'meta',
            ]);
    });

    it('can filter cleanings by type', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        Cleaning::factory()->count(3)->forSite($this->regularSite)->forMaterial($this->material)->createdBy($user)
            ->withType(CleaningType::DAILY)->create();
        Cleaning::factory()->count(2)->forSite($this->regularSite)->forMaterial($this->material)->createdBy($user)
            ->withType(CleaningType::WEEKLY)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/cleanings?type=daily');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('can filter cleanings by material', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $anotherMaterial = Material::factory()->forZone($this->zone)->create();

        Cleaning::factory()->count(3)->forSite($this->regularSite)->forMaterial($this->material)->createdBy($user)->create();
        Cleaning::factory()->count(2)->forSite($this->regularSite)->forMaterial($anotherMaterial)->createdBy($user)->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/cleanings?material_id={$this->material->id}");

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('can search cleanings by description', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        Cleaning::factory()->forSite($this->regularSite)->forMaterial($this->material)->createdBy($user)->create([
            'description' => 'Deep cleaning of the machine',
        ]);
        Cleaning::factory()->forSite($this->regularSite)->forMaterial($this->material)->createdBy($user)->create([
            'description' => 'Regular dusting',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/cleanings?search=machine');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.description', 'Deep cleaning of the machine');
    });

    it('requires cleaning.viewAny permission', function () {
        $roleWithoutViewAny = Role::create(['name' => 'limited-cleaning', 'guard_name' => 'web']);
        $user = createUserOnRegularSite($this->regularSite, $roleWithoutViewAny);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/cleanings');

        $response->assertForbidden();
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/cleanings');

        $response->assertUnauthorized();
    });
});

// ============================================================================
// SHOW TESTS
// ============================================================================

describe('show', function () {
    it('returns cleaning details with proper structure', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $cleaning = Cleaning::factory()->forSite($this->regularSite)->forMaterial($this->material)->createdBy($user)->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/cleanings/{$cleaning->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'description',
                    'type',
                    'type_label',
                    'site_id',
                    'site',
                    'material_id',
                    'material_name',
                    'material',
                    'created_by_id',
                    'created_by_name',
                    'creator',
                    'edited_by_id',
                    'created_at',
                    'updated_at',
                ],
            ]);
    });

    it('cannot view cleaning from another site', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $otherSiteCleaning = Cleaning::factory()->forSite($this->otherSite)->forMaterial($this->otherSiteMaterial)->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/cleanings/{$otherSiteCleaning->id}");

        $response->assertForbidden();
    });

    it('requires cleaning.viewAny permission', function () {
        $roleWithoutView = Role::create(['name' => 'no-view-cleaning', 'guard_name' => 'web']);
        $user = createUserOnRegularSite($this->regularSite, $roleWithoutView);

        $cleaning = Cleaning::factory()->forSite($this->regularSite)->forMaterial($this->material)->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/cleanings/{$cleaning->id}");

        $response->assertForbidden();
    });
});

// ============================================================================
// STORE TESTS
// ============================================================================

describe('store', function () {
    it('can create cleaning with required fields', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/cleanings', [
                'material_id' => $this->material->id,
                'description' => 'Deep cleaning of the machine',
                'type' => 'daily',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.description', 'Deep cleaning of the machine')
            ->assertJsonPath('data.material_id', $this->material->id)
            ->assertJsonPath('data.type', 'daily');

        $this->assertDatabaseHas('cleanings', [
            'material_id' => $this->material->id,
            'description' => 'Deep cleaning of the machine',
            'created_by_id' => $user->id,
            'type' => 'daily',
        ]);
    });

    it('cannot create cleaning for material from another site', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/cleanings', [
                'material_id' => $this->otherSiteMaterial->id,
                'description' => 'Should not be allowed',
                'type' => 'daily',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['material_id']);
    });

    it('validates required fields', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/cleanings', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['material_id', 'description', 'type']);
    });

    it('requires cleaning.create permission', function () {
        $roleWithoutCreate = Role::create(['name' => 'no-create-cleaning', 'guard_name' => 'web']);
        $roleWithoutCreate->givePermissionTo('cleaning.viewAny');
        $user = createUserOnRegularSite($this->regularSite, $roleWithoutCreate);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/cleanings', [
                'material_id' => $this->material->id,
                'description' => 'Test cleaning',
                'type' => 'daily',
            ]);

        $response->assertForbidden();
    });
});

// ============================================================================
// UPDATE TESTS
// ============================================================================

describe('update', function () {
    it('can update cleaning description', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $cleaning = Cleaning::factory()->forSite($this->regularSite)->forMaterial($this->material)->createdBy($user)->create([
            'description' => 'Original description',
        ]);

        $response = $this->actingAs($user)
            ->putJson("/api/v1/cleanings/{$cleaning->id}", [
                'description' => 'Updated description',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.description', 'Updated description')
            ->assertJsonPath('data.edited_by_id', $user->id);

        $this->assertDatabaseHas('cleanings', [
            'id' => $cleaning->id,
            'description' => 'Updated description',
            'edited_by_id' => $user->id,
        ]);
    });

    it('can update cleaning type', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $cleaning = Cleaning::factory()->forSite($this->regularSite)->forMaterial($this->material)->createdBy($user)
            ->withType(CleaningType::DAILY)->create();

        $response = $this->actingAs($user)
            ->putJson("/api/v1/cleanings/{$cleaning->id}", [
                'type' => 'weekly',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.type', 'weekly');
    });

    it('cannot update cleaning from another site', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $otherSiteCleaning = Cleaning::factory()->forSite($this->otherSite)->forMaterial($this->otherSiteMaterial)->create();

        $response = $this->actingAs($user)
            ->putJson("/api/v1/cleanings/{$otherSiteCleaning->id}", [
                'description' => 'Updated',
            ]);

        $response->assertForbidden();
    });

    it('requires cleaning.update permission', function () {
        $roleWithoutUpdate = Role::create(['name' => 'no-update-cleaning', 'guard_name' => 'web']);
        $roleWithoutUpdate->givePermissionTo('cleaning.viewAny');
        $user = createUserOnRegularSite($this->regularSite, $roleWithoutUpdate);

        $cleaning = Cleaning::factory()->forSite($this->regularSite)->forMaterial($this->material)->create();

        $response = $this->actingAs($user)
            ->putJson("/api/v1/cleanings/{$cleaning->id}", [
                'description' => 'Updated',
            ]);

        $response->assertForbidden();
    });
});

// ============================================================================
// DELETE TESTS
// ============================================================================

describe('destroy', function () {
    it('can delete a cleaning', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $cleaning = Cleaning::factory()->forSite($this->regularSite)->forMaterial($this->material)->createdBy($user)->create();

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/cleanings/{$cleaning->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('cleanings', ['id' => $cleaning->id]);
    });

    it('cannot delete cleaning from another site', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $otherSiteCleaning = Cleaning::factory()->forSite($this->otherSite)->forMaterial($this->otherSiteMaterial)->create();

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/cleanings/{$otherSiteCleaning->id}");

        $response->assertForbidden();

        $this->assertDatabaseHas('cleanings', ['id' => $otherSiteCleaning->id]);
    });

    it('requires cleaning.delete permission', function () {
        $roleWithoutDelete = Role::create(['name' => 'no-delete-cleaning', 'guard_name' => 'web']);
        $roleWithoutDelete->givePermissionTo('cleaning.viewAny');
        $user = createUserOnRegularSite($this->regularSite, $roleWithoutDelete);

        $cleaning = Cleaning::factory()->forSite($this->regularSite)->forMaterial($this->material)->create();

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/cleanings/{$cleaning->id}");

        $response->assertForbidden();
    });
});

// ============================================================================
// AVAILABLE MATERIALS TESTS
// ============================================================================

describe('available-materials', function () {
    it('returns materials for current site only', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        Material::factory()->count(3)->forZone($this->zone)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/cleanings/available-materials');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name'],
                ],
            ])
            // 3 created + 1 from beforeEach
            ->assertJsonCount(4, 'data');
    });
});

// ============================================================================
// TYPE OPTIONS TESTS
// ============================================================================

describe('type-options', function () {
    it('returns all cleaning type options', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/cleanings/type-options');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['value', 'label'],
                ],
            ])
            ->assertJsonCount(count(CleaningType::cases()), 'data');
    });
});
