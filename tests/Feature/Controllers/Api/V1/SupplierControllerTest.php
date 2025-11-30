<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use XetaSuite\Models\Item;
use XetaSuite\Models\Site;
use XetaSuite\Models\Supplier;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create headquarters site
    $this->headquarters = Site::factory()->create(['is_headquarters' => true]);

    // Create a regular site
    $this->regularSite = Site::factory()->create(['is_headquarters' => false]);

    // Create permissions
    $permissions = [
        'supplier.viewAny',
        'supplier.view',
        'supplier.create',
        'supplier.update',
        'supplier.delete',
    ];

    foreach ($permissions as $permission) {
        Permission::create(['name' => $permission, 'guard_name' => 'web']);
    }

    // Create role with all permissions
    $this->role = Role::create(['name' => 'supplier-manager', 'guard_name' => 'web']);
    $this->role->givePermissionTo($permissions);
});

// ============================================================================
// INDEX TESTS
// ============================================================================

describe('index', function () {
    it('returns paginated list of suppliers for authorized user on headquarters', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        Supplier::factory()->count(25)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/suppliers');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'description', 'item_count', 'created_at'],
                ],
                'links',
                'meta',
            ])
            ->assertJsonCount(20, 'data'); // Default pagination
    });

    it('returns suppliers ordered by name', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        Supplier::factory()->create(['name' => 'Zebra Corp']);
        Supplier::factory()->create(['name' => 'Alpha Inc']);
        Supplier::factory()->create(['name' => 'Beta Ltd']);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/suppliers');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name')->toArray();
        expect($names)->toBe(['Alpha Inc', 'Beta Ltd', 'Zebra Corp']);
    });

    it('includes item_count in response', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $supplier = Supplier::factory()->create();
        Item::factory()->count(5)->forSite($this->headquarters)->fromSupplier($supplier)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/suppliers');

        $response->assertOk()
            ->assertJsonPath('data.0.item_count', 5);
    });

    it('denies access for user not on headquarters', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/suppliers');

        $response->assertForbidden();
    });

    it('denies access for user without viewAny permission', function () {
        $roleWithoutViewAny = Role::create(['name' => 'limited', 'guard_name' => 'web']);
        $user = createUserOnHeadquarters($this->headquarters, $roleWithoutViewAny);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/suppliers');

        $response->assertForbidden();
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/suppliers');

        $response->assertUnauthorized();
    });
});

// ============================================================================
// SHOW TESTS
// ============================================================================

describe('show', function () {
    it('returns supplier details for authorized user', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $supplier = Supplier::factory()->create([
            'name' => 'Test Supplier',
            'description' => 'Test description',
            'created_by_id' => $user->id,
            'created_by_name' => $user->full_name,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/suppliers/{$supplier->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'name', 'description', 'created_by_id', 'created_by_name', 'created_at', 'updated_at'],
            ])
            ->assertJsonPath('data.name', 'Test Supplier')
            ->assertJsonPath('data.description', 'Test description');
    });

    it('denies access for user not on headquarters', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $supplier = Supplier::factory()->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/suppliers/{$supplier->id}");

        $response->assertForbidden();
    });

    it('returns 404 for non-existent supplier', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/suppliers/99999');

        $response->assertNotFound();
    });
});

// ============================================================================
// ITEMS TESTS (paginated items for a supplier)
// ============================================================================

describe('items', function () {
    it('returns paginated items for a supplier', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $supplier = Supplier::factory()->create();
        Item::factory()->count(25)->forSite($this->headquarters)->fromSupplier($supplier)->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/suppliers/{$supplier->id}/items");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'reference', 'description'],
                ],
                'links',
                'meta',
            ])
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.total', 25);
    });

    it('returns items ordered by name', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $supplier = Supplier::factory()->create();
        Item::factory()->forSite($this->headquarters)->fromSupplier($supplier)->create(['name' => 'Zebra Item']);
        Item::factory()->forSite($this->headquarters)->fromSupplier($supplier)->create(['name' => 'Alpha Item']);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/suppliers/{$supplier->id}/items");

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name')->toArray();
        expect($names)->toBe(['Alpha Item', 'Zebra Item']);
    });

    it('returns only items from the specified supplier', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $supplier1 = Supplier::factory()->create();
        $supplier2 = Supplier::factory()->create();

        Item::factory()->count(3)->forSite($this->headquarters)->fromSupplier($supplier1)->create();
        Item::factory()->count(5)->forSite($this->headquarters)->fromSupplier($supplier2)->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/suppliers/{$supplier1->id}/items");

        $response->assertOk()
            ->assertJsonPath('meta.total', 3);
    });

    it('supports pagination', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $supplier = Supplier::factory()->create();
        Item::factory()->count(25)->forSite($this->headquarters)->fromSupplier($supplier)->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/suppliers/{$supplier->id}/items?page=2");

        $response->assertOk()
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonCount(5, 'data'); // 25 - 20 = 5 on page 2
    });

    it('denies access for user not on headquarters', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $supplier = Supplier::factory()->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/suppliers/{$supplier->id}/items");

        $response->assertForbidden();
    });
});

