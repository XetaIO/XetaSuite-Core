<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use XetaSuite\Models\Item;
use XetaSuite\Models\ItemMovement;
use XetaSuite\Models\Material;
use XetaSuite\Models\Site;
use XetaSuite\Models\Supplier;
use XetaSuite\Models\User;
use XetaSuite\Models\Zone;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create headquarters site
    $this->headquarters = Site::factory()->create(['is_headquarters' => true]);

    // Create a regular site
    $this->regularSite = Site::factory()->create(['is_headquarters' => false]);

    // Create another regular site
    $this->otherSite = Site::factory()->create(['is_headquarters' => false]);

    // Create a supplier (on HQ)
    $this->supplier = Supplier::factory()->create();

    // Create permissions
    $permissions = [
        'item.viewAny',
        'item.view',
        'item.create',
        'item.update',
        'item.delete',
    ];

    foreach ($permissions as $permission) {
        Permission::create(['name' => $permission, 'guard_name' => 'web']);
    }

    // Create role with all permissions
    $this->role = Role::create(['name' => 'item-manager', 'guard_name' => 'web']);
    $this->role->givePermissionTo($permissions);

    // Create zones for materials
    $this->zone = Zone::factory()->forSite($this->regularSite)->create([
        'name' => 'Zone With Materials',
        'allow_material' => true,
    ]);

    $this->otherSiteZone = Zone::factory()->forSite($this->otherSite)->create([
        'name' => 'Other Site Zone',
        'allow_material' => true,
    ]);
});

// ============================================================================
// INDEX TESTS
// ============================================================================

describe('index', function () {
    it('returns only items for user current site', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        Item::factory()->count(3)->forSite($this->regularSite)->createdBy($user)->create();
        Item::factory()->count(2)->forSite($this->otherSite)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/items');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('returns paginated list with proper structure', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        Item::factory()->count(5)->forSite($this->regularSite)->createdBy($user)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/items');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'reference',
                        'description',
                        'supplier_id',
                        'supplier_name',
                        'purchase_price',
                        'stock_level',
                        'stock_status',
                        'created_by_id',
                        'created_by_name',
                        'created_at',
                    ],
                ],
                'links',
                'meta',
            ]);
    });

    it('can search items by name', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        Item::factory()->forSite($this->regularSite)->createdBy($user)->create(['name' => 'Alpha Product']);
        Item::factory()->forSite($this->regularSite)->createdBy($user)->create(['name' => 'Beta Item']);
        Item::factory()->forSite($this->regularSite)->createdBy($user)->create(['name' => 'Gamma Widget']);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/items?search=Beta');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Beta Item');
    });

    it('can search items by reference', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        Item::factory()->forSite($this->regularSite)->createdBy($user)->create(['reference' => 'REF-001']);
        Item::factory()->forSite($this->regularSite)->createdBy($user)->create(['reference' => 'REF-002']);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/items?search=REF-001');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.reference', 'REF-001');
    });

    it('can filter items by supplier', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);
        $supplier2 = Supplier::factory()->create();

        Item::factory()->forSite($this->regularSite)->createdBy($user)
            ->fromSupplier($this->supplier)->count(2)->create();
        Item::factory()->forSite($this->regularSite)->createdBy($user)
            ->fromSupplier($supplier2)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/items?supplier_id='.$this->supplier->id);

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('requires item.viewAny permission', function () {
        $roleWithoutPermission = Role::create(['name' => 'no-item-view', 'guard_name' => 'web']);
        $user = createUserOnRegularSite($this->regularSite, $roleWithoutPermission);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/items');

        $response->assertForbidden();
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/items');

        $response->assertUnauthorized();
    });
});

// ============================================================================
// SHOW TESTS
// ============================================================================

describe('show', function () {
    it('returns item details with proper structure', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $item = Item::factory()
            ->forSite($this->regularSite)
            ->createdBy($user)
            ->fromSupplier($this->supplier)
            ->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/items/'.$item->id);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'reference',
                    'description',
                    'site_id',
                    'supplier_id',
                    'supplier_name',
                    'supplier_reference',
                    'supplier',
                    'purchase_price',
                    'currency',
                    'stock_level',
                    'stock_status',
                    'item_entry_total',
                    'item_exit_total',
                    'item_entry_count',
                    'item_exit_count',
                    'number_warning_enabled',
                    'number_warning_minimum',
                    'number_critical_enabled',
                    'number_critical_minimum',
                    'materials',
                    'recipients',
                    'created_by_id',
                    'created_by_name',
                    'created_at',
                    'updated_at',
                ],
            ]);
    });

    it('cannot view item from another site', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $otherItem = Item::factory()->forSite($this->otherSite)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/items/'.$otherItem->id);

        $response->assertForbidden();
    });

    it('requires item.view permission', function () {
        $roleWithoutPermission = Role::create(['name' => 'no-item-view', 'guard_name' => 'web']);
        $user = createUserOnRegularSite($this->regularSite, $roleWithoutPermission);

        $item = Item::factory()->forSite($this->regularSite)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/items/'.$item->id);

        $response->assertForbidden();
    });
});

