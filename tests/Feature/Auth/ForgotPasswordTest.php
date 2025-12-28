<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Notification;
use XetaSuite\Models\User;

beforeEach(function () {
    // Configure reCAPTCHA settings
    config([
        'services.recaptcha.secret_key' => 'test-secret-key',
        'services.recaptcha.min_score' => 0.5,
        'services.recaptcha.enabled' => true,
    ]);
});

describe('Forgot Password', function () {
    it('sends reset link for valid email', function () {
        mockSuccessfulRecaptcha();
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $user->email,
            'recaptcha_token' => 'valid-token',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['message']);
    });

    it('returns success for non-existent email to prevent enumeration', function () {
        mockSuccessfulRecaptcha();
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'nonexistent@example.com',
            'recaptcha_token' => 'valid-token',
        ]);

        // Always returns success to prevent email enumeration
        $response->assertOk()
            ->assertJsonStructure(['message']);

        Notification::assertNothingSent();
    });

    it('validates email is required', function () {
        mockSuccessfulRecaptcha();

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'recaptcha_token' => 'valid-token',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('validates email format', function () {
        mockSuccessfulRecaptcha();

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'invalid-email',
            'recaptcha_token' => 'valid-token',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('rejects missing recaptcha token', function () {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'test@example.com',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('errors.recaptcha_token.0', __('validation.recaptcha.required'));
    });

    it('rejects invalid recaptcha token', function () {
        mockFailedRecaptcha();

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'test@example.com',
            'recaptcha_token' => 'invalid-token',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('errors.recaptcha_token.0', __('validation.recaptcha.failed'));
    });
});
