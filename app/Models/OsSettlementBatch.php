<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OsSettlementBatch extends Model
{
    protected $fillable = [
        'os_user_id',
        'from_date',
        'to_date',
        'amount',
        'payment_method',
        'settlement_side',
        'delivery_format',
        'kpay_name',
        'kpay_no',
        'kpay_slip_path',
        'slip_table_path',
        'slip_image_path',
        'slip_pdf_path',
        'kpay_pdf_path',
        'combined_pdf_path',
        'item_ids',
        'finished_by',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'from_date' => 'date',
            'to_date' => 'date',
            'amount' => 'float',
            'item_ids' => 'array',
            'finished_at' => 'datetime',
        ];
    }

    public function osUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'os_user_id');
    }

    public function finishedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finished_by');
    }
}
