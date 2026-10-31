<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointOrderPayment extends Model
{
    public const STATUS_PENDING_ADMIN = 'pending_admin';
    public const STATUS_PENDING_SUPER_ADMIN = 'pending_super_admin';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    public const KIND_ORDER = 'order';

    public const KIND_OS_RECEIVE = 'os_receive';

    protected $fillable = [
        'user_id',
        'order_id',
        'os_receive_settlement_id',
        'payment_kind',
        'points',
        'status',
        'admin_approved_by',
        'admin_approved_at',
        'super_admin_confirmed_by',
        'super_admin_confirmed_at',
        'rejected_by',
        'rejected_at',
        'remark',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'order_id' => 'integer',
        'os_receive_settlement_id' => 'integer',
        'points' => 'float',
        'admin_approved_by' => 'integer',
        'admin_approved_at' => 'datetime',
        'super_admin_confirmed_by' => 'integer',
        'super_admin_confirmed_at' => 'datetime',
        'rejected_by' => 'integer',
        'rejected_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function osReceiveSettlement(): BelongsTo
    {
        return $this->belongsTo(OsReceiveSettlement::class, 'os_receive_settlement_id');
    }

    public function isOsReceive(): bool
    {
        return ($this->payment_kind ?? self::KIND_ORDER) === self::KIND_OS_RECEIVE
            || (int) ($this->os_receive_settlement_id ?? 0) > 0;
    }

    public function adminApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_approved_by');
    }

    public function superAdminConfirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'super_admin_confirmed_by');
    }
}
