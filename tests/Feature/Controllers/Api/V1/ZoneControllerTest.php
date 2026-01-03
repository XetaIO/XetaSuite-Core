<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
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
        'zone.viewAny',
        'zone.view',
        'zone.create',
        'zone.update',
        'zone.delete',
    ];

    foreach ($permissions as $permission) {
        Permission::create(['name' => $permission, 'guard_name' => 'web']);
    }

    // Create role with all permissions
    $this->role = Role::create(['name' => 'zone-manager', 'guard_name' => 'web']);
    $this->role->givePermissionTo($permissions);
});

// ============================================================================
// INDEX TESTS
// ============================================================================

describe('index', function () {
    it('returns only zones for the user current site', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        Zone::factory()->count(3)->forSite($this->regularSite)->create();
        Zone::factory()->count(2)->forSite($this->otherSite)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/zones');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('returns paginated list with proper structure', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        Zone::factory()->count(5)->forSite($this->regularSite)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/zones');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'allow_material', 'parent_id', 'site_id', 'created_at'],
                ],
                'links',
                'meta',
            ]);
    });

    it('returns zones ordered by name', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        Zone::factory()->forSite($this->regularSite)->create(['name' => 'Zebra Zone']);
        Zone::factory()->forSite($this->regularSite)->create(['name' => 'Alpha Zone']);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/zones');

        $response->assertOk();
        $data = $response->json('data');
        expect($data[0]['name'])->toBe('Alpha Zone');
        expect($data[1]['name'])->toBe('Zebra Zone');
    });

    it('includes children_count and material_count in response', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $parentZone = Zone::factory()->forSite($this->regularSite)->create(['allow_material' => false]);
        Zone::factory()->count(3)->forSite($this->regularSite)->create(['parent_id' => $parentZone->id]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/zones');

        $response->assertOk();
        $parentData = collect($response->json('data'))->firstWhere('id', $parentZone->id);
        expect($parentData['children_count'])->toBe(3);
    });

    it('filters zones by search term', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        Zone::factory()->forSite($this->regularSite)->create(['name' => 'Main Building']);
        Zone::factory()->forSite($this->regularSite)->create(['name' => 'Storage Area']);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/zones?search=Building');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name');
        expect($names)->toContain('Main Building');
        expect($names)->not->toContain('Storage Area');
    });

    it('ignores site_id filter and always uses current user site', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        Zone::factory()->forSite($this->regularSite)->create(['name' => 'My Zone']);
        Zone::factory()->forSite($this->otherSite)->create(['name' => 'Other Zone']);

        // Try to access another site's zones - should be ignored
        $response = $this->actingAs($user)
            ->getJson("/api/v1/zones?site_id={$this->otherSite->id}");

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name');
        expect($names)->toContain('My Zone');
        expect($names)->not->toContain('Other Zone');
    });

    it('denies access for user without viewAny permission', function () {
        $roleWithoutViewAny = Role::create(['name' => 'limited-zone', 'guard_name' => 'web']);
        $user = createUserOnRegularSite($this->regularSite, $roleWithoutViewAny);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/zones');

        $response->assertForbidden();
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/zones');

        $response->assertUnauthorized();
    });
});

// ============================================================================
// SHOW TESTS
// ============================================================================

describe('show', function () {
    it('returns zone details for zone on user current site', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $zone = Zone::factory()->forSite($this->regularSite)->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/zones/{$zone->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'name', 'allow_material', 'parent_id', 'site_id', 'site', 'children_count', 'material_count', 'created_at', 'updated_at'],
            ])
            ->assertJsonPath('data.id', $zone->id)
            ->assertJsonPath('data.name', $zone->name);
    });

    it('includes children for parent zones', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $parentZone = Zone::factory()->forSite($this->regularSite)->create(['allow_material' => false]);
        $childZone = Zone::factory()->forSite($this->regularSite)->create([
            'parent_id' => $parentZone->id,
            'name' => 'Child Zone',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/zones/{$parentZone->id}");

        $response->assertOk()
            ->assertJsonPath('data.children.0.id', $childZone->id)
            ->assertJsonPath('data.children.0.name', 'Child Zone');
    });

    it('includes materials for zones that allow materials', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $zone = Zone::factory()->forSite($this->regularSite)->create(['allow_material' => true]);
        $material = Material::factory()->forZone($zone)->create(['name' => 'Test Material']);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/zones/{$zone->id}");

        $response->assertOk()
            ->assertJsonPath('data.materials.0.id', $material->id)
            ->assertJsonPath('data.materials.0.name', 'Test Material');
    });

    it('denies access for zone on different site', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);
        $zone = Zone::factory()->forSite($this->otherSite)->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/zones/{$zone->id}");

        $response->assertForbidden();
    });

    it('denies access for user without view permission', function () {
        $roleWithoutView = Role::create(['name' => 'no-zone-view', 'guard_name' => 'web']);
        $user = createUserOnRegularSite($this->regularSite, $roleWithoutView);
        $zone = Zone::factory()->forSite($this->regularSite)->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/zones/{$zone->id}");

        $response->assertForbidden();
    });

    it('returns 404 for non-existent zone', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/zones/99999');

        $response->assertNotFound();
    });
});

