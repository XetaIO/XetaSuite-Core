<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use XetaSuite\Models\Item;
use XetaSuite\Models\ItemMovement;
use XetaSuite\Models\ItemPrice;
use XetaSuite\Models\Site;
use XetaSuite\Models\Supplier;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->site = Site::factory()->create();
    $this->supplier = Supplier::factory()->create(['name' => 'Acme Corp']);
    $this->item = Item::factory()->forSite($this->site)->fromSupplier($this->supplier)->create();
});

it('saves supplier_name in items when supplier is deleted', function () {
    expect($this->item->supplier_name)->toBeNull();

    $this->supplier->delete();

    $this->item->refresh();
    expect($this->item->supplier_id)->toBeNull();
    expect($this->item->supplier_name)->toBe('Acme Corp');
});

it('saves supplier_name in item_movements when supplier is deleted', function () {
    $movement = ItemMovement::factory()->forItem($this->item)->fromSupplier($this->supplier)->create();

    expect($movement->supplier_name)->toBeNull();

    $this->supplier->delete();

    $movement->refresh();
    expect($movement->supplier_id)->toBeNull();
    expect($movement->supplier_name)->toBe('Acme Corp');
});

it('saves supplier_name in item_prices when supplier is deleted', function () {
    $price = ItemPrice::factory()->forItem($this->item)->fromSupplier($this->supplier)->create();

    expect($price->supplier_name)->toBeNull();

    $this->supplier->delete();

    $price->refresh();
    expect($price->supplier_id)->toBeNull();
    expect($price->supplier_name)->toBe('Acme Corp');
});
