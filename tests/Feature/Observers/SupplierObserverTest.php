<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use XetaSuite\Models\Item;
use XetaSuite\Models\ItemMovement;
use XetaSuite\Models\ItemPrice;
use XetaSuite\Models\Site;
use XetaSuite\Models\Supplier;

uses(RefreshDatabase::class);

it('saves supplier_name in items when supplier is deleted', function () {
    $site = Site::factory()->create();
    $supplier = Supplier::factory()->create(['name' => 'Acme Corp']);
    $item = Item::factory()->for($site)->for($supplier)->create();

    expect($item->supplier_name)->toBeNull();

    $supplier->delete();

    $item->refresh();
    expect($item->supplier_id)->toBeNull();
    expect($item->supplier_name)->toBe('Acme Corp');
});

it('saves supplier_name in item_movements when supplier is deleted', function () {
    $site = Site::factory()->create();
    $supplier = Supplier::factory()->create(['name' => 'Acme Corp']);
    $item = Item::factory()->for($site)->create();
    $movement = ItemMovement::factory()->for($item)->for($supplier)->create();

    expect($movement->supplier_name)->toBeNull();

    $supplier->delete();

    $movement->refresh();
    expect($movement->supplier_id)->toBeNull();
    expect($movement->supplier_name)->toBe('Acme Corp');
});

it('saves supplier_name in item_prices when supplier is deleted', function () {
    $site = Site::factory()->create();
    $supplier = Supplier::factory()->create(['name' => 'Acme Corp']);
    $item = Item::factory()->for($site)->create();
    $price = ItemPrice::factory()->for($item)->for($supplier)->create();

    expect($price->supplier_name)->toBeNull();

    $supplier->delete();

    $price->refresh();
    expect($price->supplier_id)->toBeNull();
    expect($price->supplier_name)->toBe('Acme Corp');
});
