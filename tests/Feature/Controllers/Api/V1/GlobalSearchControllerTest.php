<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use XetaSuite\Models\Company;
use XetaSuite\Models\Incident;
use XetaSuite\Models\Item;
use XetaSuite\Models\Maintenance;
use XetaSuite\Models\Material;
use XetaSuite\Models\Site;
use XetaSuite\Models\Zone;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->headquarters = Site::factory()->headquarters()->create();
    $this->regularSite = Site::factory()->create(['name' => 'Regular Site']);

    // Create all required permissions (using .view instead of .viewAny)
    $permissions = [
        'material.view',
        'zone.view',
        'item.view',
        'incident.view',
        'maintenance.view',
        'company.view',
        'site.view',
    ];

    foreach ($permissions as $permission) {
        Permission::create(['name' => $permission, 'guard_name' => 'web']);
    }

    // Create zones and materials for testing
    $this->zone = Zone::factory()->for($this->regularSite)->create(['name' => 'Test Zone Alpha']);
    $this->material = Material::factory()->for($this->regularSite)->for($this->zone)->create(['name' => 'Material Beta']);

    // Create items
    $this->item = Item::factory()->for($this->regularSite)->create(['name' => 'Item Gamma', 'reference' => 'REF-001']);

    // Create incidents and maintenances
    $this->incident = Incident::factory()->for($this->regularSite)->for($this->material)->create(['description' => 'Incident Delta Problem']);
    $this->maintenance = Maintenance::factory()->for($this->regularSite)->for($this->material)->create(['description' => 'Maintenance Epsilon Work']);

    // Companies (no longer HQ-only)
    $this->company = Company::factory()->create(['name' => 'Company Eta']);
    $this->itemProviderCompany = Company::factory()->asItemProvider()->create(['name' => 'ItemProvider Omega']);

    // Create roles with specific permissions for regular site
    $this->materialRole = Role::create(['name' => 'material-viewer', 'site_id' => $this->regularSite->id]);
    $this->materialRole->givePermissionTo('material.view');

    $this->zoneRole = Role::create(['name' => 'zone-viewer', 'site_id' => $this->regularSite->id]);
    $this->zoneRole->givePermissionTo('zone.view');

    $this->itemRole = Role::create(['name' => 'item-viewer', 'site_id' => $this->regularSite->id]);
    $this->itemRole->givePermissionTo('item.view');

    $this->incidentRole = Role::create(['name' => 'incident-viewer', 'site_id' => $this->regularSite->id]);
    $this->incidentRole->givePermissionTo('incident.view');

    $this->maintenanceRole = Role::create(['name' => 'maintenance-viewer', 'site_id' => $this->regularSite->id]);
    $this->maintenanceRole->givePermissionTo('maintenance.view');

    // Multi-permission role for regular site
    $this->multiRole = Role::create(['name' => 'multi-viewer', 'site_id' => $this->regularSite->id]);
    $this->multiRole->givePermissionTo(['material.view', 'zone.view', 'item.view']);

    // Role for companies on regular site (no longer HQ-only)
    $this->companyRole = Role::create(['name' => 'company-viewer', 'site_id' => $this->regularSite->id]);
    $this->companyRole->givePermissionTo('company.view');

    // HQ roles (sites is still HQ-only)
    $this->hqSiteRole = Role::create(['name' => 'hq-site-viewer', 'site_id' => $this->headquarters->id]);
    $this->hqSiteRole->givePermissionTo('site.view');

    $this->hqMaterialRole = Role::create(['name' => 'hq-material-viewer', 'site_id' => $this->headquarters->id]);
    $this->hqMaterialRole->givePermissionTo('material.view');

    $this->hqMultiRole = Role::create(['name' => 'hq-multi-viewer', 'site_id' => $this->headquarters->id]);
    $this->hqMultiRole->givePermissionTo(['company.view', 'site.view']);
});

