<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Notification;
use XetaSuite\Models\User;
use XetaSuite\Notifications\Auth\RegisteredNotification;

beforeEach(function (): void {
    // Disable reCAPTCHA for most tests - we test reCAPTCHA validation separately
    config([
        'services.recaptcha.secret_key' => 'test-secret-key',
        'services.recaptcha.min_score' => 0.5,
        'services.recaptcha.enabled' => false,
    ]);
});

describe('Resend Setup Password', function (): void {
    it('returns success for valid email with pending password setup', function (): void {
        Notification::fake();

        $user = User::factory()->create([
            'password' => null,
            'password_setup_at' => null,
        ]);

        $response = $this->postJson('/api/v1/auth/setup-password-resend', [
            'email' => $user->email,
            'recaptcha_token' => 'valid-token',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['message']);

        Notification::assertSentTo($user, RegisteredNotification::class);
    });

    it('returns success but does not send notification for non-existent email', function (): void {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/setup-password-resend', [
            'email' => 'nonexistent@example.com',
            'recaptcha_token' => 'valid-token',
        ]);

        // Always returns success to prevent email enumeration
        $response->assertOk()
            ->assertJsonStructure(['message']);

        Notification::assertNothingSent();
    });

    it('returns success but does not send notification for user with password already setup', function (): void {
        // Create user with password already setup (default factory state)
        $user = User::factory()->create();

        // Ensure password_setup_at is set
        expect($user->hasSetupPassword())->toBeTrue();

        // Fake notifications AFTER user creation to avoid catching creation notification
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/setup-password-resend', [
            'email' => $user->email,
            'recaptcha_token' => 'valid-token',
        ]);

        // Always returns success to prevent email enumeration
        $response->assertOk()
            ->assertJsonStructure(['message']);

        Notification::assertNothingSent();
    });

    it('validates email is required', function (): void {
        $response = $this->postJson('/api/v1/auth/setup-password-resend', [
            'recaptcha_token' => 'valid-token',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('validates email format', function (): void {
        $response = $this->postJson('/api/v1/auth/setup-password-resend', [
            'email' => 'invalid-email',
            'recaptcha_token' => 'valid-token',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('rejects missing recaptcha token', function (): void {
        config(['services.recaptcha.enabled' => true]);

        $response = $this->postJson('/api/v1/auth/setup-password-resend', [
            'email' => 'test@example.com',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('errors.recaptcha_token.0', __('validation.recaptcha.required'));
    });

    it('rejects invalid recaptcha token', function (): void {
        config(['services.recaptcha.enabled' => true]);
        mockFailedRecaptcha();

        $response = $this->postJson('/api/v1/auth/setup-password-resend', [
            'email' => 'test@example.com',
            'recaptcha_token' => 'invalid-token',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('errors.recaptcha_token.0', __('validation.recaptcha.failed'));
    });
});
