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
     * Threads for the logged-in OS client: items that have messages, with unread counts.
     */
    public function threads(Request $request)
    {
        $user = auth()->user();
        $clientId = (int) $user->id;

        $threads = DispatchItemMessage::query()
            ->select([
                'dispatch_order_item_id',
                DB::raw('MAX(id) as last_message_id'),
                DB::raw('COUNT(*) as message_count'),
                DB::raw("SUM(CASE WHEN sender_type != 'client' AND read_at IS NULL THEN 1 ELSE 0 END) as unread_count"),
            ])
            ->where('client_id', $clientId)
            ->groupBy('dispatch_order_item_id')
            ->orderByDesc('last_message_id')
            ->get();

        $itemIds = $threads->pluck('dispatch_order_item_id')->all();
        $items = DispatchOrderItem::query()
            ->with(['order:id,client_id'])
            ->whereIn('id', $itemIds)
            ->get()
            ->keyBy('id');

        $lastMessages = DispatchItemMessage::query()
            ->with('media')
            ->whereIn('id', $threads->pluck('last_message_id')->filter()->all())
            ->get()
            ->keyBy('id');

        $osName = trim((string) ($user->name ?? ''));

        $data = $threads->map(function ($row) use ($items, $lastMessages, $osName) {
            $item = $items->get($row->dispatch_order_item_id);
            $last = $lastMessages->get($row->last_message_id);
            $lastText = trim((string) ($last?->message ?? ''));
            if ($lastText === '' && $last && getMediaFileExit($last, 'chat_image')) {
                $lastText = '[Image]';
            }

            $senderIsOs = ($last?->sender_type === 'client');

            return [
                'dispatch_order_item_id' => (int) $row->dispatch_order_item_id,
                'order_id' => $item?->order_id,
                'parcel_id' => $item?->code ?: ('#'.$row->dispatch_order_item_id),
                'os_name' => $osName !== '' ? $osName : null,
                'customer_name' => trim((string) ($item?->customer_name ?? '')) ?: null,
                'customer_phone' => trim((string) ($item?->customer_phone ?? '')) ?: null,
                'message_count' => (int) $row->message_count,
                'unread_count' => (int) $row->unread_count,
                'last_message' => $lastText !== '' ? $lastText : null,
                'last_message_at' => optional($last?->created_at)?->toDateTimeString(),
                'last_sender_type' => $last?->sender_type,
                'last_sender_label' => $senderIsOs
                    ? ($osName !== '' ? $osName : 'Os')
                    : 'Admin',
            ];
        })
            // Unread / unreplied threads stay on top; read/replied sink to the bottom.
            ->sort(function ($a, $b) {
                $aUnread = ((int) ($a['unread_count'] ?? 0) > 0) ? 1 : 0;
                $bUnread = ((int) ($b['unread_count'] ?? 0) > 0) ? 1 : 0;
                if ($aUnread !== $bUnread) {
                    return $bUnread <=> $aUnread;
                }

                return strcmp((string) ($b['last_message_at'] ?? ''), (string) ($a['last_message_at'] ?? ''));
            })
            ->values();

        $totalUnread = (int) $data->filter(function ($row) {
            return (int) ($row['unread_count'] ?? 0) > 0;
        })->count();

        return json_custom_response([
            'data' => $data,
            'total_unread' => $totalUnread,
        ]);
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
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'dispatch_order_item_id' => 'required|integer',
            'message' => 'nullable|string|max:5000',
            'chat_image' => 'nullable|image|max:10240',
        ]);

        if (! $request->filled('message') && ! $request->hasFile('chat_image')) {
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

        $message = DispatchItemMessage::create([
            'dispatch_order_item_id' => $item->id,
            'order_id' => $item->order_id,
            'client_id' => $item->order?->client_id,
            'sender_id' => $user->id,
            'sender_type' => $senderType,
            'message_type' => $hasImage ? 'image' : 'text',
            'message' => $body,
            'read_at' => null,
        ]);

        if ($hasImage) {
            uploadMediaFile($message, $request->file('chat_image'), 'chat_image');
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
            $pushPreview = $body !== '' ? $body : ($hasImage ? __('message.image') : '');
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
        }

        return json_custom_response([
            'message' => __('message.save_form', ['form' => __('message.chat')]),
            'data' => new DispatchItemMessageResource($message->fresh(['sender', 'media'])),
        ]);
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