describe('GlobalSearchController', function (): void {

    describe('search endpoint', function (): void {

        it('requires authentication', function (): void {
            $response = $this->getJson('/api/v1/search?q=test');

            $response->assertUnauthorized();
        });

        it('requires minimum 2 characters', function (): void {
            $user = createUserOnRegularSite($this->regularSite, $this->materialRole);

            $response = $this->actingAs($user)->getJson('/api/v1/search?q=a');

            $response->assertUnprocessable();
        });

        it('returns empty results for short queries', function (): void {
            $user = createUserOnRegularSite($this->regularSite, $this->materialRole);

            $response = $this->actingAs($user)->getJson('/api/v1/search?q=ab');

            $response->assertSuccessful()
                ->assertJsonStructure(['results', 'total', 'query']);
        });

        it('searches materials when user has permission', function (): void {
            $user = createUserOnRegularSite($this->regularSite, $this->materialRole);

            $response = $this->actingAs($user)->getJson('/api/v1/search?q=Beta');

            $response->assertSuccessful()
                ->assertJsonPath('results.materials.count', 1)
                ->assertJsonPath('results.materials.items.0.title', 'Material Beta');
        });

        it('does not return materials when user lacks permission', function (): void {
            $user = createUserOnRegularSite($this->regularSite, $this->zoneRole);

            $response = $this->actingAs($user)->getJson('/api/v1/search?q=Beta');

            $response->assertSuccessful();
            expect($response->json('results'))->not->toHaveKey('materials');
        });

        it('searches zones when user has permission', function (): void {
            $user = createUserOnRegularSite($this->regularSite, $this->zoneRole);

            $response = $this->actingAs($user)->getJson('/api/v1/search?q=Alpha');

            $response->assertSuccessful()
                ->assertJsonPath('results.zones.count', 1)
                ->assertJsonPath('results.zones.items.0.title', 'Test Zone Alpha');
        });

        it('searches items when user has permission', function (): void {
            $user = createUserOnRegularSite($this->regularSite, $this->itemRole);

            $response = $this->actingAs($user)->getJson('/api/v1/search?q=Gamma');

            $response->assertSuccessful()
                ->assertJsonPath('results.items.count', 1)
                ->assertJsonPath('results.items.items.0.title', 'Item Gamma');
        });

        it('searches incidents when user has permission', function (): void {
            $user = createUserOnRegularSite($this->regularSite, $this->incidentRole);

            $response = $this->actingAs($user)->getJson('/api/v1/search?q=Delta');

            $response->assertSuccessful()
                ->assertJsonPath('results.incidents.count', 1);
        });

        it('searches maintenances when user has permission', function (): void {
            $user = createUserOnRegularSite($this->regularSite, $this->maintenanceRole);

            $response = $this->actingAs($user)->getJson('/api/v1/search?q=Epsilon');

            $response->assertSuccessful()
                ->assertJsonPath('results.maintenances.count', 1);
        });

        it('returns companies for regular site users with permission', function (): void {
            $user = createUserOnRegularSite($this->regularSite, $this->companyRole);

            $response = $this->actingAs($user)->getJson('/api/v1/search?q=Eta');

            $response->assertSuccessful()
                ->assertJsonPath('results.companies.count', 1)
                ->assertJsonPath('results.companies.items.0.title', 'Company Eta');
        });

        it('does not return companies when user lacks permission', function (): void {
            $user = createUserOnRegularSite($this->regularSite, $this->materialRole);

            $response = $this->actingAs($user)->getJson('/api/v1/search?q=Eta');

            $response->assertSuccessful();
            expect($response->json('results'))->not->toHaveKey('companies');
        });

        it('does not return sites for regular site users', function (): void {
            $siteRoleRegular = Role::create(['name' => 'site-viewer-regular', 'site_id' => $this->regularSite->id]);
            $siteRoleRegular->givePermissionTo('site.view');
            $user = createUserOnRegularSite($this->regularSite, $siteRoleRegular);

            $response = $this->actingAs($user)->getJson('/api/v1/search?q=Regular');

            $response->assertSuccessful();
            expect($response->json('results'))->not->toHaveKey('sites');
        });

        it('returns sites for HQ users with permission', function (): void {
            $user = createUserOnHeadquarters($this->headquarters, $this->hqSiteRole);

            $response = $this->actingAs($user)->getJson('/api/v1/search?q=Regular');

            $response->assertSuccessful()
                ->assertJsonPath('results.sites.count', 1)
                ->assertJsonPath('results.sites.items.0.title', 'Regular Site');
        });

        it('scopes results to current site for regular users', function (): void {
            // Create another site with a material
            $otherSite = Site::factory()->create();
            $otherZone = Zone::factory()->for($otherSite)->create();
            Material::factory()->for($otherSite)->for($otherZone)->create(['name' => 'Material Beta Other']);

            $user = createUserOnRegularSite($this->regularSite, $this->materialRole);

            $response = $this->actingAs($user)->getJson('/api/v1/search?q=Beta');

            $response->assertSuccessful()
                ->assertJsonPath('results.materials.count', 1)
                ->assertJsonPath('results.materials.items.0.title', 'Material Beta');
        });

        it('returns results from all sites for HQ users', function (): void {
            $user = createUserOnHeadquarters($this->headquarters, $this->hqMaterialRole);

            $response = $this->actingAs($user)->getJson('/api/v1/search?q=Beta');

            $response->assertSuccessful()
                ->assertJsonPath('results.materials.count', 1);
        });

        it('respects per_type limit', function (): void {
            // Create multiple materials
            for ($i = 0; $i < 10; $i++) {
                Material::factory()->for($this->regularSite)->for($this->zone)->create(['name' => "Search Material {$i}"]);
            }

            $user = createUserOnRegularSite($this->regularSite, $this->materialRole);

            $response = $this->actingAs($user)->getJson('/api/v1/search?q=Search&per_type=3');

            $response->assertSuccessful()
                ->assertJsonPath('results.materials.count', 3)
                ->assertJsonPath('results.materials.has_more', true);
        });

        it('searches across multiple types simultaneously', function (): void {
            // Create searchable items with common term
            Material::factory()->for($this->regularSite)->for($this->zone)->create(['name' => 'Common Search Term']);
            Zone::factory()->for($this->regularSite)->create(['name' => 'Common Search Zone']);
            Item::factory()->for($this->regularSite)->create(['name' => 'Common Search Item']);

            $user = createUserOnRegularSite($this->regularSite, $this->multiRole);

            $response = $this->actingAs($user)->getJson('/api/v1/search?q=Common');

            $response->assertSuccessful();

            $results = $response->json('results');
            expect($results)->toHaveKeys(['materials', 'zones', 'items']);
        });

    });

    describe('types endpoint', function (): void {

        it('requires authentication', function (): void {
            $response = $this->getJson('/api/v1/search/types');

            $response->assertUnauthorized();
        });

        it('returns only permitted types for regular site user', function (): void {
            $roleWithTwo = Role::create(['name' => 'two-perms', 'site_id' => $this->regularSite->id]);
            $roleWithTwo->givePermissionTo(['material.view', 'zone.view']);
            $user = createUserOnRegularSite($this->regularSite, $roleWithTwo);

            $response = $this->actingAs($user)->getJson('/api/v1/search/types');

            $response->assertSuccessful()
                ->assertJsonPath('types', ['materials', 'zones']);
        });

        it('excludes HQ-only types for regular site users', function (): void {
            $roleWithMixed = Role::create(['name' => 'mixed-perms', 'site_id' => $this->regularSite->id]);
            $roleWithMixed->givePermissionTo(['material.view', 'company.view', 'site.view']);
            $user = createUserOnRegularSite($this->regularSite, $roleWithMixed);

            $response = $this->actingAs($user)->getJson('/api/v1/search/types');

            $response->assertSuccessful();

            $types = $response->json('types');
            // Only sites is HQ-only, companies are accessible
            expect($types)->not->toContain('sites')
                ->toContain('companies');
        });

        it('includes HQ-only types for HQ users with permissions', function (): void {
            $user = createUserOnHeadquarters($this->headquarters, $this->hqMultiRole);

            $response = $this->actingAs($user)->getJson('/api/v1/search/types');

            $response->assertSuccessful();

            $types = $response->json('types');
            expect($types)->toContain('companies')
                ->toContain('sites');
        });

    });

});