// ============================================================================
// STORE TESTS
// ============================================================================

describe('store', function () {
    it('creates a new zone for user current site', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/zones', [
                'name' => 'New Zone',
                'allow_material' => true,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'New Zone')
            ->assertJsonPath('data.allow_material', true)
            ->assertJsonPath('data.site_id', $this->regularSite->id);

        $this->assertDatabaseHas('zones', [
            'name' => 'New Zone',
            'site_id' => $this->regularSite->id,
        ]);
    });

    it('ignores provided site_id and uses user current site', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/zones', [
                'site_id' => $this->otherSite->id, // Try to create on another site
                'name' => 'New Zone',
                'allow_material' => true,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.site_id', $this->regularSite->id); // Should be current site

        $this->assertDatabaseHas('zones', [
            'name' => 'New Zone',
            'site_id' => $this->regularSite->id,
        ]);
    });

    it('creates a zone with parent', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $parentZone = Zone::factory()->forSite($this->regularSite)->create(['allow_material' => false]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/zones', [
                'name' => 'Child Zone',
                'parent_id' => $parentZone->id,
                'allow_material' => true,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.parent_id', $parentZone->id);

        $this->assertDatabaseHas('zones', [
            'name' => 'Child Zone',
            'parent_id' => $parentZone->id,
        ]);
    });

    it('validates required fields', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/zones', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('validates parent zone exists', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/zones', [
                'name' => 'Test Zone',
                'parent_id' => 99999,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['parent_id']);
    });

    it('denies access for user without create permission', function () {
        $roleWithoutCreate = Role::create(['name' => 'no-zone-create', 'guard_name' => 'web']);
        $roleWithoutCreate->givePermissionTo('zone.viewAny');
        $user = createUserOnRegularSite($this->regularSite, $roleWithoutCreate);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/zones', [
                'name' => 'New Zone',
            ]);

        $response->assertForbidden();
    });
});

// ============================================================================
// UPDATE TESTS
// ============================================================================

describe('update', function () {
    it('updates a zone on user current site', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $zone = Zone::factory()->forSite($this->regularSite)->create(['name' => 'Old Name']);

        $response = $this->actingAs($user)
            ->putJson("/api/v1/zones/{$zone->id}", [
                'name' => 'New Name',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'New Name');

        $this->assertDatabaseHas('zones', [
            'id' => $zone->id,
            'name' => 'New Name',
        ]);
    });

    it('updates zone allow_material flag', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $zone = Zone::factory()->forSite($this->regularSite)->create(['allow_material' => false]);

        $response = $this->actingAs($user)
            ->putJson("/api/v1/zones/{$zone->id}", [
                'allow_material' => true,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.allow_material', true);
    });

    it('updates zone parent', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $newParent = Zone::factory()->forSite($this->regularSite)->create(['allow_material' => false]);
        $zone = Zone::factory()->forSite($this->regularSite)->create();

        $response = $this->actingAs($user)
            ->putJson("/api/v1/zones/{$zone->id}", [
                'parent_id' => $newParent->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.parent_id', $newParent->id);
    });

    it('validates parent zone belongs to same site', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $otherSiteParent = Zone::factory()->forSite($this->otherSite)->create(['allow_material' => false]);
        $zone = Zone::factory()->forSite($this->regularSite)->create();

        $response = $this->actingAs($user)
            ->putJson("/api/v1/zones/{$zone->id}", [
                'parent_id' => $otherSiteParent->id,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['parent_id']);
    });

    it('denies access for zone on different site', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);
        $zone = Zone::factory()->forSite($this->otherSite)->create();

        $response = $this->actingAs($user)
            ->putJson("/api/v1/zones/{$zone->id}", [
                'name' => 'Updated Name',
            ]);

        $response->assertForbidden();
    });

    it('denies access for user without update permission', function () {
        $roleWithoutUpdate = Role::create(['name' => 'no-zone-update', 'guard_name' => 'web']);
        $roleWithoutUpdate->givePermissionTo('zone.viewAny');
        $user = createUserOnRegularSite($this->regularSite, $roleWithoutUpdate);
        $zone = Zone::factory()->forSite($this->regularSite)->create();

        $response = $this->actingAs($user)
            ->putJson("/api/v1/zones/{$zone->id}", [
                'name' => 'Updated Name',
            ]);

        $response->assertForbidden();
    });
});

