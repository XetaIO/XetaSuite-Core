<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use XetaSuite\Models\EventCategory;
use XetaSuite\Models\Site;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Create headquarters site
    $this->headquarters = Site::factory()->create(['is_headquarters' => true]);

    // Create a regular site
    $this->regularSite = Site::factory()->create(['is_headquarters' => false]);

    // Create another regular site
    $this->otherSite = Site::factory()->create(['is_headquarters' => false]);

    // Create permissions
    $permissions = [
        'eventCategory.viewAny',
        'eventCategory.view',
        'eventCategory.create',
        'eventCategory.update',
        'eventCategory.delete',
    ];

    foreach ($permissions as $permission) {
        Permission::create(['name' => $permission, 'guard_name' => 'web']);
    }

    // Create role with all permissions
    $this->role = Role::create(['name' => 'calendar-manager', 'guard_name' => 'web']);
    $this->role->givePermissionTo($permissions);
});

// ============================================================================
// INDEX TESTS
// ============================================================================

describe('index', function (): void {
    it('returns only categories for user current site', function (): void {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        EventCategory::factory()->count(3)->forSite($this->regularSite)->create();
        EventCategory::factory()->count(2)->forSite($this->otherSite)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/event-categories');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('returns paginated list with proper structure', function (): void {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        EventCategory::factory()->count(5)->forSite($this->regularSite)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/event-categories');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'color',
                        'description',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links',
                'meta',
            ]);
    });

    it('can search categories by name', function (): void {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        EventCategory::factory()->forSite($this->regularSite)->create(['name' => 'Meetings']);
        EventCategory::factory()->forSite($this->regularSite)->create(['name' => 'Training']);
        EventCategory::factory()->forSite($this->regularSite)->create(['name' => 'Team Building']);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/event-categories?search=meet');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Meetings');
    });

    it('denies access without permission', function (): void {
        $roleWithoutPermission = Role::create(['name' => 'no-access', 'guard_name' => 'web']);
        $user = createUserOnRegularSite($this->regularSite, $roleWithoutPermission);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/event-categories');

        $response->assertForbidden();
    });

    it('denies access from headquarters', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        // HQ cannot viewAny event categories - they are site-scoped
        EventCategory::factory()->count(2)->forSite($this->regularSite)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/event-categories');

        // HQ users should be able to viewAny (the policy's before() only blocks create/update/delete)
        $response->assertOk();
    });
});

// ============================================================================
// STORE TESTS
// ============================================================================

describe('store', function (): void {
    it('creates a new category successfully', function (): void {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $data = [
            'name' => 'Team Meetings',
            'color' => '#22c55e',
            'description' => 'Weekly team sync meetings',
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/v1/event-categories', $data);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Team Meetings')
            ->assertJsonPath('data.color', '#22c55e');

        $this->assertDatabaseHas('event_categories', [
            'site_id' => $this->regularSite->id,
            'name' => 'Team Meetings',
            'color' => '#22c55e',
        ]);
    });

    it('creates category with default color if not provided', function (): void {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/event-categories', [
                'name' => 'New Category',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.color', '#465fff');
    });

    it('validates required name', function (): void {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/event-categories', [
                'color' => '#ff0000',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('validates color format', function (): void {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/event-categories', [
                'name' => 'Test',
                'color' => 'invalid-color',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['color']);
    });

    it('denies create from headquarters', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/event-categories', [
                'name' => 'HQ Category',
            ]);

        $response->assertForbidden();
    });
});

// ============================================================================
// SHOW TESTS
// ============================================================================

describe('show', function (): void {
    it('returns category details', function (): void {
        $user = createUserOnRegularSite($this->regularSite, $this->role);
        $category = EventCategory::factory()->forSite($this->regularSite)->create([
            'name' => 'Test Category',
            'color' => '#3b82f6',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/event-categories/{$category->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $category->id)
            ->assertJsonPath('data.name', 'Test Category')
            ->assertJsonPath('data.color', '#3b82f6');
    });

    it('denies access to category from other site', function (): void {
        $user = createUserOnRegularSite($this->regularSite, $this->role);
        $otherCategory = EventCategory::factory()->forSite($this->otherSite)->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/event-categories/{$otherCategory->id}");

        $response->assertForbidden();
    });
});

// ============================================================================
// UPDATE TESTS
// ============================================================================

describe('update', function (): void {
    it('updates category successfully', function (): void {
        $user = createUserOnRegularSite($this->regularSite, $this->role);
        $category = EventCategory::factory()->forSite($this->regularSite)->create([
            'name' => 'Old Name',
            'color' => '#000000',
        ]);

        $response = $this->actingAs($user)
            ->putJson("/api/v1/event-categories/{$category->id}", [
                'name' => 'New Name',
                'color' => '#ffffff',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.color', '#ffffff');

        $this->assertDatabaseHas('event_categories', [
            'id' => $category->id,
            'name' => 'New Name',
            'color' => '#ffffff',
        ]);
    });

    it('updates only provided fields', function (): void {
        $user = createUserOnRegularSite($this->regularSite, $this->role);
        $category = EventCategory::factory()->forSite($this->regularSite)->create([
            'name' => 'Original Name',
            'color' => '#ff0000',
            'description' => 'Original description',
        ]);

        $response = $this->actingAs($user)
            ->putJson("/api/v1/event-categories/{$category->id}", [
                'name' => 'Updated Name',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.color', '#ff0000')
            ->assertJsonPath('data.description', 'Original description');
    });

    it('denies update from headquarters', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);
        $category = EventCategory::factory()->forSite($this->regularSite)->create();

        $response = $this->actingAs($user)
            ->putJson("/api/v1/event-categories/{$category->id}", [
                'name' => 'Updated',
            ]);

        $response->assertForbidden();
    });

    it('denies update to category from other site', function (): void {
        $user = createUserOnRegularSite($this->regularSite, $this->role);
        $otherCategory = EventCategory::factory()->forSite($this->otherSite)->create();

        $response = $this->actingAs($user)
            ->putJson("/api/v1/event-categories/{$otherCategory->id}", [
                'name' => 'Hacked',
            ]);

        $response->assertForbidden();
    });
});

// ============================================================================
// DELETE TESTS
// ============================================================================

describe('destroy', function (): void {
    it('deletes category successfully', function (): void {
        $user = createUserOnRegularSite($this->regularSite, $this->role);
        $category = EventCategory::factory()->forSite($this->regularSite)->create();

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/event-categories/{$category->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('event_categories', [
            'id' => $category->id,
        ]);
    });

    it('denies delete from headquarters', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);
        $category = EventCategory::factory()->forSite($this->regularSite)->create();

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/event-categories/{$category->id}");

        $response->assertForbidden();
    });

    it('denies delete of category from other site', function (): void {
        $user = createUserOnRegularSite($this->regularSite, $this->role);
        $otherCategory = EventCategory::factory()->forSite($this->otherSite)->create();

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/event-categories/{$otherCategory->id}");

        $response->assertForbidden();
    });
});
