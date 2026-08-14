<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyCheckInvoice extends Model
{
    public const PARTY_OS = 'os';

    public const PARTY_RIDER = 'rider';

    protected $fillable = [
        'invoice_no',
        'party_type',
        'party_user_id',
        'branch_id',
        'received_date',
        'remitted_date',
        'remitted_photo_path',
        'remitted_by',
        'item_ids',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'party_user_id' => 'integer',
            'branch_id' => 'integer',
            'received_date' => 'date',
            'remitted_date' => 'date',
            'remitted_by' => 'integer',
            'created_by' => 'integer',
            'item_ids' => 'array',
        ];
    }

    public function partyUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'party_user_id', 'id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

    public function remittedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'remitted_by', 'id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function remittedPhotoUrl(): ?string
    {
        if (! $this->remitted_photo_path) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->remitted_photo_path);
    }

    public function isOs(): bool
    {
        return $this->party_type === self::PARTY_OS;
    }

    public function isRider(): bool
    {
        return $this->party_type === self::PARTY_RIDER;
    }
}
