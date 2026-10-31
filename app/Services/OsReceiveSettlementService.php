<?php

namespace App\Services;

use App\Models\OsReceiveSettlement;
use App\Models\OsSettlementBatch;
use App\Models\PointOrderPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OsReceiveSettlementService
{
    public function __construct(
        protected AppPushService $pushService
    ) {}

    public function createFromBatch(OsSettlementBatch $batch, int $finishedBy, bool $notify = true): OsReceiveSettlement
    {
        $receive = OsReceiveSettlement::query()->create([
            'settlement_batch_id' => $batch->id,
            'os_user_id' => (int) $batch->os_user_id,
            'from_date' => $batch->from_date,
            'to_date' => $batch->to_date,
            'amount' => abs((float) $batch->amount),
            'status' => OsReceiveSettlement::STATUS_PENDING,
            'admin_qr_path' => $batch->kpay_slip_path,
            'finished_by' => $finishedBy,
        ]);

        if ($notify) {
            $osClient = User::query()->find((int) $batch->os_user_id);
            if ($osClient) {
                $this->notifyPending($osClient, $receive);
            }
        }

        return $receive;
    }

    public function submitOsPayslip(OsReceiveSettlement $receive, UploadedFile $file): OsReceiveSettlement
    {
        return $this->submitOsPayslips($receive, [$file]);
    }

    /**
     * @param  list<UploadedFile>  $files
     */
    public function submitOsPayslips(OsReceiveSettlement $receive, array $files): OsReceiveSettlement
    {
        if (! in_array($receive->status, [
            OsReceiveSettlement::STATUS_PENDING,
            OsReceiveSettlement::STATUS_REJECTED,
        ], true)) {
            throw new \RuntimeException(__('message.os_receive_cannot_upload'));
        }

        $files = array_values(array_filter($files, static fn ($f) => $f instanceof UploadedFile));
        if ($files === []) {
            throw new \RuntimeException(__('message.please_select_kbz_screenshot'));
        }
        if (count($files) > 10) {
            $files = array_slice($files, 0, 10);
        }

        $dir = 'os-receive/'.$receive->id;
        $oldPaths = [];
        if (is_array($receive->os_payslip_paths)) {
            $oldPaths = $receive->os_payslip_paths;
        } elseif ($receive->os_payslip_path) {
            $oldPaths = [(string) $receive->os_payslip_path];
        }

        $newPaths = [];
        foreach ($files as $file) {
            $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                $ext = 'jpg';
            }
            $filename = 'os-payslip-'.Str::random(8).'.'.$ext;
            $newPaths[] = $file->storeAs($dir, $filename, 'public');
        }

        foreach ($oldPaths as $oldPath) {
            $oldPath = (string) $oldPath;
            if ($oldPath !== '' && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $receive->update([
            'os_payslip_path' => $newPaths[0] ?? null,
            'os_payslip_paths' => $newPaths,
            'status' => OsReceiveSettlement::STATUS_WAITING,
            'os_submitted_at' => now(),
            'admin_remark' => null,
            'rejected_at' => null,
        ]);

        return $receive->fresh();
    }

    public function approve(OsReceiveSettlement $receive, int $reviewedBy): OsReceiveSettlement
    {
        if ($receive->status !== OsReceiveSettlement::STATUS_WAITING) {
            throw new \RuntimeException(__('message.os_receive_cannot_approve'));
        }

        $updated = $this->markReceived($receive, $reviewedBy);

        // Points path: Admin approve → Super Admin Confirm (Point Follow Up).
        $pointPayment = PointOrderPayment::query()
            ->where('os_receive_settlement_id', $receive->id)
            ->where('status', PointOrderPayment::STATUS_PENDING_ADMIN)
            ->latest('id')
            ->first();

        if ($pointPayment) {
            $admin = User::query()->find($reviewedBy);
            if ($admin) {
                app(PointOrderPaymentService::class)->approveByAdmin($pointPayment, $admin, null);
            }
        }

        return $updated;
    }

    /**
     * OS pays receive with Points (1:1 MMK). Points held; Admin must Approve,
     * then Super Admin Confirm on Point Follow Up.
     */
    public function payWithPoints(OsReceiveSettlement $receive, User $osUser): OsReceiveSettlement
    {
        if ((int) $receive->os_user_id !== (int) $osUser->id) {
            throw new \RuntimeException(__('message.demo_permission_denied'));
        }

        if (! in_array($receive->status, [
            OsReceiveSettlement::STATUS_PENDING,
            OsReceiveSettlement::STATUS_REJECTED,
        ], true)) {
            throw new \RuntimeException(__('message.os_receive_cannot_pay_points'));
        }

        $amount = round((float) $receive->displayAmount(), 2);
        if ($amount <= 0) {
            throw new \RuntimeException(__('message.os_receive_cannot_pay_points'));
        }

        return DB::transaction(function () use ($receive, $osUser, $amount) {
            $existingOpen = PointOrderPayment::query()
                ->where('os_receive_settlement_id', $receive->id)
                ->whereIn('status', [
                    PointOrderPayment::STATUS_PENDING_ADMIN,
                    PointOrderPayment::STATUS_PENDING_SUPER_ADMIN,
                    PointOrderPayment::STATUS_CONFIRMED,
                ])
                ->exists();

            if ($existingOpen) {
                throw new \RuntimeException(__('message.point_payment_already_exists'));
            }

            app(PointWalletService::class)->debit(
                (int) $osUser->id,
                $amount,
                'os_receive_settlement',
                (int) $osUser->id,
                __('message.os_receive_points_debit_note', [
                    'id' => $receive->id,
                    'amount' => number_format($amount),
                ]),
                [
                    'reference_id' => (int) $receive->id,
                    'reference_type' => OsReceiveSettlement::class,
                    'data' => [
                        'settlement_batch_id' => $receive->settlement_batch_id,
                        'payment_method' => 'points',
                    ],
                ]
            );

            $pointPayment = PointOrderPayment::create([
                'user_id' => (int) $osUser->id,
                'order_id' => null,
                'os_receive_settlement_id' => (int) $receive->id,
                'payment_kind' => PointOrderPayment::KIND_OS_RECEIVE,
                'points' => $amount,
                'status' => PointOrderPayment::STATUS_PENDING_ADMIN,
                'remark' => 'Os Receive #'.$receive->id.' · Paid with Points',
            ]);

            $receive->update([
                'os_payslip_path' => null,
                'os_payslip_paths' => null,
                'status' => OsReceiveSettlement::STATUS_WAITING,
                'os_submitted_at' => now(),
                'admin_remark' => 'Paid with Points',
                'rejected_at' => null,
            ]);

            app(PointOrderPaymentService::class)->notifyAdminsPendingPublic($pointPayment->fresh(['user']));

            return $receive->fresh();
        });
    }

    protected function markReceived(OsReceiveSettlement $receive, int $reviewedBy): OsReceiveSettlement
    {
        $receive->update([
            'status' => OsReceiveSettlement::STATUS_RECEIVED,
            'reviewed_by' => $reviewedBy,
            'approved_at' => now(),
            'rejected_at' => null,
        ]);

        $batch = $receive->settlementBatch;
        if ($batch) {
            try {
                app(MoneyTransferService::class)->recordFromSettlementBatch($batch);
            } catch (\Throwable $e) {
                Log::warning('os receive money transfer failed', [
                    'receive_id' => $receive->id,
                    'batch_id' => $batch->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $osClient = $receive->osUser;
        if ($osClient) {
            $this->notifyApproved($osClient, $receive);
        }

        return $receive->fresh();
    }

    public function reject(OsReceiveSettlement $receive, int $reviewedBy, string $remark): OsReceiveSettlement
    {
        if ($receive->status !== OsReceiveSettlement::STATUS_WAITING) {
            throw new \RuntimeException(__('message.os_receive_cannot_reject'));
        }

        $remark = trim($remark);
        if ($remark === '') {
            throw new \RuntimeException(__('message.os_receive_remark_required'));
        }

        return DB::transaction(function () use ($receive, $reviewedBy, $remark) {
            $pointPayment = PointOrderPayment::query()
                ->where('os_receive_settlement_id', $receive->id)
                ->whereIn('status', [
                    PointOrderPayment::STATUS_PENDING_ADMIN,
                    PointOrderPayment::STATUS_PENDING_SUPER_ADMIN,
                ])
                ->latest('id')
                ->first();

            if ($pointPayment) {
                $actor = User::query()->find($reviewedBy) ?? $receive->osUser;
                if ($actor) {
                    app(PointOrderPaymentService::class)->reject($pointPayment, $actor, $remark);
                }
            }

            $receive->update([
                'status' => OsReceiveSettlement::STATUS_REJECTED,
                'reviewed_by' => $reviewedBy,
                'admin_remark' => $remark,
                'rejected_at' => now(),
                'approved_at' => null,
            ]);

            $osClient = $receive->osUser;
            if ($osClient) {
                $this->notifyRejected($osClient, $receive);
            }

            return $receive->fresh();
        });
    }

    public function notifyPending(User $osClient, OsReceiveSettlement $receive): void
    {
        $this->pushService->notifyUser(
            $osClient,
            OsReceiveSettlement::NOTIFICATION_TYPE,
            __('message.os_receive_pending_title'),
            __('message.os_receive_pending_body', [
                'amount' => number_format($receive->displayAmount()),
                'from' => Carbon::parse($receive->from_date)->format('d-m-Y'),
                'to' => Carbon::parse($receive->to_date)->format('d-m-Y'),
            ]),
            $this->payload($receive, OsReceiveSettlement::NOTIFICATION_TYPE)
        );
    }

    public function notifyApproved(User $osClient, OsReceiveSettlement $receive): void
    {
        $this->pushService->notifyUser(
            $osClient,
            OsReceiveSettlement::NOTIFICATION_APPROVED,
            __('message.os_receive_approved_title'),
            __('message.os_receive_approved_body', [
                'amount' => number_format($receive->displayAmount()),
            ]),
            $this->payload($receive, OsReceiveSettlement::NOTIFICATION_APPROVED)
        );
    }

    public function notifyRejected(User $osClient, OsReceiveSettlement $receive): void
    {
        $this->pushService->notifyUser(
            $osClient,
            OsReceiveSettlement::NOTIFICATION_REJECTED,
            __('message.os_receive_rejected_title'),
            __('message.os_receive_rejected_body', [
                'amount' => number_format($receive->displayAmount()),
                'remark' => (string) $receive->admin_remark,
            ]),
            $this->payload($receive, OsReceiveSettlement::NOTIFICATION_REJECTED)
        );
    }

    protected function payload(OsReceiveSettlement $receive, string $type): array
    {
        return [
            'id' => 'OS_RECEIVE_'.$receive->id,
            'receive_id' => (string) $receive->id,
            'settlement_id' => (string) ($receive->settlement_batch_id ?? ''),
            'category' => $type,
            'status' => $receive->status,
            'amount' => (string) $receive->displayAmount(),
            'admin_qr_url' => $receive->adminQrUrl(),
            'os_payslip_url' => $receive->osPayslipUrl(),
            'os_payslip_urls' => $receive->osPayslipUrls(),
            'admin_remark' => (string) ($receive->admin_remark ?? ''),
            'from_date' => Carbon::parse($receive->from_date)->format('d-m-Y'),
            'to_date' => Carbon::parse($receive->to_date)->format('d-m-Y'),
            'app' => 'os',
        ];
    }

    public function toApiArray(OsReceiveSettlement $receive, ?User $viewer = null): array
    {
        $receive->loadMissing('settlementBatch');
        $batch = $receive->settlementBatch;

        $slipRows = [];
        $slipTotals = null;
        $slipTableUrl = null;
        $settlementId = null;

        if ($batch) {
            $settlementId = (int) $batch->id;
            $settlementService = app(OsSettlementService::class);
            $slipData = $settlementService->slipRowsForBatch($batch);
            $slipRows = $slipData['rows'] ?? [];
            $slipTotals = $slipData['totals'] ?? null;
            $urls = $settlementService->publicUrls($batch);
            $slipTableUrl = $urls['slip_table_url'] ?? null;
        }

        $canUpload = in_array($receive->status, [
            OsReceiveSettlement::STATUS_PENDING,
            OsReceiveSettlement::STATUS_REJECTED,
        ], true);

        $pointBalance = null;
        if ($viewer && (int) $viewer->id === (int) $receive->os_user_id) {
            $pointBalance = app(PointWalletService::class)->balance((int) $viewer->id);
        }

        $amount = (float) $receive->displayAmount();

        return [
            'id' => $receive->id,
            'settlement_batch_id' => $receive->settlement_batch_id,
            'settlement_id' => $settlementId,
            'amount' => $amount,
            'status' => $receive->status,
            'from_date' => $receive->from_date?->format('d-m-Y'),
            'to_date' => $receive->to_date?->format('d-m-Y'),
            'admin_qr_url' => $receive->adminQrUrl(),
            'os_payslip_url' => $receive->osPayslipUrl(),
            'os_payslip_urls' => $receive->osPayslipUrls(),
            'admin_remark' => $receive->admin_remark,
            'os_submitted_at' => $receive->os_submitted_at?->toIso8601String(),
            'approved_at' => $receive->approved_at?->toIso8601String(),
            'rejected_at' => $receive->rejected_at?->toIso8601String(),
            'can_upload' => $canUpload,
            'point_balance' => $pointBalance,
            'can_pay_with_points' => $canUpload && $pointBalance !== null && $pointBalance >= $amount && $amount > 0,
            'slip_rows' => $slipRows,
            'slip_totals' => $slipTotals,
            'slip_table_url' => $slipTableUrl,
        ];
    }
}
