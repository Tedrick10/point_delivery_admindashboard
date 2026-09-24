<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseSummaryTripleCheck extends Model
{
    public const KEY_SUPER_ADMIN = 'super_admin';

    public const KEY_MA_NOE_NOE = 'ma_noe_noe';

    public const KEY_MA_PHYU_SIN = 'ma_phyu_sin';

    public const KEYS = [
        self::KEY_SUPER_ADMIN,
        self::KEY_MA_NOE_NOE,
        self::KEY_MA_PHYU_SIN,
    ];

    protected $fillable = [
        'check_date',
        'checker_key',
        'user_id',
        'confirmed_at',
    ];

    protected $casts = [
        'check_date' => 'date',
        'confirmed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
