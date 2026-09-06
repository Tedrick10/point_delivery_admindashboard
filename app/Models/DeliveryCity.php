<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryCity extends Model
{
    protected $fillable = [
        'name',
        'name_mm',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'status' => 'integer',
    ];

    public function townships(): HasMany
    {
        return $this->hasMany(DeliveryTownship::class)->orderBy('sort_order')->orderBy('name_mm')->orderBy('name');
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

    public function matchesName(string $value): bool
    {
        $value = mb_strtolower(trim($value));
        if ($value === '') {
            return false;
        }

        return mb_strtolower((string) $this->name) === $value
            || mb_strtolower((string) $this->name_mm) === $value;
    }
}
