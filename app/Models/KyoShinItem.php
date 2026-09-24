<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class KyoShinItem extends Model
{
    public const STATUS_ADVANCED_PAID = 'advanced_paid';
    public const STATUS_FINISHED = 'finished';

    protected $fillable = [
        'batch_id',
        'dispatch_order_item_id',
        'scope_key',
        'branch_id',
        'os_user_id',
        'amount',
        'payment_method',
        'status',
        'advanced_paid_at',
        'advanced_paid_by',
        'due_finished_at',
        'finished_at',
        'finished_by',
        'checked_at',
        'checked_by',
        'received_at',
        'received_by',
        'last_overdue_notified_on',
    ];

    protected function casts(): array
    {
        return [
            'os_user_id' => 'integer',
            'branch_id' => 'integer',
            'amount' => 'float',
            'advanced_paid_at' => 'datetime',
            'due_finished_at' => 'date',
            'finished_at' => 'datetime',
            'checked_at' => 'datetime',
            'received_at' => 'datetime',
            'last_overdue_notified_on' => 'date',
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

    public function batch(): BelongsTo
    {
        return $this->belongsTo(KyoShinBatch::class, 'batch_id');
    }
}
