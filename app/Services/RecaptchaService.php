<?php

declare(strict_types=1);

namespace XetaSuite\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RecaptchaService
{
    private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    /**
     * Verify a reCAPTCHA v3 token.
     *
     * @param  string  $token  The reCAPTCHA token from the frontend
     * @param  string|null  $action  Expected action name (optional)
     * @return bool True if verification passed, false otherwise
     */
    public function verify(string $token, ?string $action = null): bool
    {
        // If reCAPTCHA is disabled, always return true
        if (! config('services.recaptcha.enabled', true)) {
            return true;
        }

        $secretKey = config('services.recaptcha.secret_key');

        if (empty($secretKey)) {
            Log::warning('reCAPTCHA secret key is not configured');

            return true; // Don't block if not configured
        }

        try {
            $response = Http::asForm()->post(self::VERIFY_URL, [
                'secret' => $secretKey,
                'response' => $token,
            ]);

            if (! $response->successful()) {
                Log::warning('reCAPTCHA verification request failed', [
                    'status' => $response->status(),
                ]);

                return false;
            }

            $data = $response->json();

            // Check if verification was successful
            if (! ($data['success'] ?? false)) {
                Log::info('reCAPTCHA verification failed', [
                    'error-codes' => $data['error-codes'] ?? [],
                ]);

                return false;
            }

            // Check action if provided
            if ($action !== null && ($data['action'] ?? null) !== $action) {
                Log::info('reCAPTCHA action mismatch', [
                    'expected' => $action,
                    'actual' => $data['action'] ?? null,
                ]);

                return false;
            }

            // Check score (v3 specific)
            $minScore = config('services.recaptcha.min_score', 0.5);
            $score = $data['score'] ?? 0;

            if ($score < $minScore) {
                Log::info('reCAPTCHA score too low', [
                    'score' => $score,
                    'min_score' => $minScore,
                ]);

                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('reCAPTCHA verification error', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
