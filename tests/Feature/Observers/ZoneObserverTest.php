<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use XetaSuite\Models\Material;
use XetaSuite\Models\Site;
use XetaSuite\Models\User;
use XetaSuite\Models\Zone;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->site = Site::factory()->create();
    $this->user = User::factory()->create();
});

it('prevents deletion if zone has materials', function (): void {
    $zone = Zone::factory()->forSite($this->site)->create();

    Material::factory()
        ->forSite($this->site)
        ->forZone($zone)
        ->createdBy($this->user)
        ->create();

    $result = $zone->delete();

    expect($result)->toBeFalse();
    expect(Zone::find($zone->id))->not->toBeNull();
});

it('prevents deletion if zone has child zones', function (): void {
    $parentZone = Zone::factory()->forSite($this->site)->create();
    $childZone = Zone::factory()
        ->forSite($this->site)
        ->withParent($parentZone)
        ->create();

    $result = $parentZone->delete();

    expect($result)->toBeFalse();
    expect(Zone::find($parentZone->id))->not->toBeNull();
});

it('allows deletion if zone has no materials and no children', function (): void {
    $zone = Zone::factory()->forSite($this->site)->create();

    $result = $zone->delete();

    expect($result)->toBeTrue();
    expect(Zone::find($zone->id))->toBeNull();
});

it('allows deletion of child zone without children or materials', function (): void {
    $parentZone = Zone::factory()->forSite($this->site)->create();
    $childZone = Zone::factory()
        ->forSite($this->site)
        ->withParent($parentZone)
        ->create();

    // Delete child first
    $result = $childZone->delete();

    expect($result)->toBeTrue();
    expect(Zone::find($childZone->id))->toBeNull();

    // Now parent can be deleted
    $result = $parentZone->delete();

    expect($result)->toBeTrue();
    expect(Zone::find($parentZone->id))->toBeNull();
});
