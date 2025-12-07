<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use XetaSuite\Actions\Zones\CreateZone;
use XetaSuite\Actions\Zones\DeleteZone;
use XetaSuite\Actions\Zones\UpdateZone;
use XetaSuite\Models\Material;
use XetaSuite\Models\Site;
use XetaSuite\Models\Zone;

uses(RefreshDatabase::class);

// ============================================================================
// CREATE ZONE TESTS
// ============================================================================

describe('CreateZone', function () {
    it('creates a zone successfully', function () {
        $site = Site::factory()->create();
        $action = app(CreateZone::class);

        $zone = $action->handle([
            'site_id' => $site->id,
            'name' => 'New Zone',
        ]);

        expect($zone)->toBeInstanceOf(Zone::class)
            ->and($zone->name)->toBe('New Zone')
            ->and($zone->site_id)->toBe($site->id)
            ->and($zone->parent_id)->toBeNull()
            ->and($zone->allow_material)->toBeFalse();
    });

    it('creates a zone with parent', function () {
        $site = Site::factory()->create();
        $parentZone = Zone::factory()->forSite($site)->create();
        $action = app(CreateZone::class);

        $zone = $action->handle([
            'site_id' => $site->id,
            'name' => 'Child Zone',
            'parent_id' => $parentZone->id,
        ]);

        expect($zone->parent_id)->toBe($parentZone->id)
            ->and($zone->parent->id)->toBe($parentZone->id);
    });

    it('creates a zone with allow_material enabled', function () {
        $site = Site::factory()->create();
        $action = app(CreateZone::class);

        $zone = $action->handle([
            'site_id' => $site->id,
            'name' => 'Material Zone',
            'allow_material' => true,
        ]);

        expect($zone->allow_material)->toBeTrue();
    });

    it('creates nested zones (multi-level hierarchy)', function () {
        $site = Site::factory()->create();
        $action = app(CreateZone::class);

        $level1 = $action->handle(['site_id' => $site->id, 'name' => 'Level 1']);
        $level2 = $action->handle(['site_id' => $site->id, 'name' => 'Level 2', 'parent_id' => $level1->id]);
        $level3 = $action->handle(['site_id' => $site->id, 'name' => 'Level 3', 'parent_id' => $level2->id]);

        expect($level1->children)->toHaveCount(1)
            ->and($level2->parent->id)->toBe($level1->id)
            ->and($level3->parent->id)->toBe($level2->id);
    });
});

// ============================================================================
// UPDATE ZONE TESTS
// ============================================================================

describe('UpdateZone', function () {
    it('updates zone name', function () {
        $site = Site::factory()->create();
        $zone = Zone::factory()->forSite($site)->create(['name' => 'Old Name']);
        $action = app(UpdateZone::class);

        $updatedZone = $action->handle($zone, [
            'name' => 'New Name',
        ]);

        expect($updatedZone->name)->toBe('New Name');
    });

    it('updates zone parent', function () {
        $site = Site::factory()->create();
        $zone = Zone::factory()->forSite($site)->create();
        $newParent = Zone::factory()->forSite($site)->create();
        $action = app(UpdateZone::class);

        $updatedZone = $action->handle($zone, [
            'parent_id' => $newParent->id,
        ]);

        expect($updatedZone->parent_id)->toBe($newParent->id);
    });

    it('updates zone allow_material', function () {
        $site = Site::factory()->create();
        $zone = Zone::factory()->forSite($site)->create(['allow_material' => false]);
        $action = app(UpdateZone::class);

        $updatedZone = $action->handle($zone, [
            'allow_material' => true,
        ]);

        expect($updatedZone->allow_material)->toBeTrue();
    });

    it('can remove parent from zone', function () {
        $site = Site::factory()->create();
        $parent = Zone::factory()->forSite($site)->create();
        $zone = Zone::factory()->forSite($site)->withParent($parent)->create();
        $action = app(UpdateZone::class);

        $updatedZone = $action->handle($zone, [
            'parent_id' => null,
        ]);

        expect($updatedZone->parent_id)->toBeNull();
    });

    it('returns fresh model after update', function () {
        $site = Site::factory()->create();
        $zone = Zone::factory()->forSite($site)->create(['name' => 'Original']);
        $action = app(UpdateZone::class);

        $updatedZone = $action->handle($zone, ['name' => 'Updated']);

        expect($updatedZone)->not->toBe($zone)
            ->and($updatedZone->name)->toBe('Updated');
    });
});

// ============================================================================
// DELETE ZONE TESTS
// ============================================================================

describe('DeleteZone', function () {
    it('deletes an empty zone successfully', function () {
        $site = Site::factory()->create();
        $zone = Zone::factory()->forSite($site)->create();
        $action = app(DeleteZone::class);

        $result = $action->handle($zone);

        expect($result['success'])->toBeTrue()
            ->and(Zone::find($zone->id))->toBeNull();
    });

    it('cannot delete zone with children', function () {
        $site = Site::factory()->create();
        $parent = Zone::factory()->forSite($site)->create();
        Zone::factory()->forSite($site)->withParent($parent)->create();
        $action = app(DeleteZone::class);

        $result = $action->handle($parent);

        expect($result['success'])->toBeFalse()
            ->and($result['message'])->toBe(__('zones.cannot_delete_has_children'))
            ->and(Zone::find($parent->id))->not->toBeNull();
    });

    it('cannot delete zone with materials', function () {
        $site = Site::factory()->create();
        $zone = Zone::factory()->forSite($site)->withAllowMaterial()->create();
        Material::factory()->forZone($zone)->create();
        $action = app(DeleteZone::class);

        $result = $action->handle($zone);

        expect($result['success'])->toBeFalse()
            ->and($result['message'])->toBe(__('zones.cannot_delete_has_materials'))
            ->and(Zone::find($zone->id))->not->toBeNull();
    });

    it('can delete zone after children are removed', function () {
        $site = Site::factory()->create();
        $parent = Zone::factory()->forSite($site)->create();
        $child = Zone::factory()->forSite($site)->withParent($parent)->create();
        $action = app(DeleteZone::class);

        // First, delete the child
        $action->handle($child);

        // Now parent can be deleted
        $result = $action->handle($parent->fresh());

        expect($result['success'])->toBeTrue()
            ->and(Zone::find($parent->id))->toBeNull();
    });
});
