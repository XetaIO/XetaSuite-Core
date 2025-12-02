<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use XetaSuite\Models\Site;
use XetaSuite\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->site = Site::factory()->create(['is_headquarters' => true]);
});

describe('update user locale', function () {
    it('updates the locale for authenticated user', function () {
        $user = User::factory()->create([
            'current_site_id' => $this->site->id,
            'locale' => 'en',
        ]);
        $user->sites()->attach($this->site->id);

        $response = $this->actingAs($user)
            ->patchJson('/api/v1/user/locale', ['locale' => 'fr']);

        $response->assertOk()
            ->assertJsonPath('locale', 'fr');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'locale' => 'fr',
        ]);
    });

    it('sets the app locale for current request', function () {
        $user = User::factory()->create([
            'current_site_id' => $this->site->id,
            'locale' => 'en',
        ]);
        $user->sites()->attach($this->site->id);

        $this->actingAs($user)
            ->patchJson('/api/v1/user/locale', ['locale' => 'fr']);

        expect(app()->getLocale())->toBe('fr');
    });

    it('returns success message', function () {
        $user = User::factory()->create([
            'current_site_id' => $this->site->id,
            'locale' => 'fr',
        ]);
        $user->sites()->attach($this->site->id);

        $response = $this->actingAs($user)
            ->patchJson('/api/v1/user/locale', ['locale' => 'en']);

        $response->assertOk()
            ->assertJsonStructure(['message', 'locale']);
    });

    it('requires locale field', function () {
        $user = User::factory()->create(['current_site_id' => $this->site->id]);
        $user->sites()->attach($this->site->id);

        $response = $this->actingAs($user)
            ->patchJson('/api/v1/user/locale', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['locale']);
    });

    it('validates locale is a string', function () {
        $user = User::factory()->create(['current_site_id' => $this->site->id]);
        $user->sites()->attach($this->site->id);

        $response = $this->actingAs($user)
            ->patchJson('/api/v1/user/locale', ['locale' => 123]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['locale']);
    });

    it('validates locale is in allowed values', function () {
        $user = User::factory()->create(['current_site_id' => $this->site->id]);
        $user->sites()->attach($this->site->id);

        $response = $this->actingAs($user)
            ->patchJson('/api/v1/user/locale', ['locale' => 'de']);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['locale']);
    });

    it('accepts fr locale', function () {
        $user = User::factory()->create([
            'current_site_id' => $this->site->id,
            'locale' => 'en',
        ]);
        $user->sites()->attach($this->site->id);

        $response = $this->actingAs($user)
            ->patchJson('/api/v1/user/locale', ['locale' => 'fr']);

        $response->assertOk()
            ->assertJsonPath('locale', 'fr');
    });

    it('accepts en locale', function () {
        $user = User::factory()->create([
            'current_site_id' => $this->site->id,
            'locale' => 'fr',
        ]);
        $user->sites()->attach($this->site->id);

        $response = $this->actingAs($user)
            ->patchJson('/api/v1/user/locale', ['locale' => 'en']);

        $response->assertOk()
            ->assertJsonPath('locale', 'en');
    });

    it('requires authentication', function () {
        $response = $this->patchJson('/api/v1/user/locale', ['locale' => 'fr']);

        $response->assertUnauthorized();
    });
});
