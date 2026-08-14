<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DispatchItemMessageResource extends JsonResource
{
    public function toArray($request)
    {
        $imageUrl = $this->imageUrl();

        return [
            'id' => $this->id,
            'dispatch_order_item_id' => $this->dispatch_order_item_id,
            'order_id' => $this->order_id,
            'client_id' => $this->client_id,
            'sender_id' => $this->sender_id,
            'sender_type' => $this->sender_type,
            'sender_name' => optional($this->sender)->name,
            'message_type' => $this->message_type ?: ($imageUrl ? 'image' : 'text'),
            'message' => $this->message,
            'chat_image' => $imageUrl,
            'read_at' => optional($this->read_at)?->toDateTimeString(),
            'created_at' => optional($this->created_at)?->toDateTimeString(),
            'is_mine' => auth()->id() && (int) $this->sender_id === (int) auth()->id(),
        ];
    }
}