// ============================================================================
// DESTROY TESTS
// ============================================================================

describe('destroy', function () {
    it('deletes a zone without children or materials', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $zone = Zone::factory()->forSite($this->regularSite)->create();

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/zones/{$zone->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('zones', [
            'id' => $zone->id,
        ]);
    });

    it('prevents deleting zone with children', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $parentZone = Zone::factory()->forSite($this->regularSite)->create();
        Zone::factory()->forSite($this->regularSite)->create(['parent_id' => $parentZone->id]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/zones/{$parentZone->id}");

        $response->assertUnprocessable();

        $this->assertDatabaseHas('zones', [
            'id' => $parentZone->id,
        ]);
    });

    it('prevents deleting zone with materials', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $zone = Zone::factory()->forSite($this->regularSite)->create(['allow_material' => true]);
        Material::factory()->forZone($zone)->create();

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/zones/{$zone->id}");

        $response->assertUnprocessable();

        $this->assertDatabaseHas('zones', [
            'id' => $zone->id,
        ]);
    });

    it('denies access for zone on different site', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);
        $zone = Zone::factory()->forSite($this->otherSite)->create();

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/zones/{$zone->id}");

        $response->assertForbidden();
    });

    it('denies access for user without delete permission', function () {
        $roleWithoutDelete = Role::create(['name' => 'no-zone-delete', 'guard_name' => 'web']);
        $roleWithoutDelete->givePermissionTo('zone.viewAny');
        $user = createUserOnRegularSite($this->regularSite, $roleWithoutDelete);
        $zone = Zone::factory()->forSite($this->regularSite)->create();

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/zones/{$zone->id}");

        $response->assertForbidden();
    });
});

// ============================================================================
// CHILDREN TESTS
// ============================================================================

describe('children', function () {
    it('returns children for a zone on user current site', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $parentZone = Zone::factory()->forSite($this->regularSite)->create(['allow_material' => false]);
        $child1 = Zone::factory()->forSite($this->regularSite)->create(['parent_id' => $parentZone->id, 'name' => 'Child 1']);
        $child2 = Zone::factory()->forSite($this->regularSite)->create(['parent_id' => $parentZone->id, 'name' => 'Child 2']);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/zones/{$parentZone->id}/children");

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name');
        expect($names)->toContain('Child 1');
        expect($names)->toContain('Child 2');
    });

    it('returns empty array for zone without children', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $zone = Zone::factory()->forSite($this->regularSite)->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/zones/{$zone->id}/children");

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    });

    it('denies access for zone on different site', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);
        $zone = Zone::factory()->forSite($this->otherSite)->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/zones/{$zone->id}/children");

        $response->assertForbidden();
    });
});

// ============================================================================
// MATERIALS TESTS
// ============================================================================

describe('materials', function () {
    it('returns materials for a zone that allows materials', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $zone = Zone::factory()->forSite($this->regularSite)->create(['allow_material' => true]);
        $material = Material::factory()->forZone($zone)->create(['name' => 'Test Material']);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/zones/{$zone->id}/materials");

        $response->assertOk()
            ->assertJsonPath('data.0.id', $material->id)
            ->assertJsonPath('data.0.name', 'Test Material');
    });

    it('returns error for zone that does not allow materials', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $zone = Zone::factory()->forSite($this->regularSite)->create(['allow_material' => false]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/zones/{$zone->id}/materials");

        $response->assertUnprocessable();
    });

    it('denies access for zone on different site', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);
        $zone = Zone::factory()->forSite($this->otherSite)->create(['allow_material' => true]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/zones/{$zone->id}/materials");

        $response->assertForbidden();
    });
});

// ============================================================================
// AVAILABLE PARENTS TESTS
// ============================================================================

