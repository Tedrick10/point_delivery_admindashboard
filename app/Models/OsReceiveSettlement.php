<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class OsReceiveSettlement extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_WAITING = 'waiting';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_REJECTED = 'rejected';

    public const NOTIFICATION_TYPE = 'os_receive_pay';

    public const NOTIFICATION_APPROVED = 'os_receive_approved';

    public const NOTIFICATION_REJECTED = 'os_receive_rejected';

    protected $fillable = [
        'settlement_batch_id',
        'os_user_id',
        'from_date',
        'to_date',
        'amount',
        'status',
        'admin_qr_path',
        'os_payslip_path',
        'os_payslip_paths',
        'admin_remark',
        'finished_by',
        'reviewed_by',
        'os_submitted_at',
        'approved_at',
        'rejected_at',
    ];

    protected function casts(): array
    {
        return [
            'from_date' => 'date',
            'to_date' => 'date',
            'amount' => 'float',
            'os_payslip_paths' => 'array',
            'os_submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function osUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'os_user_id');
    }

    public function settlementBatch(): BelongsTo
    {
        return $this->belongsTo(OsSettlementBatch::class, 'settlement_batch_id');
    }

    public function finishedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finished_by');
    }

    public function reviewedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function adminQrUrl(): ?string
    {
        if (! $this->admin_qr_path) {
            return null;
        }

        return Storage::disk('public')->url($this->admin_qr_path);
    }

    public function osPayslipUrl(): ?string
    {
        $urls = $this->osPayslipUrls();

        return $urls[0] ?? null;
    }

    /**
     * @return list<string>
     */
    public function osPayslipUrls(): array
    {
        $paths = [];
        $stored = $this->os_payslip_paths;
        if (is_array($stored)) {
            foreach ($stored as $path) {
                $path = trim((string) $path);
                if ($path !== '') {
                    $paths[] = $path;
                }
            }
        }
        if ($paths === [] && $this->os_payslip_path) {
            $paths[] = (string) $this->os_payslip_path;
        }

        $urls = [];
        foreach ($paths as $path) {
            if (Storage::disk('public')->exists($path)) {
                $urls[] = Storage::disk('public')->url($path);
            } else {
                // Still expose URL for remote/missing local checks
                $urls[] = Storage::disk('public')->url($path);
            }
        }

        return array_values(array_unique($urls));
    }

    public function displayAmount(): float
    {
        return abs((float) $this->amount);
    }
}
