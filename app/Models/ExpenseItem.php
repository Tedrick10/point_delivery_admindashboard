<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseItem extends Model
{
    public const DEMO_IMAGE = 'images/demo/expense-receipt.svg';

    protected $fillable = [
        'expense_card_id',
        'subject',
        'amount',
        'image',
        'sort_order',
        'source',
    ];

    public const SOURCE_RIDER_FUEL = 'rider_fuel';

    public function hasImage(): bool
    {
        $path = trim((string) ($this->image ?? ''));

        return $path !== '' && $path !== self::DEMO_IMAGE;
    }

    public function imageUrl(): ?string
    {
        if (! $this->hasImage()) {
            return null;
        }

        $path = trim((string) $this->image);

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, 'images/')) {
            return asset($path);
        }

        return asset('storage/'.$path);
    }

    protected $casts = [
        'amount' => 'float',
        'sort_order' => 'integer',
    ];

    public function card(): BelongsTo
    {
        return $this->belongsTo(ExpenseCard::class, 'expense_card_id');
    }
}
