<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use XetaSuite\Models\Company;
use XetaSuite\Models\Incident;
use XetaSuite\Models\Item;
use XetaSuite\Models\ItemMovement;
use XetaSuite\Models\Maintenance;
use XetaSuite\Models\Site;
use XetaSuite\Models\User;

uses(RefreshDatabase::class);

it('detaches companies when maintenance is deleted', function (): void {
    $site = Site::factory()->create();
    $maintenance = Maintenance::factory()->for($site)->create();
    $company = Company::factory()->create();

    $maintenance->companies()->attach($company);

    expect($maintenance->companies)->toHaveCount(1);

    $maintenance->delete();

    expect($company->fresh()->maintenances)->toHaveCount(0);
});

it('detaches operators when maintenance is deleted', function (): void {
    $site = Site::factory()->create();
    $user = User::factory()->create();
    $maintenance = Maintenance::factory()->for($site)->createdBy($user)->create();

    $maintenance->operators()->attach($user);

    $user->refresh();

    expect($maintenance->operators)->toHaveCount(1);
    expect($user->maintenance_count)->toBe(1);

    $maintenance->delete();

    $user->refresh();

    expect($user->maintenancesOperators)->toHaveCount(0);
    expect($user->maintenance_count)->toBe(0);
});

it('sets maintenance_id to null on incidents when maintenance is deleted', function (): void {
    $site = Site::factory()->create();
    $maintenance = Maintenance::factory()->for($site)->create();
    $incident = Incident::factory()->for($site)->for($maintenance)->create();

    expect($incident->maintenance_id)->toBe($maintenance->id);

    $maintenance->delete();

    $incident->refresh();
    expect($incident->maintenance_id)->toBeNull();
});

it('sets movable to null on item movements when maintenance is deleted', function (): void {
    $site = Site::factory()->create();
    $maintenance = Maintenance::factory()->for($site)->create();
    $item = Item::factory()->for($site)->create();
    $movement = ItemMovement::factory()->for($item)->create([
        'type' => 'exit',
        'movable_type' => Maintenance::class,
        'movable_id' => $maintenance->id,
    ]);

    expect($movement->movable_type)->toBe(Maintenance::class);
    expect($movement->movable_id)->toBe($maintenance->id);

    $maintenance->delete();

    $movement->refresh();
    expect($movement->movable_type)->toBeNull();
    expect($movement->movable_id)->toBeNull();
});
