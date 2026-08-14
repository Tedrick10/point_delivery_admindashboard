<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OsSettlementDraft extends Model
{
    protected $fillable = [
        'os_user_id',
        'from_date',
        'to_date',
        'kpay_slip_path',
    ];

    protected function casts(): array
    {
        return [
            'from_date' => 'date',
            'to_date' => 'date',
        ];
    }

    public function osUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'os_user_id');
    }
}
