<?php

declare(strict_types=1);

namespace XetaSuite\Http\Resources\V1\Settings;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use XetaSuite\Http\Resources\V1\Users\UserResource;

class SettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'value' => $this->value,
            'text' => $this->text,
            'label' => $this->label,
            'label_info' => $this->label_info,

            // Updater
            'updated_by_id' => $this->updated_by_id,
            'updater' => $this->whenLoaded('updater', fn () => new UserResource($this->updater)),

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
