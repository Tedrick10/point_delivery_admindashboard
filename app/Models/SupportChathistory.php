<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class SupportChathistory extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = ['support_id', 'user_id', 'message', 'message_type', 'datetime'];

    protected $casts = [
        'user_id' => 'integer',
        'support_id' => 'integer',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('chat_image')->singleFile();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function customersupport()
    {
        return $this->belongsTo(CustomerSupport::class, 'support_id', 'id');
    }
}
