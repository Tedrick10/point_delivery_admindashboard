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

    public const SETTLEMENT_MANUAL = 'manual';

    public const SETTLEMENT_MANUAL_HALF_DELI = 'manual_half_deli';

    public const SETTLEMENT_HALF_DELI = 'half_deli';

    /** @var list<string> */
    public const SETTLEMENT_MODES = [
        self::SETTLEMENT_MANUAL,
        self::SETTLEMENT_MANUAL_HALF_DELI,
        self::SETTLEMENT_HALF_DELI,
    ];

    protected $fillable = [
        'name',
        'code',
        'city_name',
        'address',
        'phone',
        'status',
        'delivery_settlement_mode',
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

    public function settlementMode(): string
    {
        $mode = (string) ($this->delivery_settlement_mode ?? self::SETTLEMENT_MANUAL);

        return in_array($mode, self::SETTLEMENT_MODES, true)
            ? $mode
            : self::SETTLEMENT_MANUAL;
    }

    public static function normalizeSettlementMode(?string $mode): string
    {
        $mode = trim((string) $mode);

        return in_array($mode, self::SETTLEMENT_MODES, true)
            ? $mode
            : self::SETTLEMENT_MANUAL;
    }
}
