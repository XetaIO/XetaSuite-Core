<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use XetaSuite\Actions\Items\CreateItem;
use XetaSuite\Actions\Items\DeleteItem;
use XetaSuite\Actions\Items\UpdateItem;
use XetaSuite\Models\Item;
use XetaSuite\Models\ItemMovement;
use XetaSuite\Models\ItemPrice;
use XetaSuite\Models\Material;
use XetaSuite\Models\Site;
use XetaSuite\Models\Supplier;
use XetaSuite\Models\User;
use XetaSuite\Models\Zone;

uses(RefreshDatabase::class);

// ============================================================================
// CREATE ITEM TESTS
// ============================================================================

describe('CreateItem', function () {
    it('creates an item successfully', function () {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $action = app(CreateItem::class);

        $item = $action->handle($user, [
            'name' => 'New Item',
            'reference' => 'REF-001',
        ]);

        expect($item)->toBeInstanceOf(Item::class)
            ->and($item->name)->toBe('New Item')
            ->and($item->reference)->toBe('REF-001')
            ->and($item->site_id)->toBe($site->id)
            ->and($item->created_by_id)->toBe($user->id);
    });

    it('creates an item with supplier', function () {
        $site = Site::factory()->create();
        $supplier = Supplier::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $action = app(CreateItem::class);

        $item = $action->handle($user, [
            'name' => 'Item with Supplier',
            'supplier_id' => $supplier->id,
            'supplier_reference' => 'SUP-REF-001',
        ]);

        expect($item->supplier_id)->toBe($supplier->id)
            ->and($item->supplier_reference)->toBe('SUP-REF-001');
    });

    it('creates an item with current price and creates price history', function () {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $action = app(CreateItem::class);

        $item = $action->handle($user, [
            'name' => 'Priced Item',
            'current_price' => 99.99,
        ]);

        expect((float) $item->current_price)->toBe(99.99)
            ->and($item->prices)->toHaveCount(1)
            ->and((float) $item->prices->first()->price)->toBe(99.99);
    });

    it('does not create price history when price is zero', function () {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $action = app(CreateItem::class);

        $item = $action->handle($user, [
            'name' => 'Zero Price Item',
            'current_price' => 0,
        ]);

        expect((float) $item->current_price)->toBe(0.0)
            ->and($item->prices)->toHaveCount(0);
    });

    it('creates an item with materials attached', function () {
        $site = Site::factory()->create();
        $zone = Zone::factory()->forSite($site)->withAllowMaterial()->create();
        $material1 = Material::factory()->forZone($zone)->create();
        $material2 = Material::factory()->forZone($zone)->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $action = app(CreateItem::class);

        $item = $action->handle($user, [
            'name' => 'Item with Materials',
            'material_ids' => [$material1->id, $material2->id],
        ]);

        expect($item->materials)->toHaveCount(2);
    });

    it('creates an item with recipients attached', function () {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $recipient1 = User::factory()->create();
        $recipient2 = User::factory()->create();
        $action = app(CreateItem::class);

        $item = $action->handle($user, [
            'name' => 'Item with Recipients',
            'recipient_ids' => [$recipient1->id, $recipient2->id],
        ]);

        expect($item->recipients)->toHaveCount(2);
    });

    it('creates an item with stock warnings configured', function () {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $action = app(CreateItem::class);

        $item = $action->handle($user, [
            'name' => 'Item with Warnings',
            'number_warning_enabled' => true,
            'number_warning_minimum' => 10,
            'number_critical_enabled' => true,
            'number_critical_minimum' => 5,
        ]);

        expect($item->number_warning_enabled)->toBeTrue()
            ->and($item->number_warning_minimum)->toBe(10)
            ->and($item->number_critical_enabled)->toBeTrue()
            ->and($item->number_critical_minimum)->toBe(5);
    });
});

// ============================================================================
// UPDATE ITEM TESTS
// ============================================================================

