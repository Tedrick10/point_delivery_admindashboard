<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KyoShinDailyLedger extends Model
{
    protected $fillable = [
        'scope_key',
        'ledger_date',
        'sa_amount',
        'balance',
        'cash_held',
        'returned_amount',
        'os_receivable',
    ];

    protected function casts(): array
    {
        return [
            'ledger_date' => 'date',
            'sa_amount' => 'float',
            'balance' => 'float',
            'cash_held' => 'float',
            'returned_amount' => 'float',
            'os_receivable' => 'float',
        ];
    }
}
