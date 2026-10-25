<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminKpayAccount extends Model
{
    protected $fillable = [
        'settlement_date',
        'branch_id',
        'kpay_name',
        'kpay_phone',
        'amount',
        'balance',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'settlement_date' => 'date',
            'branch_id' => 'integer',
            'amount' => 'float',
            'balance' => 'float',
            'created_by' => 'integer',
        ];
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * Deduct transfer amount from remaining balance (atomic).
     */
    public function deduct(float $transferAmount): void
    {
        $need = round(abs($transferAmount), 2);
        if ($need <= 0) {
            return;
        }

        $locked = static::query()->where('id', $this->id)->lockForUpdate()->first();
        if (! $locked || (float) $locked->balance + 0.00001 < $need) {
            throw new \RuntimeException(__('message.admin_kpay_insufficient_balance'));
        }

        $locked->balance = round((float) $locked->balance - $need, 2);
        $locked->save();
        $this->refresh();
    }

    public function label(): string
    {
        $name = trim((string) $this->kpay_name);
        $phone = trim((string) $this->kpay_phone);
        $bal = number_format((float) $this->balance);

        return trim($name.($phone !== '' ? ' · '.$phone : '').' ('.$bal.')');
    }
}
