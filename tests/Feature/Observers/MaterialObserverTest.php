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

it('saves material_name in cleanings when material is deleted', function () {
    $site = Site::factory()->create();
    $zone = Zone::factory()->forSite($site)->withAllowMaterial()->create();
    $material = Material::factory()->inSiteAndZone($site, $zone)->create(['name' => 'Machine A']);
    $cleaning = Cleaning::factory()->for($site)->for($material)->create();

    expect($cleaning->material_name)->toBeNull();

    $material->delete();

    $cleaning->refresh();
    expect($cleaning->material_id)->toBeNull();
    expect($cleaning->material_name)->toBe('Machine A');
});

it('saves material_name in incidents when material is deleted', function () {
    $site = Site::factory()->create();
    $zone = Zone::factory()->forSite($site)->withAllowMaterial()->create();
    $material = Material::factory()->inSiteAndZone($site, $zone)->create(['name' => 'Machine A']);
    $incident = Incident::factory()->for($site)->for($material)->create();

    expect($incident->material_name)->toBeNull();

    $material->delete();

    $incident->refresh();
    expect($incident->material_id)->toBeNull();
    expect($incident->material_name)->toBe('Machine A');
});

it('saves material_name in maintenances when material is deleted', function () {
    $site = Site::factory()->create();
    $zone = Zone::factory()->forSite($site)->withAllowMaterial()->create();
    $material = Material::factory()->inSiteAndZone($site, $zone)->create(['name' => 'Machine A']);
    $maintenance = Maintenance::factory()->for($site)->for($material)->create();

    expect($maintenance->material_name)->toBeNull();

    $material->delete();

    $maintenance->refresh();
    expect($maintenance->material_id)->toBeNull();
    expect($maintenance->material_name)->toBe('Machine A');
});
