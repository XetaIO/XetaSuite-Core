<?php

declare(strict_types=1);

namespace XetaSuite\Http\Resources\V1\Incidents;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncidentDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array (for detail view).
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'severity' => $this->severity?->value,
            'severity_label' => $this->severity?->label(),

            // Site info
            'site_id' => $this->site_id,
            'site' => $this->whenLoaded('site', fn () => [
                'id' => $this->site->id,
                'name' => $this->site->name,
            ]),

            // Material info
            'material_id' => $this->material_id,
            'material_name' => $this->material_name,
            'material' => $this->whenLoaded('material', fn () => $this->material ? [
                'id' => $this->material->id,
                'name' => $this->material->name,
                'zone' => $this->material->zone ? [
                    'id' => $this->material->zone->id,
                    'name' => $this->material->zone->name,
                ] : null,
            ] : null),

            // Maintenance info (optional)
            'maintenance_id' => $this->maintenance_id,
            'maintenance' => $this->whenLoaded('maintenance', fn () => $this->maintenance ? [
                'id' => $this->maintenance->id,
                'description' => $this->maintenance->description,
                'status' => $this->maintenance->status?->value,
            ] : null),

            // Reporter info
            'reported_by_id' => $this->reported_by_id,
            'reported_by_name' => $this->reported_by_name,
            'reporter' => $this->whenLoaded('reporter', fn () => $this->reporter ? [
                'id' => $this->reporter->id,
                'full_name' => $this->reporter->full_name,
                'email' => $this->reporter->email,
            ] : null),

            // Editor info
            'edited_by_id' => $this->edited_by_id,
            'editor' => $this->whenLoaded('editor', fn () => $this->editor ? [
                'id' => $this->editor->id,
                'full_name' => $this->editor->full_name,
            ] : null),

            // Dates
            'started_at' => $this->started_at?->toISOString(),
            'resolved_at' => $this->resolved_at?->toISOString(),

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
