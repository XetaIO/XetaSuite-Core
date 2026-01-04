<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use XetaSuite\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create([
        'password' => null,
        'password_setup_at' => null,
    ]);
});

describe('Password Setup Verification', function (): void {
    it('returns valid for a correct signed url', function (): void {
        $signedUrl = URL::temporarySignedRoute(
            'auth.password.setup',
            Carbon::now()->addMinutes(60),
            [
                'id' => $this->user->id,
                'hash' => sha1($this->user->email),
            ]
        );

        // Extract query string from signed URL
        $parsedUrl = parse_url($signedUrl);
        $queryString = $parsedUrl['query'] ?? '';

        $response = $this->getJson("/api/v1/auth/setup-password/{$this->user->id}/" . sha1($this->user->email) . "?" . $queryString);

        $response->assertOk()
            ->assertJson([
                'valid' => true,
                'user' => [
                    'id' => $this->user->id,
                    'email' => $this->user->email,
                    'full_name' => $this->user->full_name,
                ],
            ]);
    });

    it('returns 404 for non-existent user', function (): void {
        $signedUrl = URL::temporarySignedRoute(
            'auth.password.setup',
            Carbon::now()->addMinutes(60),
            [
                'id' => 99999,
                'hash' => sha1('test@example.com'),
            ]
        );

        $parsedUrl = parse_url($signedUrl);
        $queryString = $parsedUrl['query'] ?? '';

        $response = $this->getJson("/api/v1/auth/setup-password/99999/" . sha1('test@example.com') . "?" . $queryString);

        $response->assertNotFound()
            ->assertJson(['valid' => false]);
    });

    it('returns 403 for invalid hash', function (): void {
        $signedUrl = URL::temporarySignedRoute(
            'auth.password.setup',
            Carbon::now()->addMinutes(60),
            [
                'id' => $this->user->id,
                'hash' => 'invalid-hash',
            ]
        );

        $parsedUrl = parse_url($signedUrl);
        $queryString = $parsedUrl['query'] ?? '';

        $response = $this->getJson("/api/v1/auth/setup-password/{$this->user->id}/invalid-hash?" . $queryString);

        $response->assertForbidden()
            ->assertJson(['valid' => false]);
    });

    it('returns 403 for expired link', function (): void {
        $signedUrl = URL::temporarySignedRoute(
            'auth.password.setup',
            Carbon::now()->subMinutes(60), // Expired
            [
                'id' => $this->user->id,
                'hash' => sha1($this->user->email),
            ]
        );

        $parsedUrl = parse_url($signedUrl);
        $queryString = $parsedUrl['query'] ?? '';

        $response = $this->getJson("/api/v1/auth/setup-password/{$this->user->id}/" . sha1($this->user->email) . "?" . $queryString);

        $response->assertForbidden()
            ->assertJson(['valid' => false]);
    });

    it('returns 403 if password already setup', function (): void {
        $this->user->forceFill(['password_setup_at' => now()])->save();

        $signedUrl = URL::temporarySignedRoute(
            'auth.password.setup',
            Carbon::now()->addMinutes(60),
            [
                'id' => $this->user->id,
                'hash' => sha1($this->user->email),
            ]
        );

        $parsedUrl = parse_url($signedUrl);
        $queryString = $parsedUrl['query'] ?? '';

        $response = $this->getJson("/api/v1/auth/setup-password/{$this->user->id}/" . sha1($this->user->email) . "?" . $queryString);

        $response->assertForbidden()
            ->assertJson(['valid' => false]);
    });
});

describe('Password Setup Store', function (): void {
    it('sets up password with valid data', function (): void {
        $signedUrl = URL::temporarySignedRoute(
            'auth.password.setup',
            Carbon::now()->addMinutes(60),
            [
                'id' => $this->user->id,
                'hash' => sha1($this->user->email),
            ]
        );

        $parsedUrl = parse_url($signedUrl);
        $queryString = $parsedUrl['query'] ?? '';

        $response = $this->postJson(
            "/api/v1/auth/setup-password/{$this->user->id}/" . sha1($this->user->email) . "?" . $queryString,
            [
                'password' => 'SecurePassword123!',
                'password_confirmation' => 'SecurePassword123!',
            ]
        );

        $response->assertOk()
            ->assertJsonStructure(['message']);

        $this->user->refresh();
        expect($this->user->password)->not->toBeNull();
        expect($this->user->password_setup_at)->not->toBeNull();
    });

    it('fails with password mismatch', function (): void {
        $signedUrl = URL::temporarySignedRoute(
            'auth.password.setup',
            Carbon::now()->addMinutes(60),
            [
                'id' => $this->user->id,
                'hash' => sha1($this->user->email),
            ]
        );

        $parsedUrl = parse_url($signedUrl);
        $queryString = $parsedUrl['query'] ?? '';

        $response = $this->postJson(
            "/api/v1/auth/setup-password/{$this->user->id}/" . sha1($this->user->email) . "?" . $queryString,
            [
                'password' => 'SecurePassword123!',
                'password_confirmation' => 'DifferentPassword123!',
            ]
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });

    it('fails with short password', function (): void {
        $signedUrl = URL::temporarySignedRoute(
            'auth.password.setup',
            Carbon::now()->addMinutes(60),
            [
                'id' => $this->user->id,
                'hash' => sha1($this->user->email),
            ]
        );

        $parsedUrl = parse_url($signedUrl);
        $queryString = $parsedUrl['query'] ?? '';

        $response = $this->postJson(
            "/api/v1/auth/setup-password/{$this->user->id}/" . sha1($this->user->email) . "?" . $queryString,
            [
                'password' => 'short',
                'password_confirmation' => 'short',
            ]
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });

    it('cannot setup password twice', function (): void {
        $signedUrl = URL::temporarySignedRoute(
            'auth.password.setup',
            Carbon::now()->addMinutes(60),
            [
                'id' => $this->user->id,
                'hash' => sha1($this->user->email),
            ]
        );

        $parsedUrl = parse_url($signedUrl);
        $queryString = $parsedUrl['query'] ?? '';

        // First setup - should succeed
        $this->postJson(
            "/api/v1/auth/setup-password/{$this->user->id}/" . sha1($this->user->email) . "?" . $queryString,
            [
                'password' => 'SecurePassword123!',
                'password_confirmation' => 'SecurePassword123!',
            ]
        )->assertOk();

        // Second setup - should fail
        $response = $this->postJson(
            "/api/v1/auth/setup-password/{$this->user->id}/" . sha1($this->user->email) . "?" . $queryString,
            [
                'password' => 'AnotherPassword123!',
                'password_confirmation' => 'AnotherPassword123!',
            ]
        );

        $response->assertForbidden();
    });
});
