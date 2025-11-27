<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use XetaSuite\Models\Site;
use XetaSuite\Models\Zone;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->site = Site::factory()->create();
});

it('prevents site deletion when it has zones', function () {
    Zone::factory()->forSite($this->site)->create();
    $this->site->refresh();

    expect($this->site->zone_count)->toBe(1);

    $result = $this->site->delete();

    expect($result)->toBeFalse();
    expect(Site::find($this->site->id))->not->toBeNull();
});

it('allows site deletion when it has no zones', function () {

    expect($this->site->zone_count)->toBe(0);

    $result = $this->site->delete();

    expect($result)->toBeTrue();
    expect(Site::find($this->site->id))->toBeNull();
});
