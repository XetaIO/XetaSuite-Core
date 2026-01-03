<?php

declare(strict_types=1);

namespace XetaSuite\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use XetaSuite\Http\Requests\V1\User\UpdatePasswordRequest;

class UserPasswordController extends Controller
{
    /**
     * Update the authenticated user's password.
     */
    public function __invoke(UpdatePasswordRequest $request): JsonResponse
    {
        $request->user()->update([
            'password' => Hash::make($request->validated('password')),
        ]);

        return response()->json([
            'message' => __('user.password_updated'),
        ]);
    }
}