describe('availableParents', function () {
    it('returns available parent zones for user current site', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $parentZone = Zone::factory()->forSite($this->regularSite)->create(['allow_material' => false, 'name' => 'Parent Zone']);
        Zone::factory()->forSite($this->regularSite)->create(['allow_material' => true, 'name' => 'Material Zone']);
        Zone::factory()->forSite($this->otherSite)->create(['allow_material' => false, 'name' => 'Other Site Zone']);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/zones/available-parents');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name');
        expect($names)->toContain('Parent Zone');
        expect($names)->not->toContain('Material Zone');
        expect($names)->not->toContain('Other Site Zone');
    });

    it('excludes a zone and its descendants when exclude_zone_id is provided', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $zone1 = Zone::factory()->forSite($this->regularSite)->create(['allow_material' => false, 'name' => 'Zone 1']);
        $zone2 = Zone::factory()->forSite($this->regularSite)->create(['allow_material' => false, 'name' => 'Zone 2', 'parent_id' => $zone1->id]);
        $zone3 = Zone::factory()->forSite($this->regularSite)->create(['allow_material' => false, 'name' => 'Zone 3']);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/zones/available-parents?exclude_zone_id={$zone1->id}");

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name');
        expect($names)->not->toContain('Zone 1');
        expect($names)->not->toContain('Zone 2');
        expect($names)->toContain('Zone 3');
    });
});

// ============================================================================
// TREE TESTS
// ============================================================================

describe('tree', function () {
    it('returns hierarchical zone tree for user current site', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        // Create hierarchical zones
        $rootZone = Zone::factory()->forSite($this->regularSite)->create(['name' => 'Root Zone']);
        $childZone = Zone::factory()->forSite($this->regularSite)->create([
            'name' => 'Child Zone',
            'parent_id' => $rootZone->id,
        ]);
        $grandchildZone = Zone::factory()->forSite($this->regularSite)->create([
            'name' => 'Grandchild Zone',
            'parent_id' => $childZone->id,
        ]);

        // Create zone on another site (should not appear)
        Zone::factory()->forSite($this->otherSite)->create(['name' => 'Other Site Zone']);

        $response = $this->actingAs($user)->getJson('/api/v1/zones/tree');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'allow_material',
                    'children_count',
                    'material_count',
                    'children',
                ],
            ],
            'meta' => [
                'site_id',
                'total_zones',
            ],
        ]);

        // Only root zones should be at top level
        $topLevelNames = collect($response->json('data'))->pluck('name');
        expect($topLevelNames)->toContain('Root Zone');
        expect($topLevelNames)->not->toContain('Child Zone');
        expect($topLevelNames)->not->toContain('Other Site Zone');

        // Check total count
        expect($response->json('meta.total_zones'))->toBe(3);
    });

    it('returns nested children recursively', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $rootZone = Zone::factory()->forSite($this->regularSite)->create(['name' => 'Root']);
        $childZone = Zone::factory()->forSite($this->regularSite)->create([
            'name' => 'Child',
            'parent_id' => $rootZone->id,
        ]);
        Zone::factory()->forSite($this->regularSite)->create([
            'name' => 'Grandchild',
            'parent_id' => $childZone->id,
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/zones/tree');

        $response->assertOk();

        $data = $response->json('data');
        expect($data)->toHaveCount(1);
        expect($data[0]['name'])->toBe('Root');
        expect($data[0]['children'])->toHaveCount(1);
        expect($data[0]['children'][0]['name'])->toBe('Child');
        expect($data[0]['children'][0]['children'])->toHaveCount(1);
        expect($data[0]['children'][0]['children'][0]['name'])->toBe('Grandchild');
    });

    it('allows HQ users to view zones for any site', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        // Create zones on regular site
        Zone::factory()->forSite($this->regularSite)->create(['name' => 'Regular Site Zone']);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/zones/tree?site_id={$this->regularSite->id}");

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name');
        expect($names)->toContain('Regular Site Zone');
        expect($response->json('meta.site_id'))->toBe($this->regularSite->id);
    });

    it('ignores site_id parameter for non-HQ users', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        // Create zones on both sites
        Zone::factory()->forSite($this->regularSite)->create(['name' => 'My Site Zone']);
        Zone::factory()->forSite($this->otherSite)->create(['name' => 'Other Site Zone']);

        // Try to access other site's zones
        $response = $this->actingAs($user)
            ->getJson("/api/v1/zones/tree?site_id={$this->otherSite->id}");

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name');
        // Should only see current site's zones
        expect($names)->toContain('My Site Zone');
        expect($names)->not->toContain('Other Site Zone');
    });

    it('denies access for user without viewAny permission', function () {
        $roleWithoutPermission = Role::create(['name' => 'no-zone-access', 'guard_name' => 'web']);
        $user = createUserOnRegularSite($this->regularSite, $roleWithoutPermission);

        $response = $this->actingAs($user)->getJson('/api/v1/zones/tree');

        $response->assertForbidden();
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/zones/tree');

        $response->assertUnauthorized();
    });
});
