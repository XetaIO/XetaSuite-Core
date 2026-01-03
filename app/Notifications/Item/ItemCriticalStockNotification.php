<?php

declare(strict_types=1);

namespace XetaSuite\Notifications\Item;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use XetaSuite\Models\Item;

class ItemCriticalStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Item $item,
        public int $currentStock
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $itemUrl = config('app.spa_url', config('app.url')).'/items/'.$this->item->id;

        return (new MailMessage())
            ->subject(__('items.notifications.critical_stock_subject', ['name' => $this->item->name]))
            ->error()
            ->greeting(__('items.notifications.critical_stock_greeting'))
            ->line(__('items.notifications.critical_stock_line1', [
                'name' => $this->item->name,
                'reference' => $this->item->reference ?? '-',
            ]))
            ->line(__('items.notifications.critical_stock_line2', [
                'current' => $this->currentStock,
                'minimum' => $this->item->number_critical_minimum,
            ]))
            ->action(__('items.notifications.view_item'), $itemUrl)
            ->line(__('items.notifications.critical_stock_line3'));
    }
}