// ============================================================================
// STORE TESTS
// ============================================================================

describe('store', function () {
    it('can create item with required fields only', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/items', [
                'name' => 'New Item',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'New Item');

        $this->assertDatabaseHas('items', [
            'name' => 'New Item',
            'site_id' => $this->regularSite->id,
            'created_by_id' => $user->id,
        ]);
    });

    it('can create item with all fields', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/items', [
                'name' => 'Full Item',
                'reference' => 'REF-FULL-001',
                'description' => 'A full item description',
                'supplier_id' => $this->supplier->id,
                'supplier_reference' => 'SUP-REF-001',
                'purchase_price' => 99.99,
                'currency' => 'EUR',
                'number_warning_enabled' => true,
                'number_warning_minimum' => 10,
                'number_critical_enabled' => true,
                'number_critical_minimum' => 5,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Full Item')
            ->assertJsonPath('data.supplier_id', $this->supplier->id)
            ->assertJsonPath('data.number_warning_enabled', true)
            ->assertJsonPath('data.number_critical_enabled', true);
    });

    it('can create item with materials', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $material1 = Material::factory()->forZone($this->zone)->create();
        $material2 = Material::factory()->forZone($this->zone)->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/items', [
                'name' => 'Item With Materials',
                'reference' => 'REF-MAT-001',
                'purchase_price' => 25.00,
                'material_ids' => [$material1->id, $material2->id],
            ]);

        $response->assertStatus(201)
            ->assertJsonCount(2, 'data.materials');
    });

    it('can create item with recipients', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $recipient = User::factory()->withSite($this->regularSite)->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/items', [
                'name' => 'Item With Recipients',
                'reference' => 'REF-REC-001',
                'purchase_price' => 50.00,
                'recipient_ids' => [$recipient->id],
            ]);

        $response->assertStatus(201)
            ->assertJsonCount(1, 'data.recipients');
    });

    it('validates required fields', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/items', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('validates reference uniqueness within site', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        Item::factory()->forSite($this->regularSite)->createdBy($user)->create(['reference' => 'DUPLICATE-REF']);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/items', [
                'name' => 'New Item',
                'reference' => 'DUPLICATE-REF',
                'purchase_price' => 10.00,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['reference']);
    });

    it('allows same reference on different sites', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        Item::factory()->forSite($this->otherSite)->create(['reference' => 'SHARED-REF']);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/items', [
                'name' => 'New Item',
                'reference' => 'SHARED-REF',
                'purchase_price' => 10.00,
            ]);

        $response->assertStatus(201);
    });

    it('requires item.create permission', function () {
        $roleWithoutPermission = Role::create(['name' => 'no-item-create', 'guard_name' => 'web']);
        $user = createUserOnRegularSite($this->regularSite, $roleWithoutPermission);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/items', [
                'name' => 'New Item',
                'reference' => 'REF-001',
                'purchase_price' => 10.00,
            ]);

        $response->assertForbidden();
    });
});

// ============================================================================
// UPDATE TESTS
// ============================================================================