// ============================================================================
// STORE TESTS
// ============================================================================

describe('store', function () {
    it('creates a supplier with valid data', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/suppliers', [
                'name' => 'New Supplier',
                'description' => 'A great supplier',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'New Supplier')
            ->assertJsonPath('data.description', 'A great supplier');

        $this->assertDatabaseHas('suppliers', [
            'name' => 'New Supplier',
            'description' => 'A great supplier',
            'created_by_id' => $user->id,
            'created_by_name' => null,
        ]);
    });

    it('creates a supplier without description', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/suppliers', [
                'name' => 'Minimal Supplier',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Minimal Supplier')
            ->assertJsonPath('data.description', null);
    });

    it('requires name field', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/suppliers', [
                'description' => 'No name provided',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('validates name max length', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/suppliers', [
                'name' => str_repeat('a', 256),
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('denies creation for user not on headquarters', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/suppliers', [
                'name' => 'New Supplier',
            ]);

        $response->assertForbidden();
    });

    it('denies creation for user without create permission', function () {
        $roleWithoutCreate = Role::create(['name' => 'viewer', 'guard_name' => 'web']);
        $roleWithoutCreate->givePermissionTo('supplier.viewAny');

        $user = createUserOnHeadquarters($this->headquarters, $roleWithoutCreate);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/suppliers', [
                'name' => 'New Supplier',
            ]);

        $response->assertForbidden();
    });
});

// ============================================================================
// UPDATE TESTS
// ============================================================================

describe('update', function () {
    it('updates a supplier with valid data', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $supplier = Supplier::factory()->create([
            'name' => 'Old Name',
        ]);

        $response = $this->actingAs($user)
            ->putJson("/api/v1/suppliers/{$supplier->id}", [
                'name' => 'Updated Name',
                'description' => 'Updated description',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.description', 'Updated description');

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'Updated Name',
        ]);
    });

    it('allows partial update', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $supplier = Supplier::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original description',
        ]);

        $response = $this->actingAs($user)
            ->putJson("/api/v1/suppliers/{$supplier->id}", [
                'description' => 'Only description updated',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Original Name')
            ->assertJsonPath('data.description', 'Only description updated');
    });

    it('denies update for user not on headquarters', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $supplier = Supplier::factory()->create();

        $response = $this->actingAs($user)
            ->putJson("/api/v1/suppliers/{$supplier->id}", [
                'name' => 'Updated Name',
            ]);

        $response->assertForbidden();
    });
});

// ============================================================================
// DESTROY TESTS
// ============================================================================

describe('destroy', function () {
    it('deletes a supplier without items', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $supplier = Supplier::factory()->create();

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/suppliers/{$supplier->id}");

        $response->assertOk();

        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    });

    it('prevents deletion of supplier with items', function () {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        $supplier = Supplier::factory()->create();
        Item::factory()->forSite($this->headquarters)->fromSupplier($supplier)->create();

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/suppliers/{$supplier->id}");

        $response->assertUnprocessable();

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    });

    it('denies deletion for user not on headquarters', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $supplier = Supplier::factory()->create();

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/suppliers/{$supplier->id}");

        $response->assertForbidden();
    });

    it('denies deletion for user without delete permission', function () {
        $roleWithoutDelete = Role::create(['name' => 'editor', 'guard_name' => 'web']);
        $roleWithoutDelete->givePermissionTo(['supplier.viewAny', 'supplier.view', 'supplier.update']);

        $user = createUserOnHeadquarters($this->headquarters, $roleWithoutDelete);

        $supplier = Supplier::factory()->create();

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/suppliers/{$supplier->id}");

        $response->assertForbidden();
    });
});
