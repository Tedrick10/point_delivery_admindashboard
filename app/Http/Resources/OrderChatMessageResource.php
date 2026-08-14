<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderChatMessageResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'sender_id' => $this->sender_id,
            'sender_name' => optional($this->sender)->name,
            'sender_type' => $this->sender_type,
            'way_type' => $this->way_type,
            'message' => $this->message,
            'message_type' => $this->message_type,
            'image_url' => $this->image_url,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
