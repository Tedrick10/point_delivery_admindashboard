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

    protected $fillable = [
        'user_id',
        'hr_staff_id',
        'points',
        'amount',
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
        'approved_by' => 'integer',
        'approved_at' => 'datetime',
        'rejected_by' => 'integer',
        'rejected_at' => 'datetime',
        'salary_row_id' => 'integer',
    ];

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
