<?php

namespace App\Services;

use App\Models\OsReceiveSettlement;
use App\Models\OsSettlementBatch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
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

        $receive->update([
            'status' => OsReceiveSettlement::STATUS_RECEIVED,
            'reviewed_by' => $reviewedBy,
            'approved_at' => now(),
            'admin_remark' => null,
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

    public function toApiArray(OsReceiveSettlement $receive): array
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

        return [
            'id' => $receive->id,
            'settlement_batch_id' => $receive->settlement_batch_id,
            'settlement_id' => $settlementId,
            'amount' => $receive->displayAmount(),
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
            'can_upload' => in_array($receive->status, [
                OsReceiveSettlement::STATUS_PENDING,
                OsReceiveSettlement::STATUS_REJECTED,
            ], true),
            'slip_rows' => $slipRows,
            'slip_totals' => $slipTotals,
            'slip_table_url' => $slipTableUrl,
        ];
    }
}
