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
     *
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     */
    public function toMail(User $notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage())
            ->greeting(new HtmlString('<strong>'.__('notifications.registered.greeting', ['app' => config('app.name'), 'name' => $notifiable->full_name]).'</strong>'))
            ->line(new HtmlString(__('notifications.registered.line1', ['app' => config('app.name')])))
            ->line(new HtmlString(__('notifications.registered.line2')))
            ->action(__('notifications.registered.action'), $verificationUrl)
            ->level('primary')
            ->line(new HtmlString('<strong>'.__('notifications.registered.warning').'</strong>'))
            ->subject(__('notifications.registered.subject', ['app' => config('app.name'), 'name' => $notifiable->full_name]));
    }

    /**
     * Get the verification URL for the given notifiable.
     * Generates a URL pointing to the frontend app with signed query parameters.
     */
    protected function verificationUrl(User $notifiable): string
    {
        // Generate the signed URL with backend route for signature validation
        $signedUrl = URL::temporarySignedRoute(
            'auth.password.setup',
            Carbon::now()->addMinutes(Config::get('auth.password_setup.timeout', 1440)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForSetup()),
            ]
        );

        // Extract query parameters (signature & expires) from signed URL
        $parsedUrl = parse_url($signedUrl);
        $queryString = $parsedUrl['query'] ?? '';

        // Build frontend URL with the signed parameters
        $frontendUrl = Config::get('app.spa_url', 'http://localhost:5173');

        return $frontendUrl.'/setup-password/'.$notifiable->getKey().'/'.sha1($notifiable->getEmailForSetup()).'?'.$queryString;
    }
}
