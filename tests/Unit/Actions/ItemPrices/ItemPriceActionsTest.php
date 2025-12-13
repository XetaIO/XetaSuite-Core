<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use XetaSuite\Actions\ItemPrices\CreateItemPrice;
use XetaSuite\Models\Item;
use XetaSuite\Models\ItemPrice;
use XetaSuite\Models\Site;
use XetaSuite\Models\Supplier;
use XetaSuite\Models\User;

uses(RefreshDatabase::class);

// ============================================================================
// CREATE ITEM PRICE TESTS
// ============================================================================

describe('CreateItemPrice', function () {
    it('creates a price entry successfully', function () {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $item = Item::factory()->forSite($site)->createdBy($user)->create(['current_price' => 0]);
        $action = app(CreateItemPrice::class);

        $price = $action->handle($item, $user, [
            'current_price' => 25.50,
            'supplier' => null,
        ]);

        expect($price)->toBeInstanceOf(ItemPrice::class)
            ->and($price->item_id)->toBe($item->id)
            ->and((float) $price->price)->toBe(25.50)
            ->and($price->created_by_id)->toBe($user->id)
            ->and($price->created_by_name)->toBe($user->full_name);
    });

    it('creates a price entry with supplier', function () {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $supplier = Supplier::factory()->create();
        $item = Item::factory()->forSite($site)->createdBy($user)->create();
        $action = app(CreateItemPrice::class);

        $price = $action->handle($item, $user, [
            'current_price' => 50.00,
            'supplier' => $supplier,
        ]);

        expect($price->supplier_id)->toBe($supplier->id);
    });

    it('creates a price entry with notes', function () {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $item = Item::factory()->forSite($site)->createdBy($user)->create();
        $action = app(CreateItemPrice::class);

        $price = $action->handle($item, $user, [
            'current_price' => 75.00,
            'notes' => 'Price increase due to inflation',
            'supplier' => null,
        ]);

        expect($price->notes)->toBe('Price increase due to inflation');
    });

    it('creates a price entry with effective date', function () {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $item = Item::factory()->forSite($site)->createdBy($user)->create();
        $action = app(CreateItemPrice::class);
        $effectiveDate = now()->subDays(5);

        $price = $action->handle($item, $user, [
            'current_price' => 60.00,
            'effective_date' => $effectiveDate,
            'supplier' => null,
        ]);

        expect($price->effective_date->toDateString())->toBe($effectiveDate->toDateString());
    });

    it('updates item current price after creating price entry', function () {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $item = Item::factory()->forSite($site)->createdBy($user)->create(['current_price' => 10.00]);
        $action = app(CreateItemPrice::class);

        $action->handle($item, $user, [
            'current_price' => 99.99,
            'supplier' => null,
        ]);

        $item->refresh();
        expect((float) $item->current_price)->toBe(99.99);
    });

    it('updates item supplier after creating price entry with supplier', function () {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $supplier = Supplier::factory()->create();
        $item = Item::factory()->forSite($site)->createdBy($user)->create(['supplier_id' => null]);
        $action = app(CreateItemPrice::class);

        $action->handle($item, $user, [
            'current_price' => 50.00,
            'supplier' => $supplier,
        ]);

        $item->refresh();
        expect($item->supplier_id)->toBe($supplier->id);
    });

    it('uses current date as effective date when not specified', function () {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $item = Item::factory()->forSite($site)->createdBy($user)->create();
        $action = app(CreateItemPrice::class);

        $price = $action->handle($item, $user, [
            'current_price' => 45.00,
            'supplier' => null,
        ]);

        expect($price->effective_date->toDateString())->toBe(now()->toDateString());
    });

    it('creates multiple price entries for same item', function () {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $item = Item::factory()->forSite($site)->createdBy($user)->create(['current_price' => 0]);
        $action = app(CreateItemPrice::class);

        $action->handle($item, $user, ['current_price' => 10.00, 'supplier' => null]);
        $action->handle($item, $user, ['current_price' => 15.00, 'supplier' => null]);
        $action->handle($item, $user, ['current_price' => 20.00, 'supplier' => null]);

        expect(ItemPrice::where('item_id', $item->id)->count())->toBe(3);
        expect((float) $item->fresh()->current_price)->toBe(20.0);
    });
});
