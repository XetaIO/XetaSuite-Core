<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use XetaSuite\Enums\Incidents\IncidentSeverity;
use XetaSuite\Enums\Incidents\IncidentStatus;
use XetaSuite\Models\Incident;
use XetaSuite\Models\Maintenance;
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
        'incident.viewAny',
        'incident.view',
        'incident.create',
        'incident.update',
        'incident.delete',
    ];

    foreach ($permissions as $permission) {
        Permission::create(['name' => $permission, 'guard_name' => 'web']);
    }

    // Create role with all permissions
    $this->role = Role::create(['name' => 'incident-manager', 'guard_name' => 'web']);
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
    it('returns only incidents for user current site', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        Incident::factory()->count(3)->forSite($this->material->site_id)->forMaterial($this->material)->reportedBy($user)->create();
        Incident::factory()->count(2)->forSite($this->otherSiteMaterial->site_id)->forMaterial($this->otherSiteMaterial)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/incidents');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('returns paginated list with proper structure', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        Incident::factory()->count(5)->forSite($this->material->site_id)->forMaterial($this->material)->reportedBy($user)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/incidents');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'description',
                        'status',
                        'status_label',
                        'severity',
                        'severity_label',
                        'material_id',
                        'material_name',
                        'material',
                        'maintenance_id',
                        'reported_by_id',
                        'reported_by_name',
                        'started_at',
                        'resolved_at',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links',
                'meta',
            ]);
    });

    it('can filter incidents by status', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        Incident::factory()->count(3)->forSite($this->material->site_id)->forMaterial($this->material)->reportedBy($user)
            ->withStatus(IncidentStatus::OPEN)->create();
        Incident::factory()->count(2)->forSite($this->material->site_id)->forMaterial($this->material)->reportedBy($user)
            ->withStatus(IncidentStatus::RESOLVED)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/incidents?status=open');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('can filter incidents by severity', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        Incident::factory()->count(2)->forSite($this->material->site_id)->forMaterial($this->material)->reportedBy($user)
            ->withSeverity(IncidentSeverity::CRITICAL)->create();
        Incident::factory()->count(3)->forSite($this->material->site_id)->forMaterial($this->material)->reportedBy($user)
            ->withSeverity(IncidentSeverity::LOW)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/incidents?severity=critical');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('can filter incidents by material', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $anotherMaterial = Material::factory()->forZone($this->zone)->create();

        Incident::factory()->count(3)->forSite($this->material->site_id)->forMaterial($this->material)->reportedBy($user)->create();
        Incident::factory()->count(2)->forSite($anotherMaterial->site_id)->forMaterial($anotherMaterial)->reportedBy($user)->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/incidents?material_id={$this->material->id}");

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('can search incidents by description', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        Incident::factory()->forSite($this->material->site_id)->forMaterial($this->material)->reportedBy($user)->create([
            'description' => 'The machine is broken',
        ]);
        Incident::factory()->forSite($this->material->site_id)->forMaterial($this->material)->reportedBy($user)->create([
            'description' => 'Regular maintenance needed',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/incidents?search=broken');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.description', 'The machine is broken');
    });

    it('requires incident.viewAny permission', function () {
        $roleWithoutViewAny = Role::create(['name' => 'limited-incident', 'guard_name' => 'web']);
        $user = createUserOnRegularSite($this->regularSite, $roleWithoutViewAny);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/incidents');

        $response->assertForbidden();
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/incidents');

        $response->assertUnauthorized();
    });
});

// ============================================================================
// SHOW TESTS
// ============================================================================

describe('show', function () {
    it('returns incident details with proper structure', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $incident = Incident::factory()->forSite($this->material->site_id)->forMaterial($this->material)->reportedBy($user)->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/incidents/{$incident->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'description',
                    'status',
                    'status_label',
                    'severity',
                    'severity_label',
                    'site_id',
                    'site',
                    'material_id',
                    'material_name',
                    'material',
                    'maintenance_id',
                    'maintenance',
                    'reported_by_id',
                    'reported_by_name',
                    'reporter',
                    'edited_by_id',
                    'editor',
                    'started_at',
                    'resolved_at',
                    'created_at',
                    'updated_at',
                ],
            ]);
    });

    it('cannot view incident from another site', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $otherSiteIncident = Incident::factory()->forSite($this->otherSiteMaterial->site_id)->forMaterial($this->otherSiteMaterial)->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/incidents/{$otherSiteIncident->id}");

        $response->assertForbidden();
    });

    it('requires incident.viewAny permission', function () {
        $roleWithoutView = Role::create(['name' => 'no-view-incident', 'guard_name' => 'web']);
        $user = createUserOnRegularSite($this->regularSite, $roleWithoutView);

        $incident = Incident::factory()->forSite($this->material->site_id)->forMaterial($this->material)->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/incidents/{$incident->id}");

        $response->assertForbidden();
    });
});

