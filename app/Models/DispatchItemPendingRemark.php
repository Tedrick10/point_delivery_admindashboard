<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DispatchItemPendingRemark extends Model
{
    protected $fillable = [
        'dispatch_order_item_id',
        'delivery_man_id',
        'remark',
        'photo_id',
        'pending_at',
        'created_by',
    ];

    protected $casts = [
        'dispatch_order_item_id' => 'integer',
        'delivery_man_id' => 'integer',
        'photo_id' => 'integer',
        'created_by' => 'integer',
        'pending_at' => 'datetime',
    ];

    public function item()
    {
        return $this->belongsTo(DispatchOrderItem::class, 'dispatch_order_item_id');
    }

    public function deliveryMan()
    {
        return $this->belongsTo(User::class, 'delivery_man_id');
    }

    public function photoMedia()
    {
        return $this->belongsTo(Media::class, 'photo_id', 'id');
    }

    public function photoUrl(): ?string
    {
        $this->loadMissing('photoMedia');
        $media = $this->photoMedia;
        if (! $media) {
            return null;
        }

        if (function_exists('mediaPublicUrl')) {
            return mediaPublicUrl($media) ?: mediaAbsoluteUrl($media);
        }

        return function_exists('mediaAbsoluteUrl') ? mediaAbsoluteUrl($media) : null;
    }

    public function pendingDateLabel(): string
    {
        if (! $this->pending_at) {
            return '-';
        }

        return $this->pending_at->copy()->timezone('Asia/Yangon')->format('d-m-Y');
    }
}
