<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\DispatchItemMessageResource;
use App\Models\DispatchItemMessage;
use App\Models\DispatchOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DispatchItemMessageController extends Controller
{
    /**
     * Threads for the logged-in OS client: all their dispatch items, with chat preview when present.
     */
    public function threads(Request $request)
    {
        $user = auth()->user();
        $clientId = (int) $user->id;

        // Include every parcel belonging to this OS so Order Chat is never empty when they have orders.
        $items = DispatchOrderItem::query()
            ->with(['order:id,client_id'])
            ->whereHas('order', function ($q) use ($clientId) {
                $q->where('client_id', $clientId);
            })
            ->orderByDesc('id')
            ->limit(250)
            ->get()
            ->keyBy('id');

        $itemIds = $items->keys()->all();

        $threads = collect();
        if (! empty($itemIds)) {
            $threads = DispatchItemMessage::query()
                ->select([
                    'dispatch_order_item_id',
                    DB::raw('MAX(id) as last_message_id'),
                    DB::raw('COUNT(*) as message_count'),
                    DB::raw("SUM(CASE WHEN sender_type != 'client' AND read_at IS NULL THEN 1 ELSE 0 END) as unread_count"),
                ])
                ->whereIn('dispatch_order_item_id', $itemIds)
                ->groupBy('dispatch_order_item_id')
                ->get()
                ->keyBy('dispatch_order_item_id');
        }

        $lastMessages = DispatchItemMessage::query()
            ->with('media')
            ->whereIn('id', $threads->pluck('last_message_id')->filter()->all())
            ->get()
            ->keyBy('id');

        $osName = trim((string) ($user->name ?? ''));
        $osUsername = trim((string) ($user->username ?? ''));
        $osPhone = trim((string) (function_exists('normalizeContactNumber')
            ? normalizeContactNumber($user->contact_number ?? '')
            : ($user->contact_number ?? '')));
        $osProfileImage = resolveUploadedProfileImageUrl($user, true);

        $data = $items->map(function (DispatchOrderItem $item) use ($threads, $lastMessages, $osName, $osUsername, $osPhone, $osProfileImage) {
            $row = $threads->get($item->id);
            $last = $row ? $lastMessages->get($row->last_message_id) : null;
            $lastText = $this->previewMessageText($last);
            $senderIsOs = ($last?->sender_type === 'client');

            return [
                'dispatch_order_item_id' => (int) $item->id,
                'order_id' => $item->order_id,
                'parcel_id' => $item->code ?: ('#'.$item->id),
                'os_name' => $osName !== '' ? $osName : null,
                'os_username' => $osUsername !== '' ? $osUsername : null,
                'os_phone' => $osPhone !== '' ? $osPhone : null,
                'os_profile_image' => $osProfileImage,
                'customer_name' => trim((string) ($item->customer_name ?? '')) ?: null,
                'customer_phone' => trim((string) ($item->customer_phone ?? '')) ?: null,
                'message_count' => (int) ($row->message_count ?? 0),
                'unread_count' => (int) ($row->unread_count ?? 0),
                'last_message' => $lastText !== '' ? $lastText : null,
                'last_message_at' => optional($last?->created_at)?->toDateTimeString(),
                'last_sender_type' => $last?->sender_type,
                'last_sender_label' => $last
                    ? ($senderIsOs ? ($osName !== '' ? $osName : 'Os') : 'Admin')
                    : null,
            ];
        })
            // Unread first, then threads with messages, then newest parcels.
            ->sort(function ($a, $b) {
                $aUnread = ((int) ($a['unread_count'] ?? 0) > 0) ? 1 : 0;
                $bUnread = ((int) ($b['unread_count'] ?? 0) > 0) ? 1 : 0;
                if ($aUnread !== $bUnread) {
                    return $bUnread <=> $aUnread;
                }

                $aHasMsg = ((int) ($a['message_count'] ?? 0) > 0) ? 1 : 0;
                $bHasMsg = ((int) ($b['message_count'] ?? 0) > 0) ? 1 : 0;
                if ($aHasMsg !== $bHasMsg) {
                    return $bHasMsg <=> $aHasMsg;
                }

                $byTime = strcmp((string) ($b['last_message_at'] ?? ''), (string) ($a['last_message_at'] ?? ''));
                if ($byTime !== 0) {
                    return $byTime;
                }

                return ((int) ($b['dispatch_order_item_id'] ?? 0)) <=> ((int) ($a['dispatch_order_item_id'] ?? 0));
            })
            ->values();

        $data = $this->filterMessageThreads($data, $request);

        $totalUnread = (int) $data->filter(function ($row) {
            return (int) ($row['unread_count'] ?? 0) > 0;
        })->count();

        return json_custom_response([
            'data' => $data,
            'total_unread' => $totalUnread,
        ]);
    }

    protected function previewMessageText($message): string
    {
        if (! $message) {
            return '';
        }

        $text = trim((string) ($message->message ?? ''));
        if ($text !== '') {
            return $text;
        }

        $type = strtolower((string) ($message->message_type ?? ''));
        if (in_array($type, ['audio', 'video', 'image'], true)) {
            return match ($type) {
                'audio' => '🎤 Voice message',
                'video' => '🎬 Video',
                default => '[Image]',
            };
        }

        if (getMediaFileExit($message, 'chat_image')) {
            return '[Image]';
        }
        if (getMediaFileExit($message, 'chat_media')) {
            $mime = (string) (optional($message->getFirstMedia('chat_media'))->mime_type ?? '');
            if (str_starts_with($mime, 'video/')) {
                return '🎬 Video';
            }
            if (str_starts_with($mime, 'audio/')) {
                return '🎤 Voice message';
            }

            return '📎 Attachment';
        }

        return '';
    }

    public function unreadCount()
    {
        $user = auth()->user();
        // One badge unit per item that has unread admin/staff messages.
        $count = DispatchItemMessage::query()
            ->where('client_id', $user->id)
            ->where('sender_type', '!=', 'client')
            ->whereNull('read_at')
            ->distinct()
            ->count('dispatch_order_item_id');

        return json_custom_response(['total_unread' => (int) $count]);
    }

    public function getList(Request $request)
    {
        $request->validate(['dispatch_order_item_id' => 'required|integer']);
        $user = auth()->user();
        $itemId = (int) $request->dispatch_order_item_id;

        $item = DispatchOrderItem::query()->with('order')->findOrFail($itemId);
        $this->assertCanAccessItem($user, $item);

        if ($user->user_type === 'client') {
            DispatchItemMessage::query()
                ->where('dispatch_order_item_id', $itemId)
                ->where('client_id', $user->id)
                ->where('sender_type', '!=', 'client')
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        $messages = DispatchItemMessage::query()
            ->with(['sender', 'media'])
            ->where('dispatch_order_item_id', $itemId)
            ->orderBy('created_at')
            ->get();

        return json_custom_response([
            'data' => DispatchItemMessageResource::collection($messages),
            'item' => [
                'id' => $item->id,
                'order_id' => $item->order_id,
                'parcel_id' => $item->code ?: ('#'.$item->id),
                'customer_name' => trim((string) ($item->customer_name ?? '')) ?: null,
                'customer_phone' => trim((string) ($item->customer_phone ?? '')) ?: null,
                'os_name' => trim((string) ($user->name ?? '')) ?: null,
                'os_profile_image' => resolveUploadedProfileImageUrl($user, true),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'dispatch_order_item_id' => 'required|integer',
            'message' => 'nullable|string|max:5000',
            'message_type' => 'nullable|in:text,image,audio,video',
            'chat_image' => 'nullable|image|max:10240',
            'chat_media' => 'nullable|file|max:51200',
        ]);

        if (! $request->filled('message') && ! $request->hasFile('chat_image') && ! $request->hasFile('chat_media')) {
            return json_custom_response([
                'message' => __('message.required', ['name' => __('message.chat')]),
                'success' => false,
            ], 422);
        }

        $user = auth()->user();
        $item = DispatchOrderItem::query()->with('order.client')->findOrFail((int) $request->dispatch_order_item_id);
        $this->assertCanAccessItem($user, $item);

        $senderType = $user->user_type === 'client'
            ? 'client'
            : (($user->user_type === 'delivery_man') ? 'delivery_man' : 'admin');

        $body = trim((string) $request->input('message', ''));
        $hasImage = $request->hasFile('chat_image');
        $hasMedia = $request->hasFile('chat_media');
        $messageType = 'text';
        if ($hasImage) {
            $messageType = 'image';
        }
        if ($hasMedia) {
            $mime = (string) $request->file('chat_media')->getMimeType();
            $messageType = str_starts_with($mime, 'video/') ? 'video' : 'audio';
            if ($request->filled('message_type') && in_array($request->input('message_type'), ['audio', 'video'], true)) {
                $messageType = (string) $request->input('message_type');
            }
        }

        $message = DispatchItemMessage::create([
            'dispatch_order_item_id' => $item->id,
            'order_id' => $item->order_id,
            'client_id' => $item->order?->client_id,
            'sender_id' => $user->id,
            'sender_type' => $senderType,
            'message_type' => $messageType,
            'message' => $body,
            'read_at' => null,
        ]);

        if ($hasImage) {
            uploadMediaFile($message, $request->file('chat_image'), 'chat_image');
        }
        if ($hasMedia) {
            uploadMediaFile($message, $request->file('chat_media'), 'chat_media');
        }

        // Admin/staff reply marks client messages as handled (sort unread threads down).
        // Client reply marks admin messages as read on the user inbox sort.
        if ($senderType !== 'client') {
            DispatchItemMessage::query()
                ->where('dispatch_order_item_id', $item->id)
                ->where('sender_type', 'client')
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        } else {
            DispatchItemMessage::query()
                ->where('dispatch_order_item_id', $item->id)
                ->where('sender_type', '!=', 'client')
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        // Client replies stay unread for admin until admin opens the thread.
        // Admin/staff messages notify the OS client app.
        if ($senderType !== 'client' && $item->order?->client) {
            $pushPreview = $body !== ''
                ? $body
                : ($messageType === 'audio'
                    ? '🎤 Voice message'
                    : ($messageType === 'video'
                        ? '🎬 Video'
                        : ($hasImage ? __('message.image') : '')));
            try {
                app(\App\Services\AppPushService::class)->notifyClientItemMessage(
                    $item->order->client,
                    $item,
                    (string) $pushPreview
                );
            } catch (\Throwable $e) {
                \Log::warning('push failed after api item message', [
                    'item_id' => $item->id,
                    'error' => $e->getMessage(),
                ]);
            }
        } elseif ($senderType === 'client') {
            try {
                $payload = buildDispatchItemMessageNotificationPayload(
                    $item,
                    $user,
                    $body,
                    $hasImage,
                    (int) $message->id
                );
                notifyAdminsDispatchItemMessage($payload);
            } catch (\Throwable $e) {
                \Log::warning('admin notify failed after client item message', [
                    'item_id' => $item->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return json_custom_response([
            'message' => __('message.save_form', ['form' => __('message.chat')]),
            'data' => new DispatchItemMessageResource($message->fresh(['sender', 'media'])),
        ]);
    }

    /**
     * Filter OS message threads by username (OS / customer name) and phone.
     */
    protected function filterMessageThreads($threads, Request $request)
    {
        $search = trim((string) $request->get('search', ''));
        $username = trim((string) $request->get('username', ''));
        $phone = trim((string) $request->get('phone', ''));

        if ($search === '' && $username === '' && $phone === '') {
            return $threads;
        }

        $phoneDigits = preg_replace('/\D+/', '', $phone !== '' ? $phone : $search) ?? '';

        return $threads->filter(function ($row) use ($search, $username, $phone, $phoneDigits) {
            $osName = (string) ($row['os_name'] ?? '');
            $osUsername = (string) ($row['os_username'] ?? '');
            $osPhone = (string) ($row['os_phone'] ?? '');
            $customerName = (string) ($row['customer_name'] ?? '');
            $customerPhone = (string) ($row['customer_phone'] ?? '');
            $parcelId = (string) ($row['parcel_id'] ?? '');
            $combinedPhone = trim($osPhone.' '.$customerPhone);
            $combinedPhoneDigits = preg_replace('/\D+/', '', $combinedPhone) ?? '';

            $matchUsername = static function (string $term) use ($osName, $osUsername, $customerName, $parcelId): bool {
                if ($term === '') {
                    return true;
                }

                return stripos($osName, $term) !== false
                    || stripos($osUsername, $term) !== false
                    || stripos($customerName, $term) !== false
                    || stripos($parcelId, $term) !== false;
            };

            $matchPhone = static function (string $term, string $digits) use ($combinedPhone, $combinedPhoneDigits): bool {
                if ($term === '' && $digits === '') {
                    return true;
                }

                return ($term !== '' && stripos($combinedPhone, $term) !== false)
                    || ($digits !== '' && $combinedPhoneDigits !== '' && str_contains($combinedPhoneDigits, $digits));
            };

            if ($username === '' && $phone === '' && $search !== '') {
                return $matchUsername($search) || $matchPhone($search, $phoneDigits);
            }

            return $matchUsername($username) && $matchPhone($phone, $phoneDigits);
        })->values();
    }

    protected function assertCanAccessItem($user, DispatchOrderItem $item): void
    {
        if (($user->user_type ?? '') === 'admin') {
            return;
        }

        if (method_exists($user, 'hasRole') && $user->hasRole(['admin', 'demo_admin'])) {
            return;
        }

        if (($user->user_type ?? '') === 'client' && (int) optional($item->order)->client_id === (int) $user->id) {
            return;
        }

        abort(403, __('message.demo_permission_denied'));
    }
}
