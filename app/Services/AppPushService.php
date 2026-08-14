<?php

namespace App\Services;

use App\Models\DispatchOrderItem;
use App\Models\Order;
use App\Models\User;
use App\Notifications\CommonNotification;
use App\Notifications\OrderNotification;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;

/**
 * Dispatch / delivery push notifications.
 * Always stores a database notification (for in-app inbox + local popup poller).
 * Prefers native FCM when credentials + fcm_token exist; otherwise OneSignal (player_id).
 */
class AppPushService
{
    public const TYPE_ITEM_PENDING = 'item_pending';
    public const TYPE_ITEM_DELIVERED = 'item_delivered';
    public const TYPE_ITEM_MESSAGE = 'item_message';
    public const TYPE_PICKUP_READY = 'pickup_ready';
    public const TYPE_PICKUP_ASSIGNED = 'pickup_assigned';
    public const TYPE_DELIVERY_ASSIGNED = 'delivery_assigned';

    public function notifyUser(User $user, string $type, string $subject, string $message, array $extra = [], bool $persistDatabase = true): void
    {
        $payload = array_merge([
            'id' => $extra['id'] ?? ('USER_' . $user->id),
            'type' => $type,
            'subject' => $subject,
            'message' => $message,
            'order_id' => (string) ($extra['order_id'] ?? ''),
            'item_id' => (string) ($extra['item_id'] ?? ''),
        ], $extra);

        // Normalize database payload id to numeric order id when possible.
        $dbPayload = $payload;
        $orderId = (int) ($extra['order_id'] ?? 0);
        if ($orderId > 0) {
            $dbPayload['id'] = $orderId;
        } elseif (is_string($payload['id']) && preg_match('/(\d+)/', (string) $payload['id'], $m)) {
            $dbPayload['id'] = (int) $m[1];
        }
        if (! empty($extra['item_id'])) {
            $dbPayload['item_id'] = (int) $extra['item_id'];
        }

        if ($persistDatabase) {
            try {
                $user->notify(new OrderNotification($dbPayload));
            } catch (\Throwable $e) {
                Log::warning('db notification failed', [
                    'user_id' => $user->id,
                    'type' => $type,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (! pushNotificationsEnabled()) {
            return;
        }

        $sentFcm = $this->sendFcm($user, $subject, $message, $payload);

        if (! $sentFcm && ! empty($user->player_id)) {
            try {
                $user->notify(new CommonNotification($type, $payload));
            } catch (\Throwable $e) {
                Log::warning('onesignal push failed', [
                    'user_id' => $user->id,
                    'type' => $type,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function notifyClientItemPending(DispatchOrderItem $item): void
    {
        $order = $item->order;
        $client = $order?->client;
        if (! $client) {
            return;
        }

        $this->notifyUser(
            $client,
            self::TYPE_ITEM_PENDING,
            __('message.push_item_pending_title'),
            __('message.push_item_pending_body', [
                'id' => $item->id,
                'code' => $item->code ?: $item->id,
            ]),
            [
                'id' => 'ITEM_' . $item->id,
                'order_id' => (string) $item->order_id,
                'item_id' => (string) $item->id,
                'app' => 'os',
            ]
        );
    }

    public function notifyClientItemDelivered(DispatchOrderItem $item): void
    {
        $order = $item->order;
        $client = $order?->client;
        if (! $client) {
            return;
        }

        // Persist as item_delivered (not generic order "completed") so OS App can filter.
        $this->notifyUser(
            $client,
            self::TYPE_ITEM_DELIVERED,
            __('message.push_item_delivered_title'),
            __('message.push_item_delivered_body', [
                'id' => $item->id,
                'code' => $item->code ?: $item->id,
            ]),
            [
                'id' => 'ITEM_' . $item->id,
                'order_id' => (string) $item->order_id,
                'item_id' => (string) $item->id,
                'app' => 'os',
            ]
        );
    }

    public function notifyClientItemMessage(User $client, DispatchOrderItem $item, string $preview): void
    {
        $snippet = trim($preview);
        if (mb_strlen($snippet) > 120) {
            $snippet = mb_substr($snippet, 0, 117).'...';
        }

        $this->notifyUser(
            $client,
            self::TYPE_ITEM_MESSAGE,
            __('message.push_item_message_title'),
            __('message.push_item_message_body', [
                'code' => $item->code ?: $item->id,
                'message' => $snippet !== '' ? $snippet : '-',
            ]),
            [
                'id' => 'ITEM_MSG_'.$item->id,
                'order_id' => (string) $item->order_id,
                'item_id' => (string) $item->id,
                'screen' => 'item_message',
                'app' => 'os',
            ]
        );
    }

    public function notifyClientPickupReady(Order $order): void
    {
        $client = $order->client;
        if (! $client) {
            return;
        }

        // Count only this pickup (order) — not every Assign 100 item for the client today.
        $itemCount = $this->clientAssign100OrderItemsQuery($client->id, (int) $order->id)->count();
        if ($itemCount < 1) {
            $itemCount = 1;
        }

        $this->notifyUser(
            $client,
            self::TYPE_PICKUP_READY,
            trans('message.push_pickup_ready_title', ['count' => $itemCount], 'my'),
            trans('message.push_pickup_ready_body', [], 'my'),
            [
                'id' => 'ORDER_' . $order->id,
                'order_id' => (string) $order->id,
                'item_id' => '',
                'item_count' => (string) $itemCount,
                'order_count' => '1',
                'screen' => 'assign100_today',
                'app' => 'os',
            ]
        );
    }

    /**
     * Items currently in Assign 100 for this client (status=assigned), assigned today.
     */
    public function clientAssign100TodayItemCount(int $clientId): int
    {
        return $this->clientAssign100TodayItemsQuery($clientId)->count();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\DispatchOrderItem>
     */
    public function clientAssign100TodayItemsQuery(int $clientId)
    {
        $start = now('Asia/Yangon')->startOfDay()->utc();
        $end = now('Asia/Yangon')->endOfDay()->utc();

        return DispatchOrderItem::query()
            ->where('status', 'assigned')
            ->whereNull('delivery_man_id')
            ->whereNotNull('assigned_at')
            ->whereBetween('assigned_at', [$start, $end])
            ->whereHas('order', function ($q) use ($clientId) {
                $q->where('client_id', $clientId);
            });
    }

    /**
     * Items that entered Assign 100 for a specific pickup (order).
     * Kept as history after leaving the pool (delivery / finished).
     *
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\DispatchOrderItem>
     */
    public function clientAssign100OrderItemsQuery(int $clientId, int $orderId)
    {
        return DispatchOrderItem::query()
            ->where('order_id', $orderId)
            ->whereNotNull('assigned_at')
            ->whereIn('status', ['assigned', 'courier_assigned', 'courier_departed', 'pending', 'completed'])
            ->whereHas('order', function ($q) use ($clientId) {
                $q->where('client_id', $clientId);
            });
    }

    public function notifyRiderPickupAssigned(Order $order, User $rider): void
    {
        // DB notification already created by saveOrderHistory.
        $this->notifyUser(
            $rider,
            self::TYPE_PICKUP_ASSIGNED,
            __('message.push_pickup_assigned_title'),
            __('message.push_pickup_assigned_body', ['id' => $order->id]),
            [
                'id' => 'ORDER_' . $order->id,
                'order_id' => (string) $order->id,
                'item_id' => '',
                'app' => 'delivery',
            ],
            false
        );
    }

    public function notifyRiderDeliveryAssigned(User $rider, DispatchOrderItem $item): void
    {
        $this->notifyUser(
            $rider,
            self::TYPE_DELIVERY_ASSIGNED,
            __('message.push_delivery_assigned_title'),
            __('message.push_delivery_assigned_body', [
                'id' => $item->id,
                'code' => $item->code ?: $item->id,
            ]),
            [
                'id' => 'ITEM_' . $item->id,
                'order_id' => (string) $item->order_id,
                'item_id' => (string) $item->id,
                'app' => 'delivery',
            ]
        );
    }

    /**
     * @return bool true when FCM was attempted successfully
     */
    protected function sendFcm(User $user, string $title, string $body, array $data): bool
    {
        $token = trim((string) ($user->fcm_token ?? ''));
        if ($token === '') {
            return false;
        }

        $credentials = $this->credentialsPathForUser($user);
        if ($credentials === null) {
            return false;
        }

        try {
            $factory = (new Factory)->withServiceAccount($credentials);
            $messaging = $factory->createMessaging();

            $stringData = [];
            foreach ($data as $key => $value) {
                if (is_scalar($value) || $value === null) {
                    $stringData[(string) $key] = (string) ($value ?? '');
                }
            }

            $message = CloudMessage::withTarget('token', $token)
                ->withNotification(FcmNotification::create($title, $body))
                ->withData($stringData);

            $messaging->send($message);

            return true;
        } catch (\Throwable $e) {
            Log::warning('fcm push failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            // Clear dead tokens so OneSignal can take over next time.
            if (str_contains(strtolower($e->getMessage()), 'not found')
                || str_contains(strtolower($e->getMessage()), 'unregistered')) {
                $user->forceFill(['fcm_token' => null])->save();
            }

            return false;
        }
    }

    protected function credentialsPathForUser(User $user): ?string
    {
        $userType = (string) ($user->user_type ?? '');
        $candidates = [];

        if ($userType === 'delivery_man') {
            $candidates[] = env('FIREBASE_DELIVERY_CREDENTIALS');
            $candidates[] = storage_path('app/firebase/delivery-service-account.json');
        } else {
            $candidates[] = env('FIREBASE_USER_CREDENTIALS');
            $candidates[] = storage_path('app/firebase/user-service-account.json');
        }

        $candidates[] = env('FIREBASE_CREDENTIALS');
        $candidates[] = env('GOOGLE_APPLICATION_CREDENTIALS');
        $candidates[] = storage_path('app/firebase/service-account.json');

        foreach ($candidates as $path) {
            $path = trim((string) $path);
            if ($path === '') {
                continue;
            }
            if (! str_starts_with($path, '/') && ! preg_match('#^[A-Za-z]:\\\\#', $path)) {
                $absolute = base_path($path);
                if (is_readable($absolute)) {
                    return $absolute;
                }
            }
            if (is_readable($path)) {
                return $path;
            }
        }

        return null;
    }
}
