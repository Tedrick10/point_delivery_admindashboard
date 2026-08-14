<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'sender_id', 'sender_type', 'way_type',
        'message', 'message_type', 'image_url',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'sender_id' => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
