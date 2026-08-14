<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OsCashPayout extends Model
{
    public const STATUS_UNASSIGNED = 'unassigned';

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_PENDING = 'pending';

    public const STATUS_DONE = 'done';

    protected $fillable = [
        'os_user_id',
        'settlement_batch_id',
        'money_transfer_id',
        'period_from',
        'period_to',
        'amount',
        'slip_photo_path',
        'status',
        'delivery_man_id',
        'assigned_at',
        'pending_at',
        'done_at',
        'pending_note',
        'pending_photo_path',
        'done_note',
        'done_photo_path',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'os_user_id' => 'integer',
            'settlement_batch_id' => 'integer',
            'money_transfer_id' => 'integer',
            'period_from' => 'date',
            'period_to' => 'date',
            'amount' => 'double',
            'delivery_man_id' => 'integer',
            'assigned_at' => 'datetime',
            'pending_at' => 'datetime',
            'done_at' => 'datetime',
            'created_by' => 'integer',
        ];
    }

    public function osUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'os_user_id', 'id');
    }

    public function deliveryMan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivery_man_id', 'id');
    }

    public function settlementBatch(): BelongsTo
    {
        return $this->belongsTo(OsSettlementBatch::class, 'settlement_batch_id', 'id');
    }

    public function moneyTransfer(): BelongsTo
    {
        return $this->belongsTo(OsMoneyTransfer::class, 'money_transfer_id', 'id');
    }

    public function pendingPhotoUrl(): ?string
    {
        return $this->publicUrl($this->pending_photo_path);
    }

    public function donePhotoUrl(): ?string
    {
        return $this->publicUrl($this->done_photo_path);
    }

    /** Admin Cash Finish proof image shown on Assign / rider cards. */
    public function slipPhotoUrl(): ?string
    {
        if ($url = $this->publicUrl($this->slip_photo_path)) {
            return $url;
        }

        $batchPath = $this->settlementBatch?->kpay_slip_path;

        return $this->publicUrl($batchPath);
    }

    protected function publicUrl(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        // Always expose a public URL when a path is stored (avoid hiding
        // rider uploads if exists() fails due to symlink / path quirks).
        return asset('storage/'.ltrim($path, '/'));
    }
}
