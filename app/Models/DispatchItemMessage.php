<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class DispatchItemMessage extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'dispatch_order_item_id',
        'order_id',
        'client_id',
        'sender_id',
        'sender_type',
        'message_type',
        'message',
        'read_at',
    ];

    protected $casts = [
        'dispatch_order_item_id' => 'integer',
        'order_id' => 'integer',
        'client_id' => 'integer',
        'sender_id' => 'integer',
        'read_at' => 'datetime',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('chat_image')->singleFile();
    }

    public function item()
    {
        return $this->belongsTo(DispatchOrderItem::class, 'dispatch_order_item_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function imageUrl(): ?string
    {
        if (! getMediaFileExit($this, 'chat_image')) {
            return null;
        }

        return getSingleMedia($this, 'chat_image', null) ?: null;
    }

    /**
     * Attach chat preview fields onto DispatchOrderItem models for OS client lists.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\DispatchOrderItem>|\Illuminate\Database\Eloquent\Collection<int, \App\Models\DispatchOrderItem>  $items
     */
    public static function attachClientChatMeta($items, int $clientId): void
    {
        $collection = $items instanceof \Illuminate\Support\Collection
            ? $items
            : collect($items);

        $ids = $collection->pluck('id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
        if ($ids === [] || $clientId <= 0) {
            return;
        }

        $stats = static::query()
            ->select([
                'dispatch_order_item_id',
                \Illuminate\Support\Facades\DB::raw('COUNT(*) as message_count'),
                \Illuminate\Support\Facades\DB::raw("SUM(CASE WHEN sender_type != 'client' AND read_at IS NULL THEN 1 ELSE 0 END) as unread_count"),
                \Illuminate\Support\Facades\DB::raw('MAX(id) as last_message_id'),
            ])
            ->where('client_id', $clientId)
            ->whereIn('dispatch_order_item_id', $ids)
            ->groupBy('dispatch_order_item_id')
            ->get()
            ->keyBy('dispatch_order_item_id');

        $lastMessages = static::query()
            ->with('media')
            ->whereIn('id', $stats->pluck('last_message_id')->filter()->all())
            ->get()
            ->keyBy('id');

        foreach ($collection as $item) {
            $row = $stats->get($item->id);
            if (! $row) {
                $item->chat_message_count = 0;
                $item->chat_unread_count = 0;
                $item->last_chat_message = null;
                continue;
            }

            $last = $lastMessages->get($row->last_message_id);
            $lastText = trim((string) ($last?->message ?? ''));
            if ($lastText === '' && $last && getMediaFileExit($last, 'chat_image')) {
                $lastText = '[Image]';
            }

            $item->chat_message_count = (int) $row->message_count;
            $item->chat_unread_count = (int) $row->unread_count;
            $item->last_chat_message = $lastText !== '' ? $lastText : null;
        }
    }
}
