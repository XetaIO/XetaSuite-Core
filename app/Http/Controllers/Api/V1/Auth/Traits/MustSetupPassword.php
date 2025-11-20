<?php

declare(strict_types=1);

namespace XetaSuite\Http\Controllers\Api\V1\Auth\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use XetaSuite\Notifications\Auth\RegisteredNotification;

trait MustSetupPassword
{
    /**
     * Determine if the user has set up their password.
     *
     * @return bool
     */
    public function hasSetupPassword()
    {
        return ! is_null($this->password_setup_at);
    }

    /**
     * Mark the given user's password as set up.
     *
     * @return bool
     */
    public function markPasswordAsSetup(Request $request)
    {
        return $this->forceFill([
            'password' => Hash::make($request->password),
            'password_setup_at' => $this->freshTimestamp(),
        ])->save();
    }

    /**
     * Send the email registered notification.
     *
     * @return void
     */
    public function sendEmailRegisteredNotification()
    {
        $this->notify(new RegisteredNotification);
    }

    /**
     * Get the email address that should be used for setup.
     *
     * @return string
     */
    public function getEmailForSetup()
    {
        return $this->email;
    }
}
