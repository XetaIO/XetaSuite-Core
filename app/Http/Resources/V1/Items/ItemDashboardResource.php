<?php

declare(strict_types=1);

namespace XetaSuite\Http\Resources\V1\Items;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemDashboardResource extends JsonResource
{
    /**
     * Transform the resource for dashboard (alerts & stats).
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'reference' => $this->reference,

            // Stock pour dashboard
            'current_stock' => $this->current_stock,
            'stock_status' => $this->stock_status,
            'stock_status_color' => $this->stock_status_color,
            'stock_status_label' => $this->stock_status_label,
            'stock_value' => $this->stock_value,
            'formatted_stock_value' => $this->formatted_stock_value,

            // Alertes uniquement
            'is_low_stock' => $this->is_low_stock,
            'is_critical_stock' => $this->is_critical_stock,
            'needs_restock' => $this->needs_restock,
            'quantity_to_warning_level' => $this->quantity_to_warning_level,

            // Stats clés
            'average_entry_quantity' => $this->average_entry_quantity,
            'average_exit_quantity' => $this->average_exit_quantity,

            'site' => [
                'id' => $this->site_id,
                'name' => $this->whenLoaded('site', $this->site?->name),
            ],
        ];
    }
}
