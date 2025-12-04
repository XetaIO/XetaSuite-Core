<?php

declare(strict_types=1);

namespace XetaSuite\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use XetaSuite\Models\Item;
use XetaSuite\Notifications\ItemCriticalStockNotification;

class CheckItemCriticalStock implements ShouldQueue
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

        // Check if critical alert is enabled
        if (! $item->number_critical_enabled) {
            return;
        }

        // Check current stock level
        $currentStock = $item->item_entry_total - $item->item_exit_total;

        if ($currentStock > $item->number_critical_minimum) {
            return;
        }

        // Send notification to all recipients
        foreach ($item->recipients as $recipient) {
            $recipient->notify(new ItemCriticalStockNotification($item, $currentStock));
        }
    }
}