describe('UpdateItem', function () {
    it('updates item name', function () {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $item = Item::factory()->forSite($site)->createdBy($user)->create(['name' => 'Old Name']);
        $action = app(UpdateItem::class);

        $updatedItem = $action->handle($item, $user, [
            'name' => 'New Name',
        ]);

        expect($updatedItem->name)->toBe('New Name')
            ->and($updatedItem->edited_by_id)->toBe($user->id);
    });

    it('updates item price', function () {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $item = Item::factory()->forSite($site)->createdBy($user)->create(['current_price' => 50.00]);
        $action = app(UpdateItem::class);

        $updatedItem = $action->handle($item, $user, [
            'current_price' => 75.00,
        ]);

        expect((float) $updatedItem->current_price)->toBe(75.0);
    });

    it('updates supplier', function () {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $oldSupplier = Supplier::factory()->create();
        $newSupplier = Supplier::factory()->create();
        $item = Item::factory()->forSite($site)->createdBy($user)->fromSupplier($oldSupplier)->create();
        $action = app(UpdateItem::class);

        $updatedItem = $action->handle($item, $user, [
            'supplier_id' => $newSupplier->id,
        ]);

        expect($updatedItem->supplier_id)->toBe($newSupplier->id);
    });

    it('syncs materials', function () {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $zone = Zone::factory()->forSite($site)->withAllowMaterial()->create();
        $oldMaterial = Material::factory()->forZone($zone)->create();
        $newMaterial = Material::factory()->forZone($zone)->create();
        $item = Item::factory()->forSite($site)->createdBy($user)->create();
        $item->materials()->attach($oldMaterial->id);
        $action = app(UpdateItem::class);

        $updatedItem = $action->handle($item, $user, [
            'material_ids' => [$newMaterial->id],
        ]);

        expect($updatedItem->materials)->toHaveCount(1)
            ->and($updatedItem->materials->first()->id)->toBe($newMaterial->id);
    });

    it('syncs recipients', function () {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $oldRecipient = User::factory()->create();
        $newRecipient = User::factory()->create();
        $item = Item::factory()->forSite($site)->createdBy($user)->create();
        $item->recipients()->attach($oldRecipient->id);
        $action = app(UpdateItem::class);

        $updatedItem = $action->handle($item, $user, [
            'recipient_ids' => [$newRecipient->id],
        ]);

        expect($updatedItem->recipients)->toHaveCount(1)
            ->and($updatedItem->recipients->first()->id)->toBe($newRecipient->id);
    });

    it('clears materials when empty array provided', function () {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $zone = Zone::factory()->forSite($site)->withAllowMaterial()->create();
        $material = Material::factory()->forZone($zone)->create();
        $item = Item::factory()->forSite($site)->createdBy($user)->create();
        $item->materials()->attach($material->id);
        $action = app(UpdateItem::class);

        $updatedItem = $action->handle($item, $user, [
            'material_ids' => [],
        ]);

        expect($updatedItem->materials)->toHaveCount(0);
    });

    it('returns fresh model after update', function () {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $item = Item::factory()->forSite($site)->createdBy($user)->create(['name' => 'Original']);
        $action = app(UpdateItem::class);

        $updatedItem = $action->handle($item, $user, ['name' => 'Updated']);

        expect($updatedItem)->not->toBe($item)
            ->and($updatedItem->name)->toBe('Updated');
    });
});

// ============================================================================
// DELETE ITEM TESTS
// ============================================================================

describe('DeleteItem', function () {
    it('deletes an item successfully', function () {
        $site = Site::factory()->create();
        $user = User::factory()->create();
        $item = Item::factory()->forSite($site)->createdBy($user)->create();
        $action = app(DeleteItem::class);

        $result = $action->handle($item);

        expect($result['success'])->toBeTrue()
            ->and(Item::find($item->id))->toBeNull();
    });

    it('cannot delete item with movements', function () {
        $site = Site::factory()->create();
        $user = User::factory()->create();
        $item = Item::factory()->forSite($site)->createdBy($user)->create();
        ItemMovement::factory()->forItem($item)->entry()->withQuantity(10, 5.00)->createdBy($user)->create();
        $action = app(DeleteItem::class);

        $result = $action->handle($item);

        expect($result['success'])->toBeFalse()
            ->and($result['message'])->toBe(__('items.cannot_delete_has_movements'))
            ->and(Item::find($item->id))->not->toBeNull();
    });

    it('detaches materials when deleting', function () {
        $site = Site::factory()->create();
        $user = User::factory()->create();
        $zone = Zone::factory()->forSite($site)->withAllowMaterial()->create();
        $material = Material::factory()->forZone($zone)->create();
        $item = Item::factory()->forSite($site)->createdBy($user)->create();
        $item->materials()->attach($material->id);
        $action = app(DeleteItem::class);

        $result = $action->handle($item);

        expect($result['success'])->toBeTrue()
            ->and(Material::find($material->id))->not->toBeNull()
            ->and($material->fresh()->items)->toHaveCount(0);
    });

    it('detaches recipients when deleting', function () {
        $site = Site::factory()->create();
        $user = User::factory()->create();
        $recipient = User::factory()->create();
        $item = Item::factory()->forSite($site)->createdBy($user)->create();
        $item->recipients()->attach($recipient->id);
        $action = app(DeleteItem::class);

        $result = $action->handle($item);

        expect($result['success'])->toBeTrue()
            ->and(User::find($recipient->id))->not->toBeNull();
    });

    it('deletes associated prices when deleting', function () {
        $site = Site::factory()->create();
        $user = User::factory()->create();
        $item = Item::factory()->forSite($site)->createdBy($user)->create();
        ItemPrice::factory()->forItem($item)->createdBy($user)->count(3)->create();
        $action = app(DeleteItem::class);

        $result = $action->handle($item);

        expect($result['success'])->toBeTrue()
            ->and(ItemPrice::where('item_id', $item->id)->count())->toBe(0);
    });
});
