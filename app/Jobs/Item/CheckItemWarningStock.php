<?php

declare(strict_types=1);

namespace XetaSuite\Jobs\Item;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;
use XetaSuite\Models\Item;
use XetaSuite\Notifications\Item\ItemWarningStockNotification;

class CheckItemWarningStock implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $itemId
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $item = Item::with('recipients')->find($this->itemId);

        if (! $item) {
            return;
        }

        // Check if warning alert is enabled
        if (! $item->number_warning_enabled) {
            return;
        }

        // Check current stock level
        $currentStock = $item->item_entry_total - $item->item_exit_total;

        if ($currentStock > $item->number_warning_minimum) {
            return;
        }

        // Send notification to all recipients
        Notification::send($item->recipients, new ItemWarningStockNotification($item, $currentStock));
    }
}
