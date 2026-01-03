<?php

declare(strict_types=1);

namespace XetaSuite\Notifications\Cleaning;

use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use XetaSuite\Enums\Notifications\NotificationType;
use XetaSuite\Models\Material;

class CleaningAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The last cleaning date or null if no date.
     */
    private CarbonImmutable $lastCleaning;

    /**
     * The next cleaning date for the material.
     */
    private CarbonImmutable $nextCleaning;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private Material $material
    ) {
        $this->lastCleaning = $this->material->last_cleaning_at ?: $this->material->created_at;

        // Get le next cleaning date or use the created_at field if there's no last cleaning date.
        $this->nextCleaning = $this->material->cleaning_alert_frequency_type->nextCleaningDate($this->lastCleaning, $this->material->cleaning_alert_frequency_repeatedly);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Check if the Email Alert is enabled.
        if ($this->material->cleaning_alert_email) {
            return [
                'database',
                'mail'
            ];
        }

        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {

        $materialUrl = config('app.spa_url', config('app.url')).'/materials/'.$this->material->id;

        return (new MailMessage())
            ->subject(__('cleanings.notifications.cleaning_alert_subject', ['name' => config('app.name')]))
            ->level('primary')
            ->line(__('cleanings.notifications.cleaning_alert_greeting'))
            ->line(__('cleanings.notifications.cleaning_alert_line1'))
            ->line('<strong>' . $this->material->name . '</strong>')
            ->line(__('cleanings.notifications.cleaning_alert_line2', [
                'next_cleaning' => $this->nextCleaning,
                'message' => $this->lastCleaning === null ? '.' : __('cleanings.notifications.cleaning_alert_line2_1', ['last_cleaning' => $this->lastCleaning]),
            ]))
            ->action(__('cleanings.notifications.view_material'), $materialUrl);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $type = NotificationType::CleaningAlert;
        $materialUrl = '/materials/' . $this->material->id;

        return [
            'alert_type' => $type->value,
            'title' => __('notifications.cleaning_alert.title'),
            'message' => __('notifications.cleaning_alert.message', [
                'material' => $this->material->name,
                'next_cleaning' => $this->nextCleaning->format('d/m/Y'),
            ]),
            'icon' => $type->icon(),
            'link' => $materialUrl,
            'material_id' => $this->material->id,
            'material_name' => $this->material->name,
            'last_cleaning' => $this->lastCleaning->toISOString(),
            'next_cleaning' => $this->nextCleaning->toISOString(),
        ];
    }
}
