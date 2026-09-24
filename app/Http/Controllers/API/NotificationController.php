<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;

class NotificationController extends Controller
{
    /**
     * Notification types shown (and counted) per app.
     *
     * @return list<string>|null null = no type filter
     */
    protected function allowedTypesForUser($user): ?array
    {
        return match ((string) ($user->user_type ?? '')) {
            'client' => [
                'item_pending',
                'item_delivered',
                'pickup_ready',
                'os_settlement',
                'kyo_shin',
                'kyo_shin_overdue',
                'os_receive_pay',
                'os_receive_approved',
                'os_receive_rejected',
                'item_message',
            ],
            'delivery_man' => ['pickup_assigned', 'delivery_assigned'],
            default => null,
        };
    }

    protected function notificationType($notification): string
    {
        $data = is_array($notification->data) ? $notification->data : [];

        return (string) ($data['type'] ?? '');
    }

    protected function matchesAllowedType($notification, ?array $allowedTypes): bool
    {
        if ($allowedTypes === null) {
            return true;
        }

        return in_array($this->notificationType($notification), $allowedTypes, true);
    }

    public function getList(Request $request)
    {
        $user = auth()->user();

        $user->last_notification_seen = now();
        $user->save();

        $allowedTypes = $this->allowedTypesForUser($user);

        $type = isset($request->type) ? $request->type : null;
        if ($type == 'markas_read') {
            $notificationId = $request->get('id') ?? $request->get('notification_id');
            if (! empty($notificationId)) {
                $notification = $user->unreadNotifications()->where('id', $notificationId)->first();
                if ($notification && $this->matchesAllowedType($notification, $allowedTypes)) {
                    $notification->markAsRead();
                }
            } else {
                $unread = $user->unreadNotifications;
                if ($allowedTypes !== null) {
                    $unread = $unread->filter(fn ($n) => $this->matchesAllowedType($n, $allowedTypes));
                }
                foreach ($unread as $notification) {
                    $notification->markAsRead();
                }
            }
        }

        $user->refresh();
        $user->load(['notifications', 'unreadNotifications']);

        $category = $request->get('category', 'all');
        $page = isset($request->page) ? $request->page : 1;
        $limit = isset($request->limit) ? $request->limit : config('constant.PER_PAGE_LIMIT');

        $notifications = $user->notifications->sortByDesc('created_at');
        if ($allowedTypes !== null) {
            $notifications = $notifications->filter(fn ($n) => $this->matchesAllowedType($n, $allowedTypes));
        }
        if ($category !== 'all') {
            $notifications = $notifications->filter(function ($notification) use ($category) {
                $data = is_array($notification->data) ? $notification->data : [];

                return inferNotificationCategory($data) === $category;
            });
        }
        $notifications = $notifications->values()->forPage($page, $limit);

        $unread = $user->unreadNotifications;
        if ($allowedTypes !== null) {
            $unread = $unread->filter(fn ($n) => $this->matchesAllowedType($n, $allowedTypes));
        }
        $all_unread_count = $unread->count();

        $items = NotificationResource::collection($notifications);

        $response = [
            'notification_data' => $items,
            'all_unread_count' => $all_unread_count,
        ];

        return json_custom_response($response);
    }
}
