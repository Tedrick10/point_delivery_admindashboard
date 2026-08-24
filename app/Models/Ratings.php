<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ratings extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'review_user_id',
        'order_id',
        'dispatch_order_item_id',
        'rating',
        'comment',
        'rating_by',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'review_user_id' => 'integer',
        'order_id' => 'integer',
        'dispatch_order_item_id' => 'integer',
        'rating' => 'double',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id')->withTrashed();
    }

    public function reviewUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'review_user_id', 'id')->withTrashed();
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function dispatchOrderItem(): BelongsTo
    {
        return $this->belongsTo(DispatchOrderItem::class, 'dispatch_order_item_id', 'id');
    }

    /** Quick-pick feedback messages for rider rating UI (Myanmar). */
    public static function presetComments(): array
    {
        return [
            __('message.rider_rating_preset_ontime', [], 'my'),
            __('message.rider_rating_preset_polite', [], 'my'),
            __('message.rider_rating_preset_careful', [], 'my'),
            __('message.rider_rating_preset_good_service', [], 'my'),
            __('message.rider_rating_preset_fast', [], 'my'),
            __('message.rider_rating_preset_recommend', [], 'my'),
        ];
    }
}
