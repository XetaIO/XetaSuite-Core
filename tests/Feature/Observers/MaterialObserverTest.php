<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use XetaSuite\Models\Cleaning;
use XetaSuite\Models\Incident;
use XetaSuite\Models\Maintenance;
use XetaSuite\Models\Material;
use XetaSuite\Models\Site;
use XetaSuite\Models\Zone;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->site = Site::factory()->create();
    $this->zone = Zone::factory()->forSite($this->site)->withAllowMaterial()->create();
    $this->material = Material::factory()
        ->forSite($this->site)
        ->forZone($this->zone)
        ->create(['name' => 'Machine A']);
});

it('saves material_name in cleanings when material is deleted', function (): void {
    $cleaning = Cleaning::factory()->forSite($this->site)->forMaterial($this->material)->create();

    expect($cleaning->material_name)->toBeNull();

    $this->material->delete();

    $cleaning->refresh();
    expect($cleaning->material_id)->toBeNull();
    expect($cleaning->material_name)->toBe('Machine A');
});

it('saves material_name in incidents when material is deleted', function (): void {
    $incident = Incident::factory()->forSite($this->site)->forMaterial($this->material)->create();

    expect($incident->material_name)->toBeNull();

    $this->material->delete();

    $incident->refresh();
    expect($incident->material_id)->toBeNull();
    expect($incident->material_name)->toBe('Machine A');
});

it('saves material_name in maintenances when material is deleted', function (): void {
    $maintenance = Maintenance::factory()->forSite($this->site)->forMaterial($this->material)->create();

    expect($maintenance->material_name)->toBeNull();

    $this->material->delete();

    $maintenance->refresh();
    expect($maintenance->material_id)->toBeNull();
    expect($maintenance->material_name)->toBe('Machine A');
});
