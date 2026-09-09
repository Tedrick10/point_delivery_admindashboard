<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryTownship extends Model
{
    protected $fillable = [
        'delivery_city_id',
        'name',
        'name_mm',
        'deli_amount',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'delivery_city_id' => 'integer',
        'deli_amount' => 'float',
        'sort_order' => 'integer',
        'status' => 'integer',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(DeliveryCity::class, 'delivery_city_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function displayName(): string
    {
        $mm = trim((string) $this->name_mm);

        return $mm !== '' ? $mm : (string) $this->name;
    }
}
