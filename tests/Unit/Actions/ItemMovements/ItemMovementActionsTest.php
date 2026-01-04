<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use XetaSuite\Actions\ItemMovements\CreateItemMovement;
use XetaSuite\Actions\ItemMovements\DeleteItemMovement;
use XetaSuite\Actions\ItemMovements\UpdateItemMovement;
use XetaSuite\Jobs\Item\CheckItemCriticalStock;
use XetaSuite\Models\Item;
use XetaSuite\Models\ItemMovement;
use XetaSuite\Models\ItemPrice;
use XetaSuite\Models\Site;
use XetaSuite\Models\Company;
use XetaSuite\Models\User;

uses(RefreshDatabase::class);

// ============================================================================
// CREATE ITEM MOVEMENT TESTS
// ============================================================================

describe('CreateItemMovement', function (): void {
    beforeEach(function (): void {
        Queue::fake();
    });

    it('creates an entry movement successfully', function (): void {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $item = Item::factory()->forSite($site)->createdBy($user)->create();
        $action = app(CreateItemMovement::class);

        $movement = $action->handle($item, $user, [
            'type' => 'entry',
            'quantity' => 10,
            'unit_price' => 5.00,
        ]);

        expect($movement)->toBeInstanceOf(ItemMovement::class)
            ->and($movement->type)->toBe('entry')
            ->and($movement->quantity)->toBe(10)
            ->and((float) $movement->unit_price)->toBe(5.0)
            ->and((float) $movement->total_price)->toBe(50.0)
            ->and($movement->created_by_id)->toBe($user->id);
    });

    it('creates an entry movement with company', function (): void {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $company = Company::factory()->asItemProvider()->create();
        $item = Item::factory()->forSite($site)->createdBy($user)->create();
        $action = app(CreateItemMovement::class);

        $movement = $action->handle($item, $user, [
            'type' => 'entry',
            'quantity' => 5,
            'unit_price' => 10.00,
            'company_id' => $company->id,
            'company_invoice_number' => 'INV-001',
            'invoice_date' => '2025-01-15',
        ]);

        expect($movement->company_id)->toBe($company->id)
            ->and($movement->company_invoice_number)->toBe('INV-001')
            ->and($movement->invoice_date->toDateString())->toBe('2025-01-15');
    });

    it('creates an entry movement with notes', function (): void {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $item = Item::factory()->forSite($site)->createdBy($user)->create();
        $action = app(CreateItemMovement::class);

        $movement = $action->handle($item, $user, [
            'type' => 'entry',
            'quantity' => 3,
            'notes' => 'Restocking for project X',
        ]);

        expect($movement->notes)->toBe('Restocking for project X');
    });

    it('creates an entry movement with movement date', function (): void {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $item = Item::factory()->forSite($site)->createdBy($user)->create();
        $action = app(CreateItemMovement::class);

        $movement = $action->handle($item, $user, [
            'type' => 'entry',
            'quantity' => 2,
            'movement_date' => '2025-06-01',
        ]);

        expect($movement->movement_date->toDateString())->toBe('2025-06-01');
    });

    it('creates price history when price changes on entry', function (): void {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $item = Item::factory()->forSite($site)->createdBy($user)->create(['current_price' => 10.00]);
        $action = app(CreateItemMovement::class);

        $action->handle($item, $user, [
            'type' => 'entry',
            'quantity' => 5,
            'unit_price' => 15.00,
        ]);

        expect(ItemPrice::where('item_id', $item->id)->count())->toBe(1)
            ->and((float) $item->fresh()->current_price)->toBe(15.0);
    });

    it('does not create price history when price unchanged on entry', function (): void {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $item = Item::factory()->forSite($site)->createdBy($user)->create(['current_price' => 10.00]);
        $action = app(CreateItemMovement::class);

        $action->handle($item, $user, [
            'type' => 'entry',
            'quantity' => 5,
            'unit_price' => 10.00,
        ]);

        expect(ItemPrice::where('item_id', $item->id)->count())->toBe(0);
    });

    it('creates an exit movement successfully', function (): void {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $item = Item::factory()->forSite($site)->createdBy($user)->create([
            'current_price' => 5.00,
            'item_entry_total' => 20,
            'item_exit_total' => 0,
        ]);
        $action = app(CreateItemMovement::class);

        $movement = $action->handle($item, $user, [
            'type' => 'exit',
            'quantity' => 5,
        ]);

        expect($movement->type)->toBe('exit')
            ->and($movement->quantity)->toBe(5)
            ->and((float) $movement->unit_price)->toBe(5.0)
            ->and((float) $movement->total_price)->toBe(25.0);
    });

    it('throws exception when exit exceeds available stock', function (): void {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $item = Item::factory()->forSite($site)->createdBy($user)->create([
            'item_entry_total' => 5,
            'item_exit_total' => 0,
        ]);
        $action = app(CreateItemMovement::class);

        expect(fn () => $action->handle($item, $user, [
            'type' => 'exit',
            'quantity' => 10,
        ]))->toThrow(\Exception::class);
    });

    it('dispatches critical stock job when threshold reached on exit', function (): void {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $item = Item::factory()->forSite($site)->createdBy($user)->create([
            'item_entry_total' => 10,
            'item_exit_total' => 0,
            'number_critical_enabled' => true,
            'number_critical_minimum' => 5,
        ]);
        $action = app(CreateItemMovement::class);

        $action->handle($item, $user, [
            'type' => 'exit',
            'quantity' => 6,
        ]);

        Queue::assertPushed(CheckItemCriticalStock::class);
    });

    it('does not dispatch critical stock job when threshold not reached', function (): void {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $item = Item::factory()->forSite($site)->createdBy($user)->create([
            'item_entry_total' => 20,
            'item_exit_total' => 0,
            'number_critical_enabled' => true,
            'number_critical_minimum' => 5,
        ]);
        $action = app(CreateItemMovement::class);

        $action->handle($item, $user, [
            'type' => 'exit',
            'quantity' => 5,
        ]);

        Queue::assertNotPushed(CheckItemCriticalStock::class);
    });
});

