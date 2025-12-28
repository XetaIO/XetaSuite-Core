<?php

declare(strict_types=1);

namespace XetaSuite\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;
use XetaSuite\Models\User;
use XetaSuite\Settings\Settings;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        // Custom authentication logic to check if login is enabled
        Fortify::authenticateUsing(function (Request $request) {
            // Find user by email first
            $user = User::where('email', $request->input(Fortify::username()))->first();

            // Validate credentials
            if (! $user || ! Hash::check($request->input('password'), $user->password)) {
                return null;
            }

            /** @var Settings $settings */
            $settings = app(Settings::class);

            // Check if login is enabled globally
            $loginEnabled = $settings->withoutContext()->get('login_enabled');

            if ($loginEnabled === false) {
                // Check if user has bypass.login permission on ANY site
                $canBypass = $this->userCanBypassLogin($user);

                if (! $canBypass) {
                    throw ValidationException::withMessages([
                        Fortify::username() => [__('auth.login_disabled')],
                    ]);
                }
            }

            return $user;
        });

        // Customize password reset URL to point to React SPA
        ResetPassword::createUrlUsing(function ($user, string $token) {
            return config('app.frontend_url').'/reset-password?token='.$token.'&email='.urlencode($user->email);
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }

    /**
     * Check if user has bypass.login permission globally (site_id = 0).
     * This permission is assigned directly to user via model_has_permissions with site_id = 0.
     */
    private function userCanBypassLogin(User $user): bool
    {
        // Set team context to 0 for global permissions
        setPermissionsTeamId(0);

        // Clear cached permissions to ensure fresh check
        $user->unsetRelation('permissions');
        $user->unsetRelation('roles');

        try {
            return $user->hasPermissionTo('bypass.login');
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist) {
            // Permission doesn't exist in the system
            return false;
        }
    }
}
