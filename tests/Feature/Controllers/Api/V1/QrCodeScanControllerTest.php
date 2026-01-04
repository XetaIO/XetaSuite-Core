<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use XetaSuite\Models\Item;
use XetaSuite\Models\Material;
use XetaSuite\Models\Site;
use XetaSuite\Models\Zone;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Create headquarters site
    $this->headquarters = Site::factory()->create(['is_headquarters' => true]);

    // Create a regular site
    $this->regularSite = Site::factory()->create(['is_headquarters' => false]);

    // Create another regular site (user has no access)
    $this->otherSite = Site::factory()->create(['is_headquarters' => false]);

    // Create scan permissions
    $scanPermissions = [
        'material.scanQrCode',
        'item.scanQrCode',
    ];

    // Create action permissions
    $actionPermissions = [
        'cleaning.create',
        'maintenance.create',
        'incident.create',
        'item-movement.create',
    ];

    foreach ([...$scanPermissions, ...$actionPermissions] as $permission) {
        Permission::create(['name' => $permission, 'guard_name' => 'web']);
    }

    // Create role with all scan permissions
    $this->scanRole = Role::create(['name' => 'scanner', 'guard_name' => 'web']);
    $this->scanRole->givePermissionTo($scanPermissions);

    // Create role with all permissions (scan + actions)
    $this->fullRole = Role::create(['name' => 'full-access', 'guard_name' => 'web']);
    $this->fullRole->givePermissionTo([...$scanPermissions, ...$actionPermissions]);

    // Create zone for materials
    $this->zone = Zone::factory()->forSite($this->regularSite)->create([
        'name' => 'Test Zone',
        'allow_material' => true,
    ]);

    // Create zone on other site
    $this->otherZone = Zone::factory()->forSite($this->otherSite)->create([
        'name' => 'Other Zone',
        'allow_material' => true,
    ]);
});

// ============================================================================
// MATERIAL SCAN TESTS
// ============================================================================

describe('material scan', function (): void {
    it('returns material information when user has permission and site access', function (): void {
        $user = createUserOnRegularSite($this->regularSite, $this->fullRole);

        $material = Material::factory()->forZone($this->zone)->create([
            'name' => 'Test Material',
            'description' => 'Test Description',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/qr-scan/material/{$material->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'id',
                    'name',
                    'description',
                    'site' => ['id', 'name'],
                    'zone' => ['id', 'name'],
                    'available_actions',
                ],
            ])
            ->assertJsonPath('data.type', 'material')
            ->assertJsonPath('data.name', 'Test Material')
            ->assertJsonPath('data.description', 'Test Description')
            ->assertJsonPath('data.site.id', $this->regularSite->id)
            ->assertJsonPath('data.zone.id', $this->zone->id);
    });

    it('returns available actions based on user permissions', function (): void {
        $user = createUserOnRegularSite($this->regularSite, $this->fullRole);

        $material = Material::factory()->forZone($this->zone)->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/qr-scan/material/{$material->id}");

        $response->assertOk()
            ->assertJsonPath('data.available_actions', ['cleaning', 'maintenance', 'incident']);
    });

    it('returns no actions when user has only scan permission', function (): void {
        $user = createUserOnRegularSite($this->regularSite, $this->scanRole);

        $material = Material::factory()->forZone($this->zone)->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/qr-scan/material/{$material->id}");

        $response->assertOk()
            ->assertJsonPath('data.available_actions', []);
    });

    it('returns 403 when user has no site access', function (): void {
        $user = createUserOnRegularSite($this->regularSite, $this->fullRole);

        // Material on other site
        $material = Material::factory()->forZone($this->otherZone)->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/qr-scan/material/{$material->id}");

        $response->assertForbidden()
            ->assertJsonPath('error', 'no_site_access');
    });

    it('returns 403 when user lacks scan permission', function (): void {
        // Create role without scan permission
        $noScanRole = Role::create(['name' => 'no-scan', 'guard_name' => 'web']);
        $noScanRole->givePermissionTo(['cleaning.create']);

        $user = createUserOnRegularSite($this->regularSite, $noScanRole);

        $material = Material::factory()->forZone($this->zone)->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/qr-scan/material/{$material->id}");

        $response->assertForbidden();
    });

    it('returns 401 when user is not authenticated', function (): void {
        $material = Material::factory()->forZone($this->zone)->create();

        $response = $this->getJson("/api/v1/qr-scan/material/{$material->id}");

        $response->assertUnauthorized();
    });

    it('returns 404 when material does not exist', function (): void {
        $user = createUserOnRegularSite($this->regularSite, $this->fullRole);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/qr-scan/material/99999');

        $response->assertNotFound();
    });
});

// ============================================================================
// ITEM SCAN TESTS
// ============================================================================

describe('item scan', function (): void {
    it('returns item information when user has permission and site access', function (): void {
        $user = createUserOnRegularSite($this->regularSite, $this->fullRole);

        $item = Item::factory()->forSite($this->regularSite)->create([
            'name' => 'Test Item',
            'reference' => 'REF-001',
            'description' => 'Test Item Description',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/qr-scan/item/{$item->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'id',
                    'name',
                    'reference',
                    'description',
                    'current_stock',
                    'site' => ['id', 'name'],
                    'available_actions',
                ],
            ])
            ->assertJsonPath('data.type', 'item')
            ->assertJsonPath('data.name', 'Test Item')
            ->assertJsonPath('data.reference', 'REF-001')
            ->assertJsonPath('data.current_stock', 0)
            ->assertJsonPath('data.site.id', $this->regularSite->id);
    });

    it('returns available actions based on user permissions', function (): void {
        $user = createUserOnRegularSite($this->regularSite, $this->fullRole);

        $item = Item::factory()->forSite($this->regularSite)->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/qr-scan/item/{$item->id}");

        $response->assertOk()
            ->assertJsonPath('data.available_actions', ['entry', 'exit']);
    });

    it('returns no actions when user has only scan permission', function (): void {
        $user = createUserOnRegularSite($this->regularSite, $this->scanRole);

        $item = Item::factory()->forSite($this->regularSite)->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/qr-scan/item/{$item->id}");

        $response->assertOk()
            ->assertJsonPath('data.available_actions', []);
    });

    it('returns 403 when user has no site access', function (): void {
        $user = createUserOnRegularSite($this->regularSite, $this->fullRole);

        // Item on other site
        $item = Item::factory()->forSite($this->otherSite)->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/qr-scan/item/{$item->id}");

        $response->assertForbidden()
            ->assertJsonPath('error', 'no_site_access');
    });

    it('returns 403 when user lacks scan permission', function (): void {
        // Create role without scan permission
        $noScanRole = Role::create(['name' => 'no-item-scan', 'guard_name' => 'web']);
        $noScanRole->givePermissionTo(['item-movement.create']);

        $user = createUserOnRegularSite($this->regularSite, $noScanRole);

        $item = Item::factory()->forSite($this->regularSite)->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/qr-scan/item/{$item->id}");

        $response->assertForbidden();
    });

    it('returns 401 when user is not authenticated', function (): void {
        $item = Item::factory()->forSite($this->regularSite)->create();

        $response = $this->getJson("/api/v1/qr-scan/item/{$item->id}");

        $response->assertUnauthorized();
    });

    it('returns 404 when item does not exist', function (): void {
        $user = createUserOnRegularSite($this->regularSite, $this->fullRole);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/qr-scan/item/99999');

        $response->assertNotFound();
    });
});
