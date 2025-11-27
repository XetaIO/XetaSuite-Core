<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use XetaSuite\Models\Site;
use XetaSuite\Models\Zone;

uses(RefreshDatabase::class);

it('prevents site deletion when it has zones', function () {
    $site = Site::factory()->create();
    Zone::factory()->forSite($site)->create();
    $site->refresh();

    expect($site->zone_count)->toBe(1);

    $result = $site->delete();

    expect($result)->toBeFalse();
    expect(Site::find($site->id))->not->toBeNull();
});

it('allows site deletion when it has no zones', function () {
    $site = Site::factory()->create();

    expect($site->zone_count)->toBe(0);

    $result = $site->delete();

    expect($result)->toBeTrue();
    expect(Site::find($site->id))->toBeNull();
});
