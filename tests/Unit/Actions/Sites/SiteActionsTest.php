<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use XetaSuite\Actions\Sites\CreateSite;
use XetaSuite\Actions\Sites\DeleteSite;
use XetaSuite\Actions\Sites\UpdateSite;
use XetaSuite\Models\Site;
use XetaSuite\Models\Zone;

uses(RefreshDatabase::class);

// ============================================================================
// CREATE SITE TESTS
// ============================================================================

describe('CreateSite', function (): void {
    it('creates a regular site successfully', function (): void {
        $action = app(CreateSite::class);

        $result = $action->handle([
            'name' => 'New Site',
            'email' => 'site@example.com',
            'city' => 'Paris',
        ]);

        expect($result['success'])->toBeTrue()
            ->and($result['site'])->toBeInstanceOf(Site::class)
            ->and($result['site']->name)->toBe('New Site')
            ->and($result['site']->is_headquarters)->toBeFalse();
    });

    it('creates a headquarters site when none exists', function (): void {
        $action = app(CreateSite::class);

        $result = $action->handle([
            'name' => 'Headquarters',
            'is_headquarters' => true,
        ]);

        expect($result['success'])->toBeTrue()
            ->and($result['site']->is_headquarters)->toBeTrue();
    });

    it('prevents creating a second headquarters', function (): void {
        Site::factory()->create(['is_headquarters' => true]);

        $action = app(CreateSite::class);

        $result = $action->handle([
            'name' => 'Second HQ',
            'is_headquarters' => true,
        ]);

        expect($result['success'])->toBeFalse()
            ->and($result['message'])->toBe(__('sites.headquarters_already_exists'));
    });

    it('sets all provided fields', function (): void {
        $action = app(CreateSite::class);

        $result = $action->handle([
            'name' => 'Complete Site',
            'email' => 'complete@example.com',
            'office_phone' => '0123456789',
            'cell_phone' => '0612345678',
            'address' => '123 Main St',
            'zip_code' => '75001',
            'city' => 'Paris',
            'country' => 'France',
        ]);

        expect($result['success'])->toBeTrue()
            ->and($result['site']->email)->toBe('complete@example.com')
            ->and($result['site']->office_phone)->toBe('0123456789')
            ->and($result['site']->cell_phone)->toBe('0612345678')
            ->and($result['site']->address)->toBe('123 Main St')
            ->and($result['site']->zip_code)->toBe('75001')
            ->and($result['site']->city)->toBe('Paris')
            ->and($result['site']->country)->toBe('France');
    });
});

// ============================================================================
// UPDATE SITE TESTS
// ============================================================================

describe('UpdateSite', function (): void {
    it('updates site successfully', function (): void {
        $site = Site::factory()->create(['name' => 'Old Name']);

        $action = app(UpdateSite::class);

        $result = $action->handle($site, [
            'name' => 'New Name',
            'city' => 'Lyon',
        ]);

        expect($result['success'])->toBeTrue()
            ->and($result['site']->name)->toBe('New Name')
            ->and($result['site']->city)->toBe('Lyon');
    });

    it('prevents setting headquarters when one already exists', function (): void {
        Site::factory()->create(['is_headquarters' => true]);
        $site = Site::factory()->create(['is_headquarters' => false]);

        $action = app(UpdateSite::class);

        $result = $action->handle($site, [
            'is_headquarters' => true,
        ]);

        expect($result['success'])->toBeFalse()
            ->and($result['message'])->toBe(__('sites.headquarters_already_exists'));
    });

    it('prevents removing headquarters status', function (): void {
        $hq = Site::factory()->create(['is_headquarters' => true]);

        $action = app(UpdateSite::class);

        $result = $action->handle($hq, [
            'is_headquarters' => false,
        ]);

        expect($result['success'])->toBeFalse()
            ->and($result['message'])->toBe(__('sites.cannot_remove_headquarters_status'));
    });

    it('allows updating headquarters name without changing status', function (): void {
        $hq = Site::factory()->create(['is_headquarters' => true, 'name' => 'Old HQ']);

        $action = app(UpdateSite::class);

        $result = $action->handle($hq, [
            'name' => 'New HQ Name',
        ]);

        expect($result['success'])->toBeTrue()
            ->and($result['site']->name)->toBe('New HQ Name')
            ->and($result['site']->is_headquarters)->toBeTrue();
    });

    it('allows updating headquarters when is_headquarters stays true', function (): void {
        $hq = Site::factory()->create(['is_headquarters' => true]);

        $action = app(UpdateSite::class);

        $result = $action->handle($hq, [
            'name' => 'Updated HQ',
            'is_headquarters' => true,
        ]);

        expect($result['success'])->toBeTrue()
            ->and($result['site']->is_headquarters)->toBeTrue();
    });
});

// ============================================================================
// DELETE SITE TESTS
// ============================================================================

describe('DeleteSite', function (): void {
    it('deletes a regular site without zones', function (): void {
        $site = Site::factory()->create(['is_headquarters' => false]);

        $action = app(DeleteSite::class);

        $result = $action->handle($site);

        expect($result['success'])->toBeTrue()
            ->and($result['message'])->toBe(__('sites.deleted'));

        $this->assertDatabaseMissing('sites', ['id' => $site->id]);
    });

    it('prevents deleting headquarters site', function (): void {
        $hq = Site::factory()->create(['is_headquarters' => true]);

        $action = app(DeleteSite::class);

        $result = $action->handle($hq);

        expect($result['success'])->toBeFalse()
            ->and($result['message'])->toBe(__('sites.cannot_delete_headquarters'));

        $this->assertDatabaseHas('sites', ['id' => $hq->id]);
    });

    it('prevents deleting site with zones', function (): void {
        $site = Site::factory()->create(['is_headquarters' => false]);
        Zone::factory()->forSite($site)->create();

        $action = app(DeleteSite::class);

        $result = $action->handle($site);

        expect($result['success'])->toBeFalse()
            ->and($result['message'])->toBe(__('sites.cannot_delete_has_zones'));

        $this->assertDatabaseHas('sites', ['id' => $site->id]);
    });
});
