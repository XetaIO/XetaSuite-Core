<?php

declare(strict_types=1);

namespace XetaSuite\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use XetaSuite\Http\Resources\V1\Notification\NotificationResource;

class NotificationController extends Controller
{
    /**
     * Get paginated list of notifications for the authenticated user.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $notifications = $request->user()
            ->notifications()
            ->paginate($request->integer('per_page', 15));

        return NotificationResource::collection($notifications);
    }

    /**
     * Get only unread notifications for the authenticated user.
     */
    public function unread(Request $request): AnonymousResourceCollection
    {
        $notifications = $request->user()
            ->unreadNotifications()
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return NotificationResource::collection($notifications);
    }

    /**
     * Get the count of unread notifications.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $request->user()->unreadNotifications()->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        if (! Str::isUuid($id)) {
            return response()->json([
                'message' => __('notifications.not_found'),
            ], 404);
        }

        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->first();

        if (! $notification) {
            return response()->json([
                'message' => __('notifications.not_found'),
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'message' => __('notifications.marked_as_read'),
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'message' => __('notifications.all_marked_as_read'),
        ]);
    }

    /**
     * Delete a notification.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        if (! Str::isUuid($id)) {
            return response()->json([
                'message' => __('notifications.not_found'),
            ], 404);
        }

        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->first();

        if (! $notification) {
            return response()->json([
                'message' => __('notifications.not_found'),
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'message' => __('notifications.deleted'),
        ]);
    }

    /**
     * Delete all notifications.
     */
    public function destroyAll(Request $request): JsonResponse
    {
        $request->user()->notifications()->delete();

        return response()->json([
            'message' => __('notifications.all_deleted'),
        ]);
    }
}
