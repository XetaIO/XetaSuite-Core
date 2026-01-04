<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use XetaSuite\Actions\Materials\CreateMaterial;
use XetaSuite\Actions\Materials\DeleteMaterial;
use XetaSuite\Actions\Materials\UpdateMaterial;
use XetaSuite\Enums\Materials\CleaningFrequency;
use XetaSuite\Models\Item;
use XetaSuite\Models\Material;
use XetaSuite\Models\Site;
use XetaSuite\Models\User;
use XetaSuite\Models\Zone;

uses(RefreshDatabase::class);

// ============================================================================
// CREATE MATERIAL TESTS
// ============================================================================

describe('CreateMaterial', function (): void {
    it('creates a material successfully', function (): void {
        $site = Site::factory()->create();
        $zone = Zone::factory()->forSite($site)->withAllowMaterial()->create();
        $user = User::factory()->create();
        $action = app(CreateMaterial::class);

        $material = $action->handle($user, [
            'zone_id' => $zone->id,
            'name' => 'New Material',
        ]);

        expect($material)->toBeInstanceOf(Material::class)
            ->and($material->name)->toBe('New Material')
            ->and($material->zone_id)->toBe($zone->id)
            ->and($material->site_id)->toBe($site->id)
            ->and($material->created_by_id)->toBe($user->id)
            ->and($material->created_by_name)->toBe($user->full_name);
    });

    it('creates a material with description', function (): void {
        $site = Site::factory()->create();
        $zone = Zone::factory()->forSite($site)->withAllowMaterial()->create();
        $user = User::factory()->create();
        $action = app(CreateMaterial::class);

        $material = $action->handle($user, [
            'zone_id' => $zone->id,
            'name' => 'Material with Description',
            'description' => 'This is a detailed description',
        ]);

        expect($material->description)->toBe('This is a detailed description');
    });

    it('creates a material with cleaning alert enabled', function (): void {
        $site = Site::factory()->create();
        $zone = Zone::factory()->forSite($site)->withAllowMaterial()->create();
        $user = User::factory()->create();
        $action = app(CreateMaterial::class);

        $material = $action->handle($user, [
            'zone_id' => $zone->id,
            'name' => 'Material with Alert',
            'cleaning_alert' => true,
            'cleaning_alert_email' => true,
            'cleaning_alert_frequency_repeatedly' => 2,
            'cleaning_alert_frequency_type' => CleaningFrequency::WEEKLY->value,
        ]);

        expect($material->cleaning_alert)->toBeTrue()
            ->and($material->cleaning_alert_email)->toBeTrue()
            ->and($material->cleaning_alert_frequency_repeatedly)->toBe(2)
            ->and($material->cleaning_alert_frequency_type)->toBe(CleaningFrequency::WEEKLY);
    });

    it('creates a material with recipients when cleaning alert is enabled', function (): void {
        $site = Site::factory()->create();
        $zone = Zone::factory()->forSite($site)->withAllowMaterial()->create();
        $user = User::factory()->create();
        $recipient1 = User::factory()->create();
        $recipient2 = User::factory()->create();
        $action = app(CreateMaterial::class);

        $material = $action->handle($user, [
            'zone_id' => $zone->id,
            'name' => 'Material with Recipients',
            'cleaning_alert' => true,
            'recipients' => [$recipient1->id, $recipient2->id],
        ]);

        expect($material->recipients)->toHaveCount(2);
    });

    it('does not attach recipients when cleaning alert is disabled', function (): void {
        $site = Site::factory()->create();
        $zone = Zone::factory()->forSite($site)->withAllowMaterial()->create();
        $user = User::factory()->create();
        $recipient = User::factory()->create();
        $action = app(CreateMaterial::class);

        $material = $action->handle($user, [
            'zone_id' => $zone->id,
            'name' => 'Material without Alert',
            'cleaning_alert' => false,
            'recipients' => [$recipient->id],
        ]);

        expect($material->recipients)->toHaveCount(0);
    });

    it('loads zone and creator relations after creation', function (): void {
        $site = Site::factory()->create();
        $zone = Zone::factory()->forSite($site)->withAllowMaterial()->create();
        $user = User::factory()->create();
        $action = app(CreateMaterial::class);

        $material = $action->handle($user, [
            'zone_id' => $zone->id,
            'name' => 'Material',
        ]);

        expect($material->relationLoaded('zone'))->toBeTrue()
            ->and($material->relationLoaded('creator'))->toBeTrue()
            ->and($material->relationLoaded('recipients'))->toBeTrue();
    });
});

// ============================================================================
// UPDATE MATERIAL TESTS
// ============================================================================

