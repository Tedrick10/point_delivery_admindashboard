<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OsMoneyTransfer extends Model
{
    protected $fillable = [
        'period_from',
        'period_to',
        'branch_id',
        'os_user_id',
        'payment_method',
        'settlement_batch_id',
        'cash_amount',
        'kpay_amount',
        'freight_amount',
        'remark',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'period_from' => 'date',
            'period_to' => 'date',
            'branch_id' => 'integer',
            'os_user_id' => 'integer',
            'settlement_batch_id' => 'integer',
            'cash_amount' => 'double',
            'kpay_amount' => 'double',
            'freight_amount' => 'double',
            'updated_by' => 'integer',
        ];
    }

    public function osUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'os_user_id', 'id');
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function cashPayout(): HasOne
    {
        return $this->hasOne(OsCashPayout::class, 'money_transfer_id', 'id');
    }
}
