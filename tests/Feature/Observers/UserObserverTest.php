<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use XetaSuite\Models\Cleaning;
use XetaSuite\Models\Company;
use XetaSuite\Models\Incident;
use XetaSuite\Models\Item;
use XetaSuite\Models\Maintenance;
use XetaSuite\Models\Site;
use XetaSuite\Models\User;

uses(RefreshDatabase::class);

it('saves created_by_name in items when user is force deleted', function (): void {
    $site = Site::factory()->create();
    $user = User::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
    $item = Item::factory()->for($site)->create(['created_by_id' => $user->id]);

    expect($item->created_by_name)->toBeNull();

    $user->forceDelete();

    $item->refresh();
    expect($item->created_by_id)->toBeNull();
    expect($item->created_by_name)->toBe('John Doe');
});

it('saves created_by_name in cleanings when user is force deleted', function (): void {
    $site = Site::factory()->create();
    $user = User::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
    $cleaning = Cleaning::factory()->for($site)->create(['created_by_id' => $user->id]);

    expect($cleaning->created_by_name)->toBeNull();

    $user->forceDelete();

    $cleaning->refresh();
    expect($cleaning->created_by_id)->toBeNull();
    expect($cleaning->created_by_name)->toBe('John Doe');
});

it('saves created_by_name in maintenances when user is force deleted', function (): void {
    $site = Site::factory()->create();
    $user = User::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
    $maintenance = Maintenance::factory()->for($site)->create(['created_by_id' => $user->id]);

    expect($maintenance->created_by_name)->toBeNull();

    $user->forceDelete();

    $maintenance->refresh();
    expect($maintenance->created_by_id)->toBeNull();
    expect($maintenance->created_by_name)->toBe('John Doe');
});

it('saves reported_by_name in incidents when user is force deleted', function (): void {
    $site = Site::factory()->create();
    $user = User::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
    $incident = Incident::factory()->for($site)->create(['reported_by_id' => $user->id]);

    expect($incident->reported_by_name)->toBeNull();

    $user->forceDelete();

    $incident->refresh();
    expect($incident->reported_by_id)->toBeNull();
    expect($incident->reported_by_name)->toBe('John Doe');
});

it('saves created_by_name in companies when user is force deleted', function (): void {
    $user = User::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
    $company = Company::factory()->create(['created_by_id' => $user->id]);

    expect($company->created_by_name)->toBeNull();

    $user->forceDelete();

    $company->refresh();
    expect($company->created_by_id)->toBeNull();
    expect($company->created_by_name)->toBe('John Doe');
});

it('invalidates avatar cache when first_name changes', function (): void {
    $user = User::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
    $cacheKey = $user->getAvatarCacheKey();

    // Access avatar to populate cache
    $originalAvatar = $user->avatar;
    expect(Cache::has($cacheKey))->toBeTrue();

    // Update first_name
    $user->update(['first_name' => 'Jane']);

    // Cache should be invalidated
    expect(Cache::has($cacheKey))->toBeFalse();
});

it('invalidates avatar cache when last_name changes', function (): void {
    $user = User::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
    $cacheKey = $user->getAvatarCacheKey();

    // Access avatar to populate cache
    $user->avatar;
    expect(Cache::has($cacheKey))->toBeTrue();

    // Update last_name
    $user->update(['last_name' => 'Smith']);

    // Cache should be invalidated
    expect(Cache::has($cacheKey))->toBeFalse();
});

it('does not invalidate avatar cache when unrelated field changes', function (): void {
    $user = User::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
    $cacheKey = $user->getAvatarCacheKey();

    // Access avatar to populate cache
    $user->avatar;
    expect(Cache::has($cacheKey))->toBeTrue();

    // Update unrelated field
    $user->update(['email' => 'newemail@example.com']);

    // Cache should still exist
    expect(Cache::has($cacheKey))->toBeTrue();
});
