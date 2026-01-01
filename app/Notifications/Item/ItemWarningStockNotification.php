<?php

declare(strict_types=1);

namespace XetaSuite\Notifications\Item;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use XetaSuite\Enums\Notifications\NotificationType;
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
        $type = NotificationType::ItemWarningStock;
        $itemUrl = '/items/' . $this->item->id;

        return [
            'alert_type' => $type->value,
            'title' => __('notifications.item_warning_stock.title'),
            'message' => __('notifications.item_warning_stock.message', [
                'item' => $this->item->name,
                'current_stock' => $this->currentStock,
                'minimum' => $this->item->number_warning_minimum,
            ]),
            'icon' => $type->icon(),
            'link' => $itemUrl,
            'item_id' => $this->item->id,
            'item_name' => $this->item->name,
            'current_stock' => $this->currentStock,
            'warning_minimum' => $this->item->number_warning_minimum,
        ];
    }
}
