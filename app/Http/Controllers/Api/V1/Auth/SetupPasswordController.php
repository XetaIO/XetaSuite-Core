<?php

declare(strict_types=1);

namespace XetaSuite\Http\Controllers\Api\V1\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use XetaSuite\Http\Requests\V1\Auth\ResendSetupPasswordRequest;
use XetaSuite\Http\Requests\V1\Auth\SetupPasswordRequest;
use XetaSuite\Models\User;
use XetaSuite\Notifications\Auth\RegisteredNotification;

class SetupPasswordController extends Controller
{
    /**
     * Verify the password setup link is valid.
     * GET /api/v1/auth/setup-password/{id}/{hash}
     */
    public function verify(Request $request, int $id, string $hash): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'valid' => false,
                'message' => __('auth.password_setup.user_not_found'),
            ], 404);
        }

        // Check if hash matches user's email
        if (! hash_equals($hash, sha1($user->getEmailForSetup()))) {
            return response()->json([
                'valid' => false,
                'message' => __('auth.password_setup.invalid_link'),
            ], 403);
        }

        // Verify the signed URL
        if (! $request->hasValidSignature()) {
            return response()->json([
                'valid' => false,
                'message' => __('auth.password_setup.link_expired'),
            ], 403);
        }

        // Check if password already set up
        if ($user->hasSetupPassword()) {
            return response()->json([
                'valid' => false,
                'message' => __('auth.password_setup.already_setup'),
            ], 403);
        }

        return response()->json([
            'valid' => true,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'full_name' => $user->full_name,
            ],
        ]);
    }

    /**
     * Set up the user's password.
     * POST /api/v1/auth/setup-password/{id}/{hash}
     */
    public function store(SetupPasswordRequest $request, int $id, string $hash): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'message' => __('auth.password_setup.user_not_found'),
            ], 404);
        }

        // Check if hash matches user's email
        if (! hash_equals($hash, sha1($user->getEmailForSetup()))) {
            return response()->json([
                'message' => __('auth.password_setup.invalid_link'),
            ], 403);
        }

        // Verify the signed URL
        if (! $request->hasValidSignature()) {
            return response()->json([
                'message' => __('auth.password_setup.link_expired'),
            ], 403);
        }

        // Check if password already set up
        if ($user->hasSetupPassword()) {
            return response()->json([
                'message' => __('auth.password_setup.already_setup'),
            ], 403);
        }

        // Set up the password
        $user->markPasswordAsSetup($request);

        return response()->json([
            'message' => __('auth.password_setup.success'),
        ]);
    }

    /**
     * Resend the password setup link.
     * POST /api/v1/auth/setup-password-resend
     */
    public function resend(ResendSetupPasswordRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        // Always return success to prevent email enumeration
        if (! $user) {
            return response()->json([
                'message' => __('auth.password_setup.resend_success'),
            ]);
        }

        // Check if password already set up
        if ($user->hasSetupPassword()) {
            return response()->json([
                'message' => __('auth.password_setup.resend_success'),
            ]);
        }

        // Resend the notification
        $user->notify(new RegisteredNotification());

        return response()->json([
            'message' => __('auth.password_setup.resend_success'),
        ]);
    }
}
