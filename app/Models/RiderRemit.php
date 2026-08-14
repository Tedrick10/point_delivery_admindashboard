<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiderRemit extends Model
{
    public const DENOMS = [10000, 5000, 1000, 500, 200, 100, 50];

    protected $fillable = [
        'remit_date',
        'branch_id',
        'delivery_man_id',
        'due_amount',
        'prepaid_amount',
        'fuel_amount',
        'fee_amount',
        'denominations',
        'kpay_amount',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'remit_date' => 'date',
            'branch_id' => 'integer',
            'delivery_man_id' => 'integer',
            'due_amount' => 'double',
            'prepaid_amount' => 'double',
            'fuel_amount' => 'double',
            'fee_amount' => 'double',
            'denominations' => 'array',
            'kpay_amount' => 'double',
            'updated_by' => 'integer',
        ];
    }

    public function deliveryMan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivery_man_id', 'id');
    }

    public function denomCount(int $note): int
    {
        $map = $this->denominations ?? [];

        return (int) ($map[(string) $note] ?? $map[$note] ?? 0);
    }

    public function cashTotal(): float
    {
        $total = 0.0;
        foreach (self::DENOMS as $note) {
            $total += $note * $this->denomCount($note);
        }

        return round($total, 2);
    }

    public function remaining(): float
    {
        return round(
            (float) $this->due_amount
            - (float) $this->prepaid_amount
            - (float) $this->fuel_amount
            - (float) $this->fee_amount,
            2
        );
    }

    public function combined(): float
    {
        return round($this->cashTotal() + (float) $this->kpay_amount, 2);
    }

    public function isBalanced(): bool
    {
        return abs($this->combined() - $this->remaining()) < 0.51;
    }
}