describe('update', function () {
    it('can update item basic fields', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $item = Item::factory()
            ->forSite($this->regularSite)
            ->createdBy($user)
            ->create(['name' => 'Original Name']);

        $response = $this->actingAs($user)
            ->putJson('/api/v1/items/'.$item->id, [
                'name' => 'Updated Name',
                'reference' => $item->reference,
                'purchase_price' => 199.99,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.purchase_price', 199.99);

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'name' => 'Updated Name',
            'edited_by_id' => $user->id,
        ]);
    });

    it('cannot update item from another site', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $otherItem = Item::factory()->forSite($this->otherSite)->create();

        $response = $this->actingAs($user)
            ->putJson('/api/v1/items/'.$otherItem->id, [
                'name' => 'Updated Name',
                'reference' => 'NEW-REF',
                'purchase_price' => 10.00,
            ]);

        $response->assertForbidden();
    });

    it('validates reference uniqueness on update', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $item1 = Item::factory()->forSite($this->regularSite)->createdBy($user)->create(['reference' => 'REF-001']);
        $item2 = Item::factory()->forSite($this->regularSite)->createdBy($user)->create(['reference' => 'REF-002']);

        $response = $this->actingAs($user)
            ->putJson('/api/v1/items/'.$item2->id, [
                'name' => $item2->name,
                'reference' => 'REF-001', // Already exists
                'purchase_price' => $item2->purchase_price,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['reference']);
    });

    it('allows keeping same reference on update', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $item = Item::factory()->forSite($this->regularSite)->createdBy($user)->create(['reference' => 'MY-REF']);

        $response = $this->actingAs($user)
            ->putJson('/api/v1/items/'.$item->id, [
                'name' => 'Updated Name',
                'reference' => 'MY-REF', // Same as before
                'purchase_price' => 50.00,
            ]);

        $response->assertOk();
    });

    it('requires item.update permission', function () {
        $roleWithoutPermission = Role::create(['name' => 'no-item-update', 'guard_name' => 'web']);
        $user = createUserOnRegularSite($this->regularSite, $roleWithoutPermission);

        $item = Item::factory()->forSite($this->regularSite)->create();

        $response = $this->actingAs($user)
            ->putJson('/api/v1/items/'.$item->id, [
                'name' => 'Updated Name',
                'reference' => $item->reference,
                'purchase_price' => 10.00,
            ]);

        $response->assertForbidden();
    });
});

// ============================================================================
// DESTROY TESTS
// ============================================================================

describe('destroy', function () {
    it('can delete item without movements', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $item = Item::factory()
            ->forSite($this->regularSite)
            ->createdBy($user)
            ->create();

        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/items/'.$item->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('items', ['id' => $item->id]);
    });

    it('cannot delete item with movements', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $item = Item::factory()
            ->forSite($this->regularSite)
            ->createdBy($user)
            ->create();

        ItemMovement::factory()->forItem($item)->create();

        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/items/'.$item->id);

        $response->assertUnprocessable();

        $this->assertDatabaseHas('items', ['id' => $item->id]);
    });

    it('cannot delete item from another site', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $otherItem = Item::factory()->forSite($this->otherSite)->create();

        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/items/'.$otherItem->id);

        $response->assertForbidden();
    });

    it('requires item.delete permission', function () {
        $roleWithoutPermission = Role::create(['name' => 'no-item-delete', 'guard_name' => 'web']);
        $user = createUserOnRegularSite($this->regularSite, $roleWithoutPermission);

        $item = Item::factory()->forSite($this->regularSite)->create();

        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/items/'.$item->id);

        $response->assertForbidden();
    });
});

// ============================================================================
// STATS TESTS
// ============================================================================

describe('stats', function () {
    it('returns monthly statistics for item', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $item = Item::factory()
            ->forSite($this->regularSite)
            ->createdBy($user)
            ->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/items/'.$item->id.'/stats');

        $response->assertOk()
            ->assertJsonStructure([
                'stats' => [
                    '*' => [
                        'month',
                        'entries',
                        'exits',
                        'price',
                    ],
                ],
            ])
            ->assertJsonCount(12, 'stats');
    });

    it('cannot view stats for item from another site', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $otherItem = Item::factory()->forSite($this->otherSite)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/items/'.$otherItem->id.'/stats');

        $response->assertForbidden();
    });
});

// ============================================================================
// MOVEMENTS TESTS
// ============================================================================

describe('movements', function () {
    it('returns movements for item', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $item = Item::factory()
            ->forSite($this->regularSite)
            ->createdBy($user)
            ->create();

        ItemMovement::factory()->count(3)->forItem($item)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/items/'.$item->id.'/movements');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'quantity',
                        'unit_price',
                        'total_price',
                        'movement_date',
                        'notes',
                        'supplier_id',
                        'supplier_name',
                        'created_by_id',
                        'created_by_name',
                        'created_at',
                    ],
                ],
            ]);
    });

    it('cannot view movements for item from another site', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $otherItem = Item::factory()->forSite($this->otherSite)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/items/'.$otherItem->id.'/movements');

        $response->assertForbidden();
    });
});

// ============================================================================
// STORE MOVEMENT TESTS
// ============================================================================

