<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use XetaSuite\Actions\Suppliers\CreateSupplier;
use XetaSuite\Actions\Suppliers\DeleteSupplier;
use XetaSuite\Actions\Suppliers\UpdateSupplier;
use XetaSuite\Models\Item;
use XetaSuite\Models\Site;
use XetaSuite\Models\Supplier;
use XetaSuite\Models\User;

uses(RefreshDatabase::class);

describe('CreateSupplier', function () {
    it('creates a supplier with all fields', function () {
        $user = User::factory()->create();
        $action = app(CreateSupplier::class);

        $supplier = $action->handle($user, [
            'name' => 'Acme Corporation',
            'description' => 'A great supplier',
        ]);

        expect($supplier)->toBeInstanceOf(Supplier::class)
            ->and($supplier->name)->toBe('Acme Corporation')
            ->and($supplier->description)->toBe('A great supplier')
            ->and($supplier->created_by_id)->toBe($user->id);

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'Acme Corporation',
            'description' => 'A great supplier',
            'created_by_id' => $user->id,
        ]);
    });

    it('creates a supplier without description', function () {
        $user = User::factory()->create();
        $action = app(CreateSupplier::class);

        $supplier = $action->handle($user, [
            'name' => 'Simple Supplier',
        ]);

        expect($supplier->name)->toBe('Simple Supplier')
            ->and($supplier->description)->toBeNull();
    });
});

describe('UpdateSupplier', function () {
    it('updates supplier with all fields', function () {
        $supplier = Supplier::factory()->create([
            'name' => 'Old Name',
            'description' => 'Old description',
        ]);
        $action = app(UpdateSupplier::class);

        $updated = $action->handle($supplier, [
            'name' => 'New Name',
            'description' => 'New description',
        ]);

        expect($updated->name)->toBe('New Name')
            ->and($updated->description)->toBe('New description');

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'New Name',
            'description' => 'New description',
        ]);
    });

    it('updates supplier partially', function () {
        $supplier = Supplier::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original description',
        ]);
        $action = app(UpdateSupplier::class);

        $updated = $action->handle($supplier, [
            'name' => 'Updated Name',
        ]);

        expect($updated->name)->toBe('Updated Name')
            ->and($updated->description)->toBe('Original description');
    });

    it('can set description to null', function () {
        $supplier = Supplier::factory()->create([
            'description' => 'Has description',
        ]);
        $action = app(UpdateSupplier::class);

        $updated = $action->handle($supplier, [
            'description' => null,
        ]);

        expect($updated->description)->toBeNull();
    });
});

describe('DeleteSupplier', function () {
    it('deletes a supplier without items', function () {
        $supplier = Supplier::factory()->create();
        $supplierId = $supplier->id;
        $action = app(DeleteSupplier::class);

        $result = $action->handle($supplier);

        expect($result['success'])->toBeTrue()
            ->and($result['message'])->toBe(__('suppliers.deleted'));

        $this->assertDatabaseMissing('suppliers', ['id' => $supplierId]);
    });

    it('prevents deletion of supplier with items', function () {
        $site = Site::factory()->create();
        $supplier = Supplier::factory()->create();
        Item::factory()->forSite($site)->fromSupplier($supplier)->create();

        $action = app(DeleteSupplier::class);
        $result = $action->handle($supplier);

        expect($result['success'])->toBeFalse()
            ->and($result['message'])->toBe(__('suppliers.cannot_delete_has_items'));

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    });
});
