<?php

declare(strict_types=1);

namespace XetaSuite\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use XetaSuite\Models\Item;
use XetaSuite\Models\ItemPrice;

class RecordItemPriceChange implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $itemId,
        public float $price,
        public ?int $supplierId,
        public int $createdById,
        public string $createdByName,
        public string $currency,
        public ?string $notes = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $item = Item::find($this->itemId);

        if (! $item) {
            return;
        }

        ItemPrice::create([
            'item_id' => $this->itemId,
            'supplier_id' => $this->supplierId,
            'created_by_id' => $this->createdById,
            'created_by_name' => $this->createdByName,
            'price' => $this->price,
            'effective_date' => now(),
            'currency' => $this->currency,
            'notes' => $this->notes,
        ]);
    }
}
