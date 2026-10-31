<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointHistory extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'transaction_type',
        'amount',
        'balance',
        'order_id',
        'reference_id',
        'reference_type',
        'created_by',
        'description',
        'data',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'amount' => 'float',
        'balance' => 'float',
        'order_id' => 'integer',
        'reference_id' => 'integer',
        'created_by' => 'integer',
        'data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