// ============================================================================
// STORE TESTS
// ============================================================================

describe('store', function () {
    it('can create incident with required fields', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/incidents', [
                'material_id' => $this->material->id,
                'description' => 'Machine is making unusual noises',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.description', 'Machine is making unusual noises')
            ->assertJsonPath('data.material_id', $this->material->id)
            ->assertJsonPath('data.status', 'open');

        $this->assertDatabaseHas('incidents', [
            'material_id' => $this->material->id,
            'description' => 'Machine is making unusual noises',
            'reported_by_id' => $user->id,
        ]);
    });

    it('can create incident with all fields', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/incidents', [
                'material_id' => $this->material->id,
                'description' => 'Critical failure detected',
                'severity' => 'critical',
                'started_at' => '2024-01-15 10:30:00',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.severity', 'critical')
            ->assertJsonPath('data.status', 'open');
    });

    it('can create incident with maintenance', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $maintenance = Maintenance::factory()->forSite($this->material->site_id)->forMaterial($this->material)->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/incidents', [
                'material_id' => $this->material->id,
                'maintenance_id' => $maintenance->id,
                'description' => 'Incident during maintenance',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.maintenance_id', $maintenance->id);
    });

    it('can create resolved incident', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/incidents', [
                'material_id' => $this->material->id,
                'description' => 'Issue was quickly resolved',
                'started_at' => '2024-01-15 10:00:00',
                'resolved_at' => '2024-01-15 11:00:00',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'resolved');
    });

    it('validates required fields', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/incidents', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['material_id', 'description']);
    });

    it('validates material belongs to user site', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/incidents', [
                'material_id' => $this->otherSiteMaterial->id,
                'description' => 'Test incident',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['material_id']);
    });

    it('validates maintenance belongs to user site', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $otherMaintenance = Maintenance::factory()->forSite($this->otherSiteMaterial->site_id)->forMaterial($this->otherSiteMaterial)->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/incidents', [
                'material_id' => $this->material->id,
                'maintenance_id' => $otherMaintenance->id,
                'description' => 'Test incident',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['maintenance_id']);
    });

    it('requires incident.create permission', function () {
        $roleWithoutCreate = Role::create(['name' => 'no-create-incident', 'guard_name' => 'web']);
        $user = createUserOnRegularSite($this->regularSite, $roleWithoutCreate);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/incidents', [
                'material_id' => $this->material->id,
                'description' => 'Test incident',
            ]);

        $response->assertForbidden();
    });

    it('requires authentication', function () {
        $response = $this->postJson('/api/v1/incidents', [
            'material_id' => $this->material->id,
            'description' => 'Test incident',
        ]);

        $response->assertUnauthorized();
    });
});

// ============================================================================
// UPDATE TESTS
// ============================================================================

