<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KyoShinBatch extends Model
{
    protected $fillable = [
        'os_user_id',
        'order_id',
        'payment_method',
        'kpay_name',
        'kpay_no',
        'slip_photo_path',
        'slip_photo_paths',
        'slip_table_path',
        'due_finished_at',
        'amount',
        'item_ids',
        'cash_payout_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'os_user_id' => 'integer',
            'order_id' => 'integer',
            'amount' => 'float',
            'due_finished_at' => 'date',
            'item_ids' => 'array',
            'slip_photo_paths' => 'array',
            'cash_payout_id' => 'integer',
        ];
    }

    public function osUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'os_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(KyoShinItem::class, 'batch_id');
    }

    public function cashPayout(): BelongsTo
    {
        return $this->belongsTo(OsCashPayout::class, 'cash_payout_id');
    }

    /**
     * @return list<string>
     */
    public function slipPhotoPaths(): array
    {
        $paths = $this->slip_photo_paths;
        if (! is_array($paths) || $paths === []) {
            $single = trim((string) $this->slip_photo_path);
            return $single !== '' ? [$single] : [];
        }

        return array_values(array_filter(array_map(
            static fn ($path) => trim((string) $path),
            $paths
        )));
    }

    /**
     * @return list<string>
     */
    public function slipPhotoUrls(): array
    {
        return array_values(array_filter(array_map(
            static fn (string $path) => asset('storage/'.ltrim($path, '/')),
            $this->slipPhotoPaths()
        )));
    }

    public function slipPhotoUrl(): ?string
    {
        return $this->slipPhotoUrls()[0] ?? null;
    }

    public function slipTableUrl(): ?string
    {
        $path = trim((string) $this->slip_table_path);
        if ($path === '') {
            return null;
        }

        return asset('storage/'.ltrim($path, '/'));
    }
};
