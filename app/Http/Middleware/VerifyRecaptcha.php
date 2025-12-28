<?php

declare(strict_types=1);

namespace XetaSuite\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use XetaSuite\Services\RecaptchaService;

class VerifyRecaptcha
{
    public function __construct(
        private RecaptchaService $recaptchaService
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $action = null): Response
    {
        // Skip if reCAPTCHA is disabled
        if (! config('services.recaptcha.enabled', true)) {
            return $next($request);
        }

        $token = $request->input('recaptcha_token');

        if (empty($token)) {
            return response()->json([
                'message' => __('validation.recaptcha.required'),
                'errors' => [
                    'recaptcha_token' => [__('validation.recaptcha.required')],
                ],
            ], 422);
        }

        if (! $this->recaptchaService->verify($token, $action)) {
            return response()->json([
                'message' => __('validation.recaptcha.failed'),
                'errors' => [
                    'recaptcha_token' => [__('validation.recaptcha.failed')],
                ],
            ], 422);
        }

        return $next($request);
    }
}
