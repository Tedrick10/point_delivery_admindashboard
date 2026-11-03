<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointSalaryRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    public const KIND_STAFF = 'staff';

    public const KIND_CLIENT = 'client';

    protected $fillable = [
        'user_id',
        'request_kind',
        'hr_staff_id',
        'points',
        'amount',
        'points_held',
        'os_pay_settled_amount',
        'os_pay_settled_item_ids',
        'os_pay_settled_details',
        'points_remaining',
        'status',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'salary_row_id',
        'salary_period',
        'remark',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'hr_staff_id' => 'integer',
        'points' => 'float',
        'amount' => 'float',
        'points_held' => 'boolean',
        'os_pay_settled_amount' => 'float',
        'os_pay_settled_item_ids' => 'array',
        'os_pay_settled_details' => 'array',
        'points_remaining' => 'float',
        'approved_by' => 'integer',
        'approved_at' => 'datetime',
        'rejected_by' => 'integer',
        'rejected_at' => 'datetime',
        'salary_row_id' => 'integer',
    ];

    public function isClientRequest(): bool
    {
        return ($this->request_kind ?? self::KIND_STAFF) === self::KIND_CLIENT;
    }

    public function remainingPoints(): float
    {
        if ($this->points_remaining !== null) {
            return round(max(0, (float) $this->points_remaining), 2);
        }

        return round(max(0, (float) $this->points - (float) ($this->os_pay_settled_amount ?? 0)), 2);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(HrStaff::class, 'hr_staff_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