describe('UpdateMaterial', function (): void {
    it('updates material name', function (): void {
        $site = Site::factory()->create();
        $zone = Zone::factory()->forSite($site)->withAllowMaterial()->create();
        $material = Material::factory()->forZone($zone)->create(['name' => 'Old Name']);
        $action = app(UpdateMaterial::class);

        $updatedMaterial = $action->handle($material, [
            'name' => 'New Name',
        ]);

        expect($updatedMaterial->name)->toBe('New Name');
    });

    it('updates material zone', function (): void {
        $site = Site::factory()->create();
        $oldZone = Zone::factory()->forSite($site)->withAllowMaterial()->create();
        $newZone = Zone::factory()->forSite($site)->withAllowMaterial()->create();
        $material = Material::factory()->forZone($oldZone)->create();
        $action = app(UpdateMaterial::class);

        $updatedMaterial = $action->handle($material, [
            'zone_id' => $newZone->id,
        ]);

        expect($updatedMaterial->zone_id)->toBe($newZone->id);
    });

    it('updates description to null', function (): void {
        $site = Site::factory()->create();
        $zone = Zone::factory()->forSite($site)->withAllowMaterial()->create();
        $material = Material::factory()->forZone($zone)->create(['description' => 'Old description']);
        $action = app(UpdateMaterial::class);

        $updatedMaterial = $action->handle($material, [
            'description' => null,
        ]);

        expect($updatedMaterial->description)->toBeNull();
    });

    it('resets cleaning fields when cleaning_alert is disabled', function (): void {
        $site = Site::factory()->create();
        $zone = Zone::factory()->forSite($site)->withAllowMaterial()->create();
        $recipient = User::factory()->create();
        $material = Material::factory()->forZone($zone)->create([
            'cleaning_alert' => true,
            'cleaning_alert_email' => true,
            'cleaning_alert_frequency_repeatedly' => 5,
            'cleaning_alert_frequency_type' => CleaningFrequency::WEEKLY->value,
        ]);
        $material->recipients()->attach($recipient->id);
        $action = app(UpdateMaterial::class);

        $updatedMaterial = $action->handle($material, [
            'cleaning_alert' => false,
        ]);

        expect($updatedMaterial->cleaning_alert)->toBeFalse()
            ->and($updatedMaterial->cleaning_alert_email)->toBeFalse()
            ->and($updatedMaterial->cleaning_alert_frequency_repeatedly)->toBe(0)
            ->and($updatedMaterial->cleaning_alert_frequency_type)->toBe(CleaningFrequency::DAILY)
            ->and($updatedMaterial->recipients)->toHaveCount(0);
    });

    it('syncs recipients when cleaning alert is enabled', function (): void {
        $site = Site::factory()->create();
        $zone = Zone::factory()->forSite($site)->withAllowMaterial()->create();
        $oldRecipient = User::factory()->create();
        $newRecipient = User::factory()->create();
        $material = Material::factory()->forZone($zone)->create(['cleaning_alert' => true]);
        $material->recipients()->attach($oldRecipient->id);
        $action = app(UpdateMaterial::class);

        $updatedMaterial = $action->handle($material, [
            'recipients' => [$newRecipient->id],
        ]);

        expect($updatedMaterial->recipients)->toHaveCount(1)
            ->and($updatedMaterial->recipients->first()->id)->toBe($newRecipient->id);
    });

    it('detaches recipients when cleaning alert becomes disabled', function (): void {
        $site = Site::factory()->create();
        $zone = Zone::factory()->forSite($site)->withAllowMaterial()->create();
        $recipient = User::factory()->create();
        $material = Material::factory()->forZone($zone)->create(['cleaning_alert' => true]);
        $material->recipients()->attach($recipient->id);
        $action = app(UpdateMaterial::class);

        $updatedMaterial = $action->handle($material, [
            'cleaning_alert' => false,
            'recipients' => [$recipient->id],
        ]);

        expect($updatedMaterial->recipients)->toHaveCount(0);
    });

    it('returns fresh model with relations after update', function (): void {
        $site = Site::factory()->create();
        $zone = Zone::factory()->forSite($site)->withAllowMaterial()->create();
        $material = Material::factory()->forZone($zone)->create();
        $action = app(UpdateMaterial::class);

        $updatedMaterial = $action->handle($material, ['name' => 'Updated']);

        expect($updatedMaterial->relationLoaded('zone'))->toBeTrue()
            ->and($updatedMaterial->relationLoaded('creator'))->toBeTrue()
            ->and($updatedMaterial->relationLoaded('recipients'))->toBeTrue();
    });
});

// ============================================================================
// DELETE MATERIAL TESTS
// ============================================================================

describe('DeleteMaterial', function (): void {
    it('deletes a material successfully', function (): void {
        $site = Site::factory()->create();
        $zone = Zone::factory()->forSite($site)->withAllowMaterial()->create();
        $material = Material::factory()->forZone($zone)->create();
        $action = app(DeleteMaterial::class);

        $result = $action->handle($material);

        expect($result)->toBeTrue()
            ->and(Material::find($material->id))->toBeNull();
    });

    it('deletes material with recipients (detaches via observer)', function (): void {
        $site = Site::factory()->create();
        $zone = Zone::factory()->forSite($site)->withAllowMaterial()->create();
        $recipient = User::factory()->create();
        $material = Material::factory()->forZone($zone)->create();
        $material->recipients()->attach($recipient->id);
        $action = app(DeleteMaterial::class);

        $result = $action->handle($material);

        expect($result)->toBeTrue()
            ->and(Material::find($material->id))->toBeNull();
    });

    it('deletes material with items attached (observer detaches)', function (): void {
        $site = Site::factory()->create();
        $zone = Zone::factory()->forSite($site)->withAllowMaterial()->create();
        $material = Material::factory()->forZone($zone)->create();
        $item = Item::factory()->forSite($site)->create();
        $item->materials()->attach($material->id);
        $action = app(DeleteMaterial::class);

        $result = $action->handle($material);

        expect($result)->toBeTrue()
            ->and(Material::find($material->id))->toBeNull()
            ->and(Item::find($item->id))->not->toBeNull()
            ->and($item->fresh()->materials)->toHaveCount(0);
    });
});
