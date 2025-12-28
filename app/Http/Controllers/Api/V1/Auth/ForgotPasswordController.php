<?php

declare(strict_types=1);

namespace XetaSuite\Http\Controllers\Api\V1\Auth;

use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class ForgotPasswordController extends Controller
{
    /**
     * Send a reset link to the given user.
     *
     * Always returns success to prevent email enumeration attacks.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([Fortify::email() => 'required|email']);

        if (config('fortify.lowercase_usernames') && $request->has(Fortify::email())) {
            $request->merge([
                Fortify::email() => Str::lower($request->{Fortify::email()}),
            ]);
        }

        // Attempt to send the reset link (silently ignore if user doesn't exist)
        $this->broker()->sendResetLink(
            $request->only(Fortify::email())
        );

        // Always return success to prevent email enumeration
        return response()->json([
            'message' => __('passwords.sent'),
        ]);
    }

    /**
     * Get the broker to be used during password reset.
     */
    protected function broker(): PasswordBroker
    {
        return Password::broker(config('fortify.passwords'));
    }
}
