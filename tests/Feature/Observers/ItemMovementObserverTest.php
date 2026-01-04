<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use XetaSuite\Models\Item;
use XetaSuite\Models\ItemMovement;
use XetaSuite\Models\Site;
use XetaSuite\Models\User;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->site = Site::factory()->create();
    $this->user = User::factory()->create();
    $this->item = Item::factory()
        ->forSite($this->site)
        ->createdBy($this->user)
        ->create();
});

it('increments item_entry_total when entry movement is created', function (): void {
    expect($this->item->item_entry_total)->toBe(0);

    ItemMovement::factory()
        ->forItem($this->item)
        ->entry()
        ->createdBy($this->user)
        ->create(['quantity' => 10]);

    $this->item->refresh();

    expect($this->item->item_entry_total)->toBe(10);
});

it('increments item_exit_total when exit movement is created', function (): void {
    expect($this->item->item_exit_total)->toBe(0);

    ItemMovement::factory()
        ->forItem($this->item)
        ->exit()
        ->createdBy($this->user)
        ->create(['quantity' => 5]);

    $this->item->refresh();

    expect($this->item->item_exit_total)->toBe(5);
});

it('decrements item_entry_total when entry movement is deleted', function (): void {
    // Create an entry movement first
    $movement = ItemMovement::factory()
        ->forItem($this->item)
        ->entry()
        ->createdBy($this->user)
        ->create(['quantity' => 10]);

    $this->item->refresh();
    expect($this->item->item_entry_total)->toBe(10);

    $movement->delete();

    $this->item->refresh();

    expect($this->item->item_entry_total)->toBe(0);
});

it('decrements item_exit_total when exit movement is deleted', function (): void {
    // Create an exit movement first
    $movement = ItemMovement::factory()
        ->forItem($this->item)
        ->exit()
        ->createdBy($this->user)
        ->create(['quantity' => 5]);

    $this->item->refresh();
    expect($this->item->item_exit_total)->toBe(5);

    $movement->delete();

    $this->item->refresh();

    expect($this->item->item_exit_total)->toBe(0);
});

it('handles multiple movements correctly', function (): void {
    ItemMovement::factory()
        ->forItem($this->item)
        ->entry()
        ->createdBy($this->user)
        ->create(['quantity' => 100]);

    ItemMovement::factory()
        ->forItem($this->item)
        ->entry()
        ->createdBy($this->user)
        ->create(['quantity' => 50]);

    ItemMovement::factory()
        ->forItem($this->item)
        ->exit()
        ->createdBy($this->user)
        ->create(['quantity' => 30]);

    $this->item->refresh();

    expect($this->item->item_entry_total)->toBe(150);
    expect($this->item->item_exit_total)->toBe(30);
    expect($this->item->item_entry_count)->toBe(2);
    expect($this->item->item_exit_count)->toBe(1);
});
