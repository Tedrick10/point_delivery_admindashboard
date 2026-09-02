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
        return $this->imagePath() !== '';
    }

    public function hasUploadedImage(): bool
    {
        $path = $this->imagePath();

        return $path !== '' && $path !== self::DEMO_IMAGE;
    }

    public function imageUrl(): ?string
    {
        $path = $this->imagePath();
        // Never fall back to the demo receipt — users must upload a real image.
        if ($path === '' || $path === self::DEMO_IMAGE) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, 'images/')) {
            return asset($path);
        }

        return asset('storage/'.$path);
    }

    private function imagePath(): string
    {
        return trim((string) ($this->image ?? ''));
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
