<?php

declare(strict_types=1);

namespace XetaSuite\Http\Resources\V1\Notification;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Illuminate\Notifications\DatabaseNotification
 */
class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = $this->data;

        return [
            'id' => $this->id,
            'type' => $this->getNotificationType(),
            'title' => $data['title'] ?? null,
            'message' => $data['message'] ?? null,
            'icon' => $data['icon'] ?? null,
            'link' => $data['link'] ?? null,
            'data' => $data,
            'read_at' => $this->read_at?->toISOString(),
            'is_read' => $this->read_at !== null,
            'created_at' => $this->created_at->toISOString(),
            'time_ago' => $this->created_at->diffForHumans(),
        ];
    }

    /**
     * Get a human-readable notification type from the class name.
     */
    private function getNotificationType(): string
    {
        $type = class_basename($this->type);

        // Remove "Notification" suffix if present
        return str_replace('Notification', '', $type);
    }
}
