<?php

declare(strict_types=1);

namespace XetaSuite\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Supported locales.
     *
     * @var array<string>
     */
    protected array $supportedLocales = ['fr', 'en'];

    /**
     * Handle an incoming request.
     *
     * Set the application locale based on:
     * 1. Authenticated user's locale preference
     * 2. Cookie 'locale'
     * 3. Accept-Language header
     * 4. Default locale from config
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->determineLocale($request);

        app()->setLocale($locale);

        $response = $next($request);

        // Set locale cookie in response for consistency
        if ($response instanceof Response) {
            $response->headers->setCookie(
                cookie('locale', $locale, 60 * 24 * 365) // 1 year
            );
        }

        return $response;
    }

    /**
     * Determine the locale to use for the request.
     */
    protected function determineLocale(Request $request): string
    {
        // 1. Check authenticated user's preference
        if ($request->user() && $request->user()->locale) {
            $locale = $request->user()->locale;
            if ($this->isValidLocale($locale)) {
                return $locale;
            }
        }

        // 2. Check cookie
        $cookieLocale = $request->cookie('locale');
        if ($cookieLocale && $this->isValidLocale($cookieLocale)) {
            return $cookieLocale;
        }

        // 3. Check Accept-Language header
        $preferredLanguage = $request->getPreferredLanguage($this->supportedLocales);
        if ($preferredLanguage && $this->isValidLocale($preferredLanguage)) {
            return $preferredLanguage;
        }

        // 4. Fall back to default locale
        return config('app.locale', 'en');
    }

    /**
     * Check if a locale is valid.
     */
    protected function isValidLocale(string $locale): bool
    {
        return in_array($locale, $this->supportedLocales, true);
    }
}
