<?php

declare(strict_types=1);

namespace XetaSuite\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;
use XetaSuite\Models\User;

class RegisteredNotification extends Notification
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     *
     * @param User $notifiable
     *
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     *
     * @param User $notifiable
     *
     * @return MailMessage
     */
    public function toMail(User $notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage())
            ->greeting(new HtmlString('<strong>Welcome to the ' . config('APP_NAME') . ', ' . $notifiable->full_name . ' !</strong>'))
            ->line(new HtmlString('Your account has just been created on the website ' . config('APP_NAME') . ' .'))
            ->line(new HtmlString('Before you can log in to the site, you must create a password for your account.'))
            ->action('Create my password', $verificationUrl)
            ->level('primary')
            ->line(new HtmlString('<strong>Note: Never share your password with anyone. The IT team does not need your password to interact with your account if they need to.</strong>'))
            ->subject('Welcome to the ' . config('APP_NAME') . ', ' . $notifiable->full_name . ' !');
    }

    /**
     * Get the verification URL for the given notifiable.
     *
     * @param User $notifiable
     *
     * @return string
     */
    protected function verificationUrl(User $notifiable): string
    {
        return URL::temporarySignedRoute(
            'auth.password.setup',
            Carbon::now()->addMinutes(Config::get('auth.password_setup.timeout', 1440)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForSetup()),
            ]
        );
    }
}