// ============================================================================
// UPDATE ITEM MOVEMENT TESTS
// ============================================================================

describe('UpdateItemMovement', function (): void {
    it('updates movement quantity', function (): void {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $item = Item::factory()->forSite($site)->createdBy($user)->create([
            'item_entry_total' => 10,
        ]);
        $movement = ItemMovement::factory()->forItem($item)->entry()->withQuantity(5, 10.00)->createdBy($user)->create();
        $action = app(UpdateItemMovement::class);

        $updatedMovement = $action->handle($movement, [
            'quantity' => 8,
        ]);

        expect($updatedMovement->quantity)->toBe(8)
            ->and((float) $updatedMovement->total_price)->toBe(80.0);
    });

    it('updates movement unit price', function (): void {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $item = Item::factory()->forSite($site)->createdBy($user)->create();
        $movement = ItemMovement::factory()->forItem($item)->entry()->withQuantity(5, 10.00)->createdBy($user)->create();
        $action = app(UpdateItemMovement::class);

        $updatedMovement = $action->handle($movement, [
            'unit_price' => 15.00,
        ]);

        expect((float) $updatedMovement->unit_price)->toBe(15.0)
            ->and((float) $updatedMovement->total_price)->toBe(75.0);
    });

    it('updates movement company', function (): void {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $item = Item::factory()->forSite($site)->createdBy($user)->create();
        $oldCompany = Company::factory()->asItemProvider()->create();
        $newCompany = Company::factory()->asItemProvider()->create();
        $movement = ItemMovement::factory()->forItem($item)->entry()->fromCompany($oldCompany)->withQuantity(5, 10.00)->createdBy($user)->create();
        $action = app(UpdateItemMovement::class);

        $updatedMovement = $action->handle($movement, [
            'company_id' => $newCompany->id,
        ]);

        expect($updatedMovement->company_id)->toBe($newCompany->id);
    });

    it('updates movement invoice fields', function (): void {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $item = Item::factory()->forSite($site)->createdBy($user)->create();
        $movement = ItemMovement::factory()->forItem($item)->entry()->withQuantity(5, 10.00)->createdBy($user)->create();
        $action = app(UpdateItemMovement::class);

        $updatedMovement = $action->handle($movement, [
            'company_invoice_number' => 'NEW-INV-001',
            'invoice_date' => '2025-03-15',
        ]);

        expect($updatedMovement->company_invoice_number)->toBe('NEW-INV-001')
            ->and($updatedMovement->invoice_date->toDateString())->toBe('2025-03-15');
    });

    it('updates movement notes', function (): void {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $item = Item::factory()->forSite($site)->createdBy($user)->create();
        $movement = ItemMovement::factory()->forItem($item)->entry()->withQuantity(5, 10.00)->createdBy($user)->create(['notes' => 'Old notes']);
        $action = app(UpdateItemMovement::class);

        $updatedMovement = $action->handle($movement, [
            'notes' => 'Updated notes',
        ]);

        expect($updatedMovement->notes)->toBe('Updated notes');
    });

    it('updates movement date', function (): void {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $item = Item::factory()->forSite($site)->createdBy($user)->create();
        $movement = ItemMovement::factory()->forItem($item)->entry()->withQuantity(5, 10.00)->createdBy($user)->create();
        $action = app(UpdateItemMovement::class);

        $updatedMovement = $action->handle($movement, [
            'movement_date' => '2025-07-01',
        ]);

        expect($updatedMovement->movement_date->toDateString())->toBe('2025-07-01');
    });

    it('adjusts item entry total when entry quantity increases', function (): void {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $item = Item::factory()->forSite($site)->createdBy($user)->create([
            'item_entry_total' => 10,
        ]);
        // After factory create: 10 + 5 = 15 (observer adds quantity)
        $movement = ItemMovement::factory()->forItem($item)->entry()->withQuantity(5, 10.00)->createdBy($user)->create();
        $action = app(UpdateItemMovement::class);

        // After update: 15 + (8-5) = 18
        $action->handle($movement, ['quantity' => 8]);

        $item->refresh();
        expect($item->item_entry_total)->toBe(18);
    });

    it('adjusts item entry total when entry quantity decreases', function (): void {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $item = Item::factory()->forSite($site)->createdBy($user)->create([
            'item_entry_total' => 10,
        ]);
        // After factory create: 10 + 5 = 15 (observer adds quantity)
        $movement = ItemMovement::factory()->forItem($item)->entry()->withQuantity(5, 10.00)->createdBy($user)->create();
        $action = app(UpdateItemMovement::class);

        // After update: 15 + (3-5) = 13
        $action->handle($movement, ['quantity' => 3]);

        $item->refresh();
        expect($item->item_entry_total)->toBe(13);
    });

    it('adjusts item exit total when exit quantity changes', function (): void {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $item = Item::factory()->forSite($site)->createdBy($user)->create([
            'item_entry_total' => 20,
            'item_exit_total' => 5,
        ]);
        // After factory create: 5 + 5 = 10 (observer adds quantity)
        $movement = ItemMovement::factory()->forItem($item)->exit()->withQuantity(5, 10.00)->createdBy($user)->create();
        $action = app(UpdateItemMovement::class);

        // After update: 10 + (8-5) = 13
        $action->handle($movement, ['quantity' => 8]);

        $item->refresh();
        expect($item->item_exit_total)->toBe(13);
    });

    it('returns fresh model after update', function (): void {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $item = Item::factory()->forSite($site)->createdBy($user)->create();
        $movement = ItemMovement::factory()->forItem($item)->entry()->withQuantity(5, 10.00)->createdBy($user)->create();
        $action = app(UpdateItemMovement::class);

        $updatedMovement = $action->handle($movement, ['quantity' => 10]);

        expect($updatedMovement)->not->toBe($movement)
            ->and($updatedMovement->quantity)->toBe(10);
    });
});

