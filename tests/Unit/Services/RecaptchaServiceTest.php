<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use XetaSuite\Services\RecaptchaService;

beforeEach(function (): void {
    // Set test config
    config([
        'services.recaptcha.secret_key' => 'test-secret-key',
        'services.recaptcha.min_score' => 0.5,
        'services.recaptcha.enabled' => true,
    ]);
});

describe('RecaptchaService', function (): void {
    it('returns true when recaptcha is disabled', function (): void {
        config(['services.recaptcha.enabled' => false]);

        $service = new RecaptchaService();

        expect($service->verify('any-token', 'test_action'))->toBeTrue();
    });

    it('returns true when secret key is not configured', function (): void {
        config(['services.recaptcha.secret_key' => null]);

        $service = new RecaptchaService();

        expect($service->verify('any-token', 'test_action'))->toBeTrue();
    });

    it('returns true for valid recaptcha response', function (): void {
        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
                'score' => 0.9,
                'action' => 'test_action',
            ]),
        ]);

        $service = new RecaptchaService();

        expect($service->verify('valid-token', 'test_action'))->toBeTrue();
    });

    it('returns false when google returns success false', function (): void {
        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => false,
                'error-codes' => ['invalid-input-response'],
            ]),
        ]);

        $service = new RecaptchaService();

        expect($service->verify('invalid-token', 'test_action'))->toBeFalse();
    });

    it('returns false when score is too low', function (): void {
        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
                'score' => 0.3,
                'action' => 'test_action',
            ]),
        ]);

        $service = new RecaptchaService();

        expect($service->verify('valid-token', 'test_action'))->toBeFalse();
    });

    it('returns false when action does not match', function (): void {
        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
                'score' => 0.9,
                'action' => 'wrong_action',
            ]),
        ]);

        $service = new RecaptchaService();

        expect($service->verify('valid-token', 'test_action'))->toBeFalse();
    });

    it('returns true when action is null (not checking action)', function (): void {
        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
                'score' => 0.9,
                'action' => 'any_action',
            ]),
        ]);

        $service = new RecaptchaService();

        expect($service->verify('valid-token'))->toBeTrue();
    });

    it('returns false when http request fails', function (): void {
        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([], 500),
        ]);

        $service = new RecaptchaService();

        expect($service->verify('valid-token', 'test_action'))->toBeFalse();
    });
});
