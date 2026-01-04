<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use XetaSuite\Models\Item;
use XetaSuite\Models\ItemMovement;
use XetaSuite\Models\Material;
use XetaSuite\Models\Site;
use XetaSuite\Models\User;
use XetaSuite\Models\Zone;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->site = Site::factory()->create();
    $this->zone = Zone::factory()->forSite($this->site)->create();
    $this->user = User::factory()->create();
    $this->item = Item::factory()
        ->forSite($this->site)
        ->createdBy($this->user)
        ->create();
});

it('prevents deletion if item has movements', function (): void {
    ItemMovement::factory()
        ->forItem($this->item)
        ->entry()
        ->createdBy($this->user)
        ->create(['quantity' => 10]);

    $result = $this->item->delete();

    expect($result)->toBeFalse();
    expect(Item::find($this->item->id))->not->toBeNull();
});

it('allows deletion if item has no movements', function (): void {
    $result = $this->item->delete();

    expect($result)->toBeTrue();
    expect(Item::find($this->item->id))->toBeNull();
});

it('detaches materials when item is deleted', function (): void {
    $material = Material::factory()
        ->forSite($this->site)
        ->forZone($this->zone)
        ->createdBy($this->user)
        ->create();

    $this->item->materials()->attach($material);

    expect($this->item->materials()->count())->toBe(1);

    $this->item->delete();

    expect($material->items()->count())->toBe(0);
});

it('detaches recipients when item is deleted', function (): void {
    $recipient = User::factory()->create();
    $this->item->recipients()->attach($recipient);

    expect($this->item->recipients()->count())->toBe(1);

    $this->item->delete();

    // Check pivot table is cleaned
    expect($recipient->fresh())->not->toBeNull(); // User still exists
    $this->assertDatabaseMissing('item_user', [
        'item_id' => $this->item->id,
        'user_id' => $recipient->id,
    ]);
});

it('cascades deletion to prices via database constraint', function (): void {
    $this->item->prices()->create([
        'price' => 100.00,
        'effective_date' => now(),
        'created_by_id' => $this->user->id,
    ]);

    expect($this->item->prices()->count())->toBe(1);

    // Item sans mouvements, donc supprimable
    $this->item->delete();

    $this->assertDatabaseMissing('item_prices', [
        'item_id' => $this->item->id,
    ]);
});
