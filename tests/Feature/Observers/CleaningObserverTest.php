<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use XetaSuite\Models\Cleaning;
use XetaSuite\Models\Material;
use XetaSuite\Models\Site;
use XetaSuite\Models\User;
use XetaSuite\Models\Zone;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->site = Site::factory()->create();
    $this->zone = Zone::factory()->forSite($this->site)->create();
    $this->user = User::factory()->create();
    $this->material = Material::factory()
        ->forSite($this->site)
        ->forZone($this->zone)
        ->createdBy($this->user)
        ->create(['last_cleaning_at' => null]);
});

it('updates material last_cleaning_at when cleaning is created', function () {
    expect($this->material->last_cleaning_at)->toBeNull();

    Cleaning::factory()
        ->forSite($this->site)
        ->forMaterial($this->material)
        ->createdBy($this->user)
        ->create();

    $this->material->refresh();

    expect($this->material->last_cleaning_at)->not->toBeNull();
    expect($this->material->last_cleaning_at->toDateString())->toBe(now()->toDateString());
});

it('does not update other materials last_cleaning_at', function () {
    $otherMaterial = Material::factory()
        ->forSite($this->site)
        ->forZone($this->zone)
        ->createdBy($this->user)
        ->create(['last_cleaning_at' => null]);

    Cleaning::factory()
        ->forSite($this->site)
        ->forMaterial($this->material)
        ->createdBy($this->user)
        ->create();

    $otherMaterial->refresh();

    expect($otherMaterial->last_cleaning_at)->toBeNull();
});
