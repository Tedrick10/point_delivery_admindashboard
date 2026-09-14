<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KyoShinItem extends Model
{
    public const STATUS_ADVANCED_PAID = 'advanced_paid';
    public const STATUS_FINISHED = 'finished';

    protected $fillable = [
        'dispatch_order_item_id',
        'scope_key',
        'os_user_id',
        'amount',
        'status',
        'advanced_paid_at',
        'advanced_paid_by',
        'finished_at',
        'finished_by',
    ];

    protected function casts(): array
    {
        return [
            'os_user_id' => 'integer',
            'amount' => 'float',
            'advanced_paid_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function dispatchItem(): BelongsTo
    {
        return $this->belongsTo(DispatchOrderItem::class, 'dispatch_order_item_id');
    }

    public function osUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'os_user_id');
    }
}
