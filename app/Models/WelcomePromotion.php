<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WelcomePromotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'max_orders', 'discount_type', 'discount_value', 'status',
    ];

    protected $casts = [
        'max_orders' => 'integer',
        'discount_value' => 'float',
        'status' => 'integer',
    ];

    public static function getActive()
    {
        return static::where('status', 1)->first();
    }

    public function calculateDiscount(float $amount): float
    {
        if ($this->discount_type === 'percentage') {
            return round($amount * $this->discount_value / 100, 2);
        }
        return min($this->discount_value, $amount);
    }
}