// ============================================================================
// DELETE ITEM MOVEMENT TESTS
// ============================================================================

describe('DeleteItemMovement', function (): void {
    it('deletes an entry movement and adjusts item totals', function (): void {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);

        // Start with existing totals (simulating 2 previous movements of 5 each = 10 total)
        $item = Item::factory()->forSite($site)->createdBy($user)->create([
            'item_entry_total' => 10,
            'item_entry_count' => 2,
        ]);

        // Creating this movement will add to totals via observer: 10+5=15, 2+1=3
        $movement = ItemMovement::factory()->forItem($item)->entry()->withQuantity(5, 10.00)->createdBy($user)->create();

        // Verify totals after creation
        $item->refresh();
        expect($item->item_entry_total)->toBe(15)
            ->and($item->item_entry_count)->toBe(3);

        $action = app(DeleteItemMovement::class);
        $result = $action->handle($movement);

        expect($result['success'])->toBeTrue()
            ->and(ItemMovement::find($movement->id))->toBeNull();

        // After deletion: 15-5=10, 3-1=2
        $item->refresh();
        expect($item->item_entry_total)->toBe(10)
            ->and($item->item_entry_count)->toBe(2);
    });

    it('deletes an exit movement and adjusts item totals', function (): void {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);

        // Start with existing totals (simulating 1 previous exit of 5)
        $item = Item::factory()->forSite($site)->createdBy($user)->create([
            'item_entry_total' => 20,
            'item_exit_total' => 5,
            'item_exit_count' => 1,
        ]);

        // Creating this movement will add to totals via observer: 5+5=10, 1+1=2
        $movement = ItemMovement::factory()->forItem($item)->exit()->withQuantity(5, 10.00)->createdBy($user)->create();

        // Verify totals after creation
        $item->refresh();
        expect($item->item_exit_total)->toBe(10)
            ->and($item->item_exit_count)->toBe(2);

        $action = app(DeleteItemMovement::class);
        $result = $action->handle($movement);

        expect($result['success'])->toBeTrue()
            ->and(ItemMovement::find($movement->id))->toBeNull();

        // After deletion: 10-5=5, 2-1=1
        $item->refresh();
        expect($item->item_exit_total)->toBe(5)
            ->and($item->item_exit_count)->toBe(1);
    });

    it('returns success true after deletion', function (): void {
        $site = Site::factory()->create();
        $user = User::factory()->create(['current_site_id' => $site->id]);
        $item = Item::factory()->forSite($site)->createdBy($user)->create();
        $movement = ItemMovement::factory()->forItem($item)->entry()->withQuantity(5, 10.00)->createdBy($user)->create();
        $action = app(DeleteItemMovement::class);

        $result = $action->handle($movement);

        expect($result)->toBe(['success' => true]);
    });
});
