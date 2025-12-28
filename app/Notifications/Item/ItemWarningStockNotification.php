<?php

declare(strict_types=1);

namespace XetaSuite\Notifications\Item;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use XetaSuite\Models\Item;

class ItemWarningStockNotification extends Notification implements ShouldQueue
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
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'alert_type' => 'warning_stock',
            'item_id' => $this->item->id,
            'item_name' => $this->item->name,
            'current_stock' => $this->currentStock,
            'warning_minimum' => $this->item->number_warning_minimum,
        ];
    }
}
