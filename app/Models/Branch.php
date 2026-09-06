<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'city_name',
        'address',
        'phone',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'integer',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'branch_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function displayLabel(): string
    {
        $city = trim((string) $this->city_name);
        if ($city !== '') {
            return $city;
        }

        return (string) $this->name;
    }
}