describe('update', function () {
    it('can update incident description', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $incident = Incident::factory()->forSite($this->material->site_id)->forMaterial($this->material)->reportedBy($user)->create([
            'description' => 'Original description',
        ]);

        $response = $this->actingAs($user)
            ->putJson("/api/v1/incidents/{$incident->id}", [
                'description' => 'Updated description',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.description', 'Updated description')
            ->assertJsonPath('data.edited_by_id', $user->id);
    });

    it('can update incident severity', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $incident = Incident::factory()->forSite($this->material->site_id)->forMaterial($this->material)->reportedBy($user)
            ->withSeverity(IncidentSeverity::LOW)->create();

        $response = $this->actingAs($user)
            ->putJson("/api/v1/incidents/{$incident->id}", [
                'severity' => 'critical',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.severity', 'critical');
    });

    it('can update incident status', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $incident = Incident::factory()->forSite($this->material->site_id)->forMaterial($this->material)->reportedBy($user)
            ->withStatus(IncidentStatus::OPEN)->create();

        $response = $this->actingAs($user)
            ->putJson("/api/v1/incidents/{$incident->id}", [
                'status' => 'in_progress',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'in_progress');
    });

    it('can resolve incident by setting resolved_at', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $incident = Incident::factory()->forSite($this->material->site_id)->forMaterial($this->material)->reportedBy($user)
            ->withStatus(IncidentStatus::OPEN)->create([
                'started_at' => '2024-01-15 10:00:00',
            ]);

        $response = $this->actingAs($user)
            ->putJson("/api/v1/incidents/{$incident->id}", [
                'resolved_at' => '2024-01-15 12:00:00',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'resolved');
    });

    it('cannot update incident from another site', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $otherSiteIncident = Incident::factory()->forSite($this->otherSiteMaterial->site_id)->forMaterial($this->otherSiteMaterial)->create();

        $response = $this->actingAs($user)
            ->putJson("/api/v1/incidents/{$otherSiteIncident->id}", [
                'description' => 'Updated description',
            ]);

        $response->assertForbidden();
    });

    it('requires incident.update permission', function () {
        $roleWithoutUpdate = Role::create(['name' => 'no-update-incident', 'guard_name' => 'web']);
        $user = createUserOnRegularSite($this->regularSite, $roleWithoutUpdate);

        $incident = Incident::factory()->forSite($this->material->site_id)->forMaterial($this->material)->create();

        $response = $this->actingAs($user)
            ->putJson("/api/v1/incidents/{$incident->id}", [
                'description' => 'Updated description',
            ]);

        $response->assertForbidden();
    });
});

// ============================================================================
// DESTROY TESTS
// ============================================================================

describe('destroy', function () {
    it('can delete incident', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $incident = Incident::factory()->forSite($this->material->site_id)->forMaterial($this->material)->reportedBy($user)->create();

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/incidents/{$incident->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('incidents', [
            'id' => $incident->id,
        ]);
    });

    it('cannot delete incident from another site', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $otherSiteIncident = Incident::factory()->forSite($this->otherSiteMaterial->site_id)->forMaterial($this->otherSiteMaterial)->create();

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/incidents/{$otherSiteIncident->id}");

        $response->assertForbidden();
    });

    it('requires incident.delete permission', function () {
        $roleWithoutDelete = Role::create(['name' => 'no-delete-incident', 'guard_name' => 'web']);
        $user = createUserOnRegularSite($this->regularSite, $roleWithoutDelete);

        $incident = Incident::factory()->forSite($this->material->site_id)->forMaterial($this->material)->create();

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/incidents/{$incident->id}");

        $response->assertForbidden();
    });
});

// ============================================================================
// AVAILABLE MATERIALS TESTS
// ============================================================================

describe('availableMaterials', function () {
    it('returns list of materials for current site', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        Material::factory()->count(2)->forSite($this->zone->site_id)->forZone($this->zone)->create();
        Material::factory()->count(3)->forSite($this->otherSiteZone->site_id)->forZone($this->otherSiteZone)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/incidents/available-materials');

        $response->assertOk()
            // 2 additional + 1 from beforeEach
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name'],
                ],
            ]);
    });

    it('requires incident.viewAny permission', function () {
        $roleWithoutViewAny = Role::create(['name' => 'no-materials-list', 'guard_name' => 'web']);
        $user = createUserOnRegularSite($this->regularSite, $roleWithoutViewAny);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/incidents/available-materials');

        $response->assertForbidden();
    });
});

// ============================================================================
// AVAILABLE MAINTENANCES TESTS
// ============================================================================

describe('availableMaintenances', function () {
    it('returns list of maintenances for current site', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        Maintenance::factory()->count(2)->forSite($this->material->site_id)->forMaterial($this->material)->create();
        Maintenance::factory()->count(3)->forSite($this->otherSiteMaterial->site_id)->forMaterial($this->otherSiteMaterial)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/incidents/available-maintenances');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'description', 'material_id'],
                ],
            ]);
    });

    it('can filter maintenances by material_id', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $anotherMaterial = Material::factory()->forZone($this->zone)->create();

        Maintenance::factory()->count(2)->forSite($this->material->site_id)->forMaterial($this->material)->create();
        Maintenance::factory()->count(3)->forSite($anotherMaterial->site_id)->forMaterial($anotherMaterial)->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/incidents/available-maintenances?material_id={$this->material->id}");

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('requires incident.viewAny permission', function () {
        $roleWithoutViewAny = Role::create(['name' => 'no-maintenances-list', 'guard_name' => 'web']);
        $user = createUserOnRegularSite($this->regularSite, $roleWithoutViewAny);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/incidents/available-maintenances');

        $response->assertForbidden();
    });
});

// ============================================================================
// OPTIONS TESTS
// ============================================================================

describe('options', function () {
    it('returns severity options', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/incidents/severity-options');

        $response->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['value', 'label'],
                ],
            ]);
    });

    it('returns status options', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/incidents/status-options');

        $response->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['value', 'label'],
                ],
            ]);
    });
});
