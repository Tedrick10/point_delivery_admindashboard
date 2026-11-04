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

    /**
     * Filter Laravel notifications by JSON data.type without loading the whole inbox.
     */
    protected function applyAllowedTypeFilter($query, ?array $allowedTypes)
    {
        if ($allowedTypes === null || $allowedTypes === []) {
            return $query;
        }

        return $query->where(function ($outer) use ($allowedTypes) {
            foreach ($allowedTypes as $type) {
                $safe = addcslashes((string) $type, "%_\\");
                $outer->orWhere('data', 'like', '%"type":"'.$safe.'"%');
            }
        });
    }

    public function getList(Request $request)
    {
        $user = auth()->user();
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
                $unread = $this->applyAllowedTypeFilter($user->unreadNotifications(), $allowedTypes);
                $unread->orderByDesc('created_at')->limit(500)->get()
                    ->each(function ($notification) {
                        $notification->markAsRead();
                    });
            }
        }

        // Throttle "seen" writes — inbox polling must not UPDATE users every few seconds.
        $seenKey = 'notif_seen_touch:'.$user->id;
        if (! cache()->has($seenKey)) {
            $user->forceFill(['last_notification_seen' => now()])->save();
            cache()->put($seenKey, 1, 60);
        }

        $category = $request->get('category', 'all');
        $page = max(1, (int) ($request->page ?? 1));
        $limit = (int) ($request->limit ?? config('constant.PER_PAGE_LIMIT'));
        if ($limit < 1) {
            $limit = (int) config('constant.PER_PAGE_LIMIT');
        }
        $limit = min(50, $limit);

        $query = $this->applyAllowedTypeFilter($user->notifications(), $allowedTypes)
            ->orderByDesc('created_at');

        // Category filter still needs JSON inspection; pull a bounded window then filter.
        if ($category !== 'all') {
            $window = (clone $query)->limit(300)->get();
            $filtered = $window->filter(function ($notification) use ($category) {
                $data = is_array($notification->data) ? $notification->data : [];

                return inferNotificationCategory($data) === $category;
            })->values();
            $notifications = $filtered->forPage($page, $limit);
        } else {
            $notifications = (clone $query)
                ->forPage($page, $limit)
                ->get();
        }

        $all_unread_count = (int) $this->applyAllowedTypeFilter($user->unreadNotifications(), $allowedTypes)->count();

        $items = NotificationResource::collection($notifications);

        return json_custom_response([
            'notification_data' => $items,
            'all_unread_count' => $all_unread_count,
        ]);
    }
}