describe('storeMovement', function () {
    it('can create entry movement', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $item = Item::factory()
            ->forSite($this->regularSite)
            ->createdBy($user)
            ->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/items/'.$item->id.'/movements', [
                'type' => 'entry',
                'quantity' => 50,
                'unit_price' => 10.00,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', 'entry')
            ->assertJsonPath('data.quantity', 50);

        // Check total_price is calculated correctly
        $responseData = $response->json('data');
        expect((float) $responseData['total_price'])->toBe(500.0);

        $item->refresh();
        expect($item->item_entry_total)->toBe(50)
            ->and($item->item_entry_count)->toBe(1);
    });

    it('can create exit movement', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        // Create fresh item for this test
        $item = Item::factory()
            ->forSite($this->regularSite)
            ->createdBy($user)
            ->create();

        // Create an entry movement first to have stock to exit
        ItemMovement::create([
            'item_id' => $item->id,
            'type' => 'entry',
            'quantity' => 100,
            'unit_price' => 0,
            'total_price' => 0,
            'created_by_id' => $user->id,
            'created_by_name' => $user->full_name,
            'movement_date' => now(),
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/items/'.$item->id.'/movements', [
                'type' => 'exit',
                'quantity' => 30,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', 'exit')
            ->assertJsonPath('data.quantity', 30);

        $item->refresh();
        expect($item->item_exit_total)->toBe(30)
            ->and($item->item_exit_count)->toBe(1);
    });

    it('validates movement type', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $item = Item::factory()
            ->forSite($this->regularSite)
            ->createdBy($user)
            ->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/items/'.$item->id.'/movements', [
                'type' => 'invalid',
                'quantity' => 10,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    });

    it('validates quantity is positive', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $item = Item::factory()
            ->forSite($this->regularSite)
            ->createdBy($user)
            ->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/items/'.$item->id.'/movements', [
                'type' => 'entry',
                'quantity' => -5,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity']);
    });

    it('cannot create movement for item from another site', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $otherItem = Item::factory()->forSite($this->otherSite)->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/items/'.$otherItem->id.'/movements', [
                'type' => 'entry',
                'quantity' => 10,
            ]);

        $response->assertForbidden();
    });
});

// ============================================================================
// QR CODE TESTS
// ============================================================================

describe('qrCode', function () {
    it('generates QR code for item', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $item = Item::factory()
            ->forSite($this->regularSite)
            ->createdBy($user)
            ->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/items/'.$item->id.'/qr-code');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'svg',
                    'url',
                    'size',
                ],
            ])
            ->assertJsonPath('data.size', 200);
    });

    it('respects size parameter', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $item = Item::factory()
            ->forSite($this->regularSite)
            ->createdBy($user)
            ->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/items/'.$item->id.'/qr-code?size=300');

        $response->assertOk()
            ->assertJsonPath('data.size', 300);
    });

    it('limits size between 100 and 500', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $item = Item::factory()
            ->forSite($this->regularSite)
            ->createdBy($user)
            ->create();

        $responseTooSmall = $this->actingAs($user)
            ->getJson('/api/v1/items/'.$item->id.'/qr-code?size=50');

        $responseTooSmall->assertOk()
            ->assertJsonPath('data.size', 100);

        $responseTooBig = $this->actingAs($user)
            ->getJson('/api/v1/items/'.$item->id.'/qr-code?size=1000');

        $responseTooBig->assertOk()
            ->assertJsonPath('data.size', 500);
    });

    it('cannot generate QR code for item from another site', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        $otherItem = Item::factory()->forSite($this->otherSite)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/items/'.$otherItem->id.'/qr-code');

        $response->assertForbidden();
    });
});

// ============================================================================
// AVAILABLE ENDPOINTS TESTS
// ============================================================================

describe('availableSuppliers', function () {
    it('returns list of suppliers', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        Supplier::factory()->count(3)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/items/available-suppliers');

        $response->assertOk()
            ->assertJsonCount(4, 'suppliers'); // 3 + 1 from beforeEach
    });

    it('requires item.viewAny permission', function () {
        $roleWithoutPermission = Role::create(['name' => 'no-item-view', 'guard_name' => 'web']);
        $user = createUserOnRegularSite($this->regularSite, $roleWithoutPermission);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/items/available-suppliers');

        $response->assertForbidden();
    });
});

describe('availableMaterials', function () {
    it('returns materials only from user current site', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        Material::factory()->count(3)->forZone($this->zone)->create();
        Material::factory()->count(2)->forZone($this->otherSiteZone)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/items/available-materials');

        $response->assertOk()
            ->assertJsonCount(3, 'materials');
    });
});

describe('availableRecipients', function () {
    it('returns users with access to current site', function () {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        User::factory()->count(2)->withSite($this->regularSite)->create();
        User::factory()->count(3)->withSite($this->otherSite)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/items/available-recipients');

        $response->assertOk()
            ->assertJsonCount(3, 'recipients'); // 2 + 1 (the user itself)
    });
});
