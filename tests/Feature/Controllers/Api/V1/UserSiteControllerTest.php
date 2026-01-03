<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use XetaSuite\Models\Site;
use XetaSuite\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create sites
    $this->headquarters = Site::factory()->create(['is_headquarters' => true]);
    $this->regularSite = Site::factory()->create(['is_headquarters' => false]);
    $this->anotherSite = Site::factory()->create(['is_headquarters' => false]);
});

describe('update current site', function () {
    it('updates the current site for authenticated user', function () {
        $user = User::factory()->create(['current_site_id' => $this->headquarters->id]);
        $user->sites()->attach([$this->headquarters->id, $this->regularSite->id]);

        $response = $this->actingAs($user)
            ->patchJson('/api/v1/user/site', ['site_id' => $this->regularSite->id]);

        $response->assertOk()
            ->assertJsonPath('user.current_site_id', $this->regularSite->id);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'current_site_id' => $this->regularSite->id,
        ]);
    });

    it('updates session with new site info', function () {
        $user = User::factory()->create(['current_site_id' => $this->regularSite->id]);
        $user->sites()->attach([$this->headquarters->id, $this->regularSite->id]);

        $response = $this->actingAs($user)
            ->patchJson('/api/v1/user/site', ['site_id' => $this->headquarters->id]);

        $response->assertOk();

        // Session should be updated
        expect(session('current_site_id'))->toBe($this->headquarters->id)
            ->and(session('is_on_headquarters'))->toBeTrue();
    });

    it('requires site_id field', function () {
        $user = User::factory()->create();
        $user->sites()->attach([$this->headquarters->id]);

        $response = $this->actingAs($user)
            ->patchJson('/api/v1/user/site', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['site_id']);
    });

    it('validates site_id is an integer', function () {
        $user = User::factory()->create();
        $user->sites()->attach([$this->headquarters->id]);

        $response = $this->actingAs($user)
            ->patchJson('/api/v1/user/site', ['site_id' => 'not-an-integer']);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['site_id']);
    });

    it('validates site exists', function () {
        $user = User::factory()->create();
        $user->sites()->attach([$this->headquarters->id]);

        $response = $this->actingAs($user)
            ->patchJson('/api/v1/user/site', ['site_id' => 99999]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['site_id']);
    });

    it('prevents switching to site user does not belong to', function () {
        $user = User::factory()->create();
        $user->sites()->attach([$this->headquarters->id]); // Only attached to headquarters

        $response = $this->actingAs($user)
            ->patchJson('/api/v1/user/site', ['site_id' => $this->regularSite->id]); // Not attached

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['site_id']);
    });

    it('returns updated user data', function () {
        $user = User::factory()->create(['current_site_id' => $this->headquarters->id]);
        $user->sites()->attach([$this->headquarters->id, $this->regularSite->id]);

        $response = $this->actingAs($user)
            ->patchJson('/api/v1/user/site', ['site_id' => $this->regularSite->id]);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'username',
                    'first_name',
                    'last_name',
                    'full_name',
                    'current_site_id',
                    'email',
                    'locale',
                    'roles',
                    'permissions',
                    'sites',
                ],
            ]);
    });

    it('requires authentication', function () {
        $response = $this->patchJson('/api/v1/user/site', ['site_id' => $this->headquarters->id]);

        $response->assertUnauthorized();
    });
});
