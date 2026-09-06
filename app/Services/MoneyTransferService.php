<?php

namespace App\Services;

use App\Models\DailyCheckInvoice;
use App\Models\DispatchOrderItem;
use App\Models\OsCashPayout;
use App\Models\OsMoneyTransfer;
use App\Models\OsSettlementBatch;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class MoneyTransferService
{
    public function __construct(
        protected DailyCheckListService $dailyCheck
    ) {}

    public function parseDate(string $value): \Carbon\Carbon
    {
        return $this->dailyCheck->parseDate($value);
    }

    /**
     * OS-level transfer sheet rows for the selected period (sheet-style).
     *
     * @return array{rows: Collection<int, object>, summary: object}
     */
    public function listSheet(
        string $fromDay,
        string $toDay,
        ?int $branchId,
        ?int $osId = null,
        ?string $paymentMethod = null
    ): array {
        $this->backfillTransferBranches();

        if ($paymentMethod === 'cash') {
            return $this->listCashFinishRows($fromDay, $toDay, $osId, $branchId);
        }

        if ($paymentMethod === 'all') {
            $kpaySheet = $this->listSheet($fromDay, $toDay, $branchId, $osId, 'kpay');
            $cashSheet = $this->listCashFinishRows($fromDay, $toDay, $osId, $branchId);
            $rows = $kpaySheet['rows']->concat($cashSheet['rows'])
                ->sortBy(function ($row) {
                    return mb_strtolower((string) ($row->name ?? '')).'|'.((string) ($row->payment_method ?? 'kpay'));
                })
                ->values();
            $totals = $this->finishTotals($fromDay, $toDay, $osId, $branchId);

            return [
                'rows' => $rows,
                'summary' => (object) [
                    'os_count' => $rows->pluck('os_user_id')->unique()->filter()->count(),
                    'amount_due' => $totals['total'],
                    'cash_total' => $totals['cash'],
                    'kpay_total' => $totals['kpay'],
                    'freight_total' => round((float) $rows->sum('freight_amount'), 2),
                ],
            ];
        }

        $invoiceRows = $this->dailyCheck->listRows($fromDay, $toDay, 'os', $branchId, $osId, null);
        if ($branchId && $branchId > 0) {
            $invoiceRows = $invoiceRows->filter(fn ($row) => (int) ($row->branch_id ?? 0) === $branchId)->values();
        }
        $grouped = $invoiceRows->groupBy(fn ($row) => (int) $row->party_user_id);

        $saved = OsMoneyTransfer::query()
            ->with(['cashPayout.deliveryMan', 'settlementBatch'])
            ->where(function ($q) use ($fromDay, $toDay) {
                $this->applyTransferPeriod($q, $fromDay, $toDay);
            })
            ->when($branchId !== null && $branchId > 0, fn ($q) => $q->where('branch_id', $branchId))
            ->when($osId !== null, fn ($q) => $q->where('os_user_id', $osId))
            ->when($paymentMethod !== null && $paymentMethod !== '', fn ($q) => $q->where('payment_method', $paymentMethod))
            ->orderByDesc('id')
            ->get()
            ->unique(fn (OsMoneyTransfer $t) => (int) $t->os_user_id)
            ->keyBy(fn (OsMoneyTransfer $t) => (int) $t->os_user_id);

        $osIds = $grouped->keys()->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0)->values();
        if ($paymentMethod === 'kpay' || $paymentMethod === 'cash') {
            $osIds = $saved->keys()->map(fn ($id) => (int) $id)->values();

            $batchOs = $this->batchesInRange($fromDay, $toDay, $osId, $paymentMethod, $branchId)
                ->pluck('os_user_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $payoutOs = [];
            if ($paymentMethod === 'cash') {
                $payoutOs = OsCashPayout::query()
                    ->with(['settlementBatch', 'moneyTransfer'])
                    ->where(function ($q) use ($fromDay, $toDay) {
                        $this->applyPayoutPeriod($q, $fromDay, $toDay);
                    })
                    ->when($osId !== null, fn ($q) => $q->where('os_user_id', $osId))
                    ->get()
                    ->filter(fn (OsCashPayout $p) => $this->payoutMatchesBranch($p, $branchId))
                    ->pluck('os_user_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
            }

            $osIds = collect($osIds)->merge($batchOs)->merge($payoutOs)->unique()->filter(fn ($id) => (int) $id > 0)->values();
        }

        $users = User::query()
            ->whereIn('id', $osIds)
            ->with('userBankAccount')
            ->get()
            ->keyBy('id');

        // Cash payouts keyed by transfer id + settlement batch id for cash tab columns.
        $payoutsByTransfer = collect();
        $payoutsByBatch = collect();
        $payoutsByOs = collect();
        if ($paymentMethod === 'cash') {
            $transferIds = $saved->pluck('id')->filter()->map(fn ($id) => (int) $id)->values()->all();
            $batchIds = $saved->pluck('settlement_batch_id')->filter()->map(fn ($id) => (int) $id)->values()->all();
            $payouts = OsCashPayout::query()
                ->with('deliveryMan')
                ->where(function ($q) use ($transferIds, $batchIds, $fromDay, $toDay, $osId) {
                    $q->where(function ($inner) use ($fromDay, $toDay) {
                        $inner->whereDate('period_from', '<=', $toDay)
                            ->whereDate('period_to', '>=', $fromDay);
                    });
                    if ($transferIds !== []) {
                        $q->orWhereIn('money_transfer_id', $transferIds);
                    }
                    if ($batchIds !== []) {
                        $q->orWhereIn('settlement_batch_id', $batchIds);
                    }
                })
                ->when($osId !== null, fn ($q) => $q->where('os_user_id', $osId))
                ->orderByDesc('id')
                ->get();
            $payoutsByTransfer = $payouts->filter(fn ($p) => (int) $p->money_transfer_id > 0)
                ->keyBy(fn ($p) => (int) $p->money_transfer_id);
            $payoutsByBatch = $payouts->filter(fn ($p) => (int) $p->settlement_batch_id > 0)
                ->groupBy(fn ($p) => (int) $p->settlement_batch_id)
                ->map(fn ($group) => $group->first());
            $payoutsByOs = $payouts->groupBy(fn ($p) => (int) $p->os_user_id)
                ->map(fn ($group) => $group->first());
        }

        // Finished settlement batches (latest per OS) for the selected method.
        $methodBatchesById = collect();
        $methodBatchesByOs = collect();
        if (in_array($paymentMethod, ['kpay', 'cash', null, ''], true)) {
            $methodKey = in_array($paymentMethod, ['kpay', 'cash'], true) ? $paymentMethod : 'kpay';
            $methodBatches = $this->batchesInRange($fromDay, $toDay, $osId, $methodKey, $branchId)
                ->when($osIds->isNotEmpty(), fn (Collection $c) => $c->filter(fn ($b) => $osIds->contains((int) $b->os_user_id))->values());
            $methodBatchesById = $methodBatches->keyBy(fn (OsSettlementBatch $b) => (int) $b->id);
            $methodBatchesByOs = $methodBatches
                ->groupBy(fn (OsSettlementBatch $b) => (int) $b->os_user_id)
                ->map(fn (Collection $group) => $group->first());
        }
        $kpayBatchesById = $methodBatchesById;
        $kpayBatchesByOs = $methodBatchesByOs;

        $settlement = app(OsSettlementService::class);
        $rows = collect();

        foreach ($osIds as $partyUserId) {
            $partyUserId = (int) $partyUserId;
            $invRows = $grouped->get($partyUserId, collect());

            $entry = $saved->get($partyUserId);
            if ($paymentMethod && ! $entry) {
                // Create synthetic row from settlement batch / cash payout if transfer row is missing.
                $batch = $methodBatchesByOs->get($partyUserId)
                    ?? $this->batchesInRange($fromDay, $toDay, $partyUserId, $paymentMethod, $branchId)->first();
                if (! $batch && $paymentMethod === 'cash') {
                    $orphanPayout = OsCashPayout::query()
                        ->with('deliveryMan')
                        ->where('os_user_id', $partyUserId)
                        ->whereDate('period_from', '<=', $toDay)
                        ->whereDate('period_to', '>=', $fromDay)
                        ->orderByDesc('id')
                        ->first();
                    if ($orphanPayout) {
                        $amountDue = round(abs((float) $orphanPayout->amount), 2);
                        $user = $users->get($partyUserId);
                        $name = $user?->name ?: ('OS #'.$partyUserId);
                        $rows->push((object) array_merge([
                            'os_user_id' => $partyUserId,
                            'name' => $name,
                            'os_phone' => normalizeContactNumber((string) ($user?->contact_number ?? '')),
                            'invoice_count' => 0,
                            'item_count' => 0,
                            'amount_due' => $amountDue,
                            'cash_amount' => $amountDue,
                            'kpay_amount' => 0,
                            'freight_amount' => 0,
                            'freight_computed' => 0,
                            'freight_is_override' => false,
                            'paid_total' => $amountDue,
                            'remaining' => 0,
                            'status' => 'matched',
                            'remitted_count' => 0,
                            'all_remitted' => false,
                            'kpay_name' => '',
                            'kpay_no' => '',
                            'kpay_slip_url' => $orphanPayout->slipPhotoUrl(),
                            'remark' => '',
                            'has_saved' => false,
                            'transfer_id' => $orphanPayout->money_transfer_id,
                            'payment_method' => 'cash',
                            'settlement_batch_id' => $orphanPayout->settlement_batch_id,
                        ], $this->cashPayoutMeta($orphanPayout)));
                    }
                    continue;
                }
                if (! $batch) {
                    continue;
                }
                $amountDue = round(abs((float) $batch->amount), 2);
                $cash = $paymentMethod === 'cash' ? $amountDue : 0.0;
                $kpay = $paymentMethod === 'kpay' ? $amountDue : 0.0;
                $user = $users->get($partyUserId);
                $name = $user?->name ?: ('OS #'.$partyUserId);
                $payout = $paymentMethod === 'cash'
                    ? OsCashPayout::query()->with('deliveryMan')->where('settlement_batch_id', $batch->id)->orderByDesc('id')->first()
                    : null;
                $rows->push((object) array_merge([
                    'os_user_id' => $partyUserId,
                    'name' => $name,
                    'os_phone' => normalizeContactNumber((string) ($user?->contact_number ?? '')),
                    'invoice_count' => 0,
                    'item_count' => is_array($batch->item_ids) ? count($batch->item_ids) : 0,
                    'amount_due' => $amountDue,
                    'cash_amount' => $cash,
                    'kpay_amount' => $kpay,
                    'freight_amount' => 0,
                    'freight_computed' => 0,
                    'freight_is_override' => false,
                    'paid_total' => $amountDue,
                    'remaining' => 0,
                    'status' => 'matched',
                    'remitted_count' => 0,
                    'all_remitted' => false,
                    'kpay_name' => $user ? $settlement->kpayNameFromUser($user) : '',
                    'kpay_no' => $user ? $settlement->kpayNoFromUser($user) : '',
                    'kpay_slip_url' => $this->kpaySlipUrl($batch),
                    'remark' => '',
                    'has_saved' => false,
                    'transfer_id' => null,
                    'payment_method' => $paymentMethod,
                    'settlement_batch_id' => $batch->id,
                ], $this->cashPayoutMeta($payout)));
                continue;
            }

            if ($invRows->isEmpty() && $entry) {
                $amountDue = round((float) $entry->cash_amount + (float) $entry->kpay_amount, 2);
            } else {
            $amountDue = round(-1 * (float) $invRows->sum('amount'), 2);
            }

            $invoiceCount = $invRows->count();
            $itemCount = (int) $invRows->sum('item_count');
            $remittedCount = $invRows->filter(fn ($r) => trim((string) ($r->remitted_date ?? '')) !== '')->count();

            $invoiceIds = $invRows->pluck('id')->map(fn ($id) => (int) $id)->all();
            $gateTotal = $this->sumGateForInvoices($invoiceIds);

            $cash = $entry ? (float) $entry->cash_amount : 0.0;
            $kpay = $entry ? (float) $entry->kpay_amount : 0.0;
            $method = $entry?->payment_method
                ?? ($cash > 0 && $kpay <= 0 ? 'cash' : 'kpay');

            if ($paymentMethod && $method !== $paymentMethod) {
                continue;
            }

            $freight = $entry && $entry->freight_amount !== null
                ? (float) $entry->freight_amount
                : $gateTotal;
            $freightIsOverride = $entry && $entry->freight_amount !== null;
            $remark = $entry?->remark ?? '';

            $paid = round($cash + $kpay, 2);
            $remaining = round($amountDue - $paid, 2);
            $status = $this->statusFor($amountDue, $paid, $remaining);

            $user = $users->get($partyUserId);
            $name = $partyUserId > 0
                ? ($user?->name ?: ($invRows->first()->name ?? 'OS #'.$partyUserId))
                : ($invRows->first()->name ?? __('message.no_os'));

            $kpayName = $user ? $settlement->kpayNameFromUser($user) : '';
            $kpayNo = $user ? $settlement->kpayNoFromUser($user) : '';

            $payout = null;
            if ($method === 'cash') {
                $payout = ($entry?->cashPayout)
                    ?? ($entry ? $payoutsByTransfer->get((int) $entry->id) : null)
                    ?? ($entry?->settlement_batch_id ? $payoutsByBatch->get((int) $entry->settlement_batch_id) : null)
                    ?? $payoutsByOs->get($partyUserId);
            }

            $slipBatch = null;
            if ($method === 'kpay') {
                $batchId = (int) ($entry?->settlement_batch_id ?? 0);
                $slipBatch = $batchId > 0
                    ? ($kpayBatchesById->get($batchId) ?? OsSettlementBatch::query()->find($batchId))
                    : $kpayBatchesByOs->get($partyUserId);
            }

            $rows->push((object) array_merge([
                'os_user_id' => $partyUserId,
                'name' => $name,
                'os_phone' => normalizeContactNumber((string) ($user?->contact_number ?? '')),
                'invoice_count' => $invoiceCount,
                'item_count' => $itemCount,
                'amount_due' => $amountDue,
                'cash_amount' => $cash,
                'kpay_amount' => $kpay,
                'freight_amount' => $freight,
                'freight_computed' => $gateTotal,
                'freight_is_override' => $freightIsOverride,
                'paid_total' => $paid,
                'remaining' => $remaining,
                'status' => $status,
                'remitted_count' => $remittedCount,
                'all_remitted' => $invoiceCount > 0 && $remittedCount === $invoiceCount,
                'kpay_name' => $kpayName,
                'kpay_no' => $kpayNo,
                'kpay_slip_url' => $this->kpaySlipUrl($slipBatch),
                'remark' => $remark,
                'has_saved' => (bool) $entry,
                'transfer_id' => $entry?->id,
                'payment_method' => $method,
                'settlement_batch_id' => $entry?->settlement_batch_id ?? $slipBatch?->id,
            ], $this->cashPayoutMeta($payout)));
        }

        $rows = $rows
            ->sortBy(fn ($row) => mb_strtolower((string) $row->name))
            ->values();

        $totals = $this->finishTotals($fromDay, $toDay, $osId, $branchId);
        $summary = (object) [
            'os_count' => $rows->count(),
            'amount_due' => $totals['total'],
            'cash_total' => $totals['cash'],
            'kpay_total' => $totals['kpay'],
            'freight_total' => round((float) $rows->sum('freight_amount'), 2),
        ];

        return ['rows' => $rows, 'summary' => $summary];
    }

    /**
     * Combined Money Transfer Amount: all OS Finish totals (Kpay + Cash), not split.
     *
     * @return array{kpay: float, cash: float, total: float}
     */
    public function finishTotals(string $fromDay, string $toDay, ?int $osId = null, ?int $branchId = null): array
    {
        $batches = $this->batchesInRange($fromDay, $toDay, $osId, null, $branchId);

        $kpay = 0.0;
        $cash = 0.0;
        foreach ($batches as $batch) {
            $amount = abs((float) $batch->amount);
            if ((string) $batch->payment_method === 'cash') {
                $cash += $amount;
            } else {
                $kpay += $amount;
            }
        }

        return [
            'kpay' => round($kpay, 2),
            'cash' => round($cash, 2),
            'total' => round($kpay + $cash, 2),
        ];
    }

    /**
     * Cash tab: one row per ငွေရှင်းတမ်း Cash Finish (payout / batch), not OS totals.
     *
     * @return array{rows: Collection<int, object>, summary: object}
     */
    protected function listCashFinishRows(string $fromDay, string $toDay, ?int $osId = null, ?int $branchId = null): array
    {
        $payouts = OsCashPayout::query()
            ->with(['osUser.city', 'deliveryMan', 'settlementBatch', 'moneyTransfer'])
            ->where(function ($q) use ($fromDay, $toDay) {
                $this->applyPayoutPeriod($q, $fromDay, $toDay);
            })
            ->when($osId !== null, fn ($q) => $q->where('os_user_id', $osId))
            ->orderByDesc('id')
            ->get()
            ->filter(fn (OsCashPayout $p) => $this->payoutMatchesBranch($p, $branchId))
            ->values();

        $coveredBatchIds = $payouts
            ->pluck('settlement_batch_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $orphanBatches = $this->batchesInRange($fromDay, $toDay, $osId, 'cash', $branchId)
            ->when($coveredBatchIds !== [], fn (Collection $c) => $c->reject(fn ($b) => in_array((int) $b->id, $coveredBatchIds, true))->values());

        $rows = collect();

        foreach ($payouts as $payout) {
            $user = $payout->osUser;
            $amount = round(abs((float) $payout->amount), 2);
            $batch = $payout->settlementBatch;
            $kpay = $this->kpayDetailsFor($user, $batch);
            $rows->push((object) array_merge([
                'os_user_id' => (int) $payout->os_user_id,
                'name' => $user?->name ?: ('OS #'.$payout->os_user_id),
                'os_phone' => normalizeContactNumber((string) ($user?->contact_number ?? '')),
                'invoice_count' => 0,
                'item_count' => is_array($batch?->item_ids) ? count($batch->item_ids) : 0,
                'amount_due' => $amount,
                'cash_amount' => $amount,
                'kpay_amount' => 0,
                'freight_amount' => 0,
                'freight_computed' => 0,
                'freight_is_override' => false,
                'paid_total' => $amount,
                'remaining' => 0,
                'status' => 'matched',
                'remitted_count' => 0,
                'all_remitted' => false,
                'kpay_name' => $kpay['name'],
                'kpay_no' => $kpay['no'],
                'kpay_slip_url' => $payout->slipPhotoUrl() ?: $this->kpaySlipUrl($batch),
                'remark' => '',
                'has_saved' => (bool) $payout->money_transfer_id,
                'transfer_id' => $payout->money_transfer_id,
                'payment_method' => 'cash',
                'settlement_batch_id' => $payout->settlement_batch_id,
            ], $this->cashPayoutMeta($payout)));
        }

        foreach ($orphanBatches as $batch) {
            $user = $batch->osUser;
            $amount = round(abs((float) $batch->amount), 2);
            $kpay = $this->kpayDetailsFor($user, $batch);
            $rows->push((object) array_merge([
                'os_user_id' => (int) $batch->os_user_id,
                'name' => $user?->name ?: ('OS #'.$batch->os_user_id),
                'os_phone' => normalizeContactNumber((string) ($user?->contact_number ?? '')),
                'invoice_count' => 0,
                'item_count' => is_array($batch->item_ids) ? count($batch->item_ids) : 0,
                'amount_due' => $amount,
                'cash_amount' => $amount,
                'kpay_amount' => 0,
                'freight_amount' => 0,
                'freight_computed' => 0,
                'freight_is_override' => false,
                'paid_total' => $amount,
                'remaining' => 0,
                'status' => 'matched',
                'remitted_count' => 0,
                'all_remitted' => false,
                'kpay_name' => $kpay['name'],
                'kpay_no' => $kpay['no'],
                'kpay_slip_url' => $this->kpaySlipUrl($batch),
                'remark' => '',
                'has_saved' => false,
                'transfer_id' => null,
                'payment_method' => 'cash',
                'settlement_batch_id' => $batch->id,
            ], $this->cashPayoutMeta(null)));
        }

        $rows = $rows
            ->sortByDesc(fn ($row) => (int) ($row->cash_payout_id ?? $row->settlement_batch_id ?? 0))
            ->values();

        $totals = $this->finishTotals($fromDay, $toDay, $osId, $branchId);
        $summary = (object) [
            'os_count' => $rows->count(),
            'amount_due' => $totals['total'],
            'cash_total' => $totals['cash'],
            'kpay_total' => $totals['kpay'],
            'freight_total' => 0.0,
        ];

        return ['rows' => $rows, 'summary' => $summary];
    }

    /**
     * @return array{name: string, no: string}
     */
    protected function kpayDetailsFor(?User $user, ?OsSettlementBatch $batch = null): array
    {
        $settlement = app(OsSettlementService::class);
        $name = $user ? $settlement->kpayNameFromUser($user) : '';
        $no = $user ? $settlement->kpayNoFromUser($user) : '';

        if ($name === '' && $batch) {
            $name = trim((string) ($batch->kpay_name ?? ''));
        }
        if ($no === '' && $batch) {
            $no = trim((string) ($batch->kpay_no ?? ''));
        }

        return ['name' => $name, 'no' => $no];
    }

    /**
     * Auto-create Money Transfer row (+ Cash payout job) after OS Finish.
     */
    public function recordFromSettlementBatch(OsSettlementBatch $batch): OsMoneyTransfer
    {
        $method = in_array((string) $batch->payment_method, ['kpay', 'cash'], true)
            ? (string) $batch->payment_method
            : 'kpay';
        $due = round(abs((float) $batch->amount), 2);
        $fromDay = optional($batch->from_date)->toDateString() ?? now('Asia/Yangon')->toDateString();
        $toDay = optional($batch->to_date)->toDateString() ?? $fromDay;

        $transfer = OsMoneyTransfer::query()->updateOrCreate(
            [
                'settlement_batch_id' => $batch->id,
            ],
            [
                'period_from' => $fromDay,
                'period_to' => $toDay,
                'branch_id' => $this->branchIdForBatch($batch) ?? 0,
                'os_user_id' => (int) $batch->os_user_id,
                'payment_method' => $method,
                'cash_amount' => $method === 'cash' ? $due : 0,
                'kpay_amount' => $method === 'kpay' ? $due : 0,
                'freight_amount' => null,
                'updated_by' => auth()->id(),
            ]
        );

        if ($method === 'cash') {
            $payout = OsCashPayout::query()->firstOrNew([
                'settlement_batch_id' => $batch->id,
            ]);
            $payout->os_user_id = (int) $batch->os_user_id;
            $payout->money_transfer_id = $transfer->id;
            $payout->period_from = $fromDay;
            $payout->period_to = $toDay;
            $payout->amount = $due;
            if (! $payout->slip_photo_path) {
                $payout->slip_photo_path = $batch->kpay_slip_path ?: null;
            }
            if (! $payout->exists) {
                $payout->status = OsCashPayout::STATUS_UNASSIGNED;
                $payout->created_by = auth()->id();
            }
            $payout->save();
        } else {
            // Switching / finishing as Kpay — remove unassigned cash jobs for this batch.
            OsCashPayout::query()
                ->where('settlement_batch_id', $batch->id)
                ->where('status', OsCashPayout::STATUS_UNASSIGNED)
                ->delete();
        }

        return $transfer->fresh();
    }

    /**
     * Switch an existing Cash transfer to Kpay.
     * Requires a KBZ Pay slip image. Blocked once a rider is assigned (or payout is pending/done).
     */
    public function switchPaymentMethod(
        OsMoneyTransfer $transfer,
        string $method,
        ?\Illuminate\Http\UploadedFile $kpaySlip = null
    ): OsMoneyTransfer {
        if (($transfer->payment_method ?? '') !== 'cash') {
            throw new \RuntimeException(__('message.cash_payout_invalid_transition'));
        }

        if ($method !== 'kpay') {
            throw new \RuntimeException(__('message.cash_payout_invalid_transition'));
        }

        if (! $kpaySlip) {
            throw new \RuntimeException(__('message.money_transfer_kpay_slip_required'));
        }

        $payout = OsCashPayout::query()
            ->where('money_transfer_id', $transfer->id)
            ->orderByDesc('id')
            ->first();
        if (! $payout && $transfer->settlement_batch_id) {
            $payout = OsCashPayout::query()
                ->where('settlement_batch_id', $transfer->settlement_batch_id)
                ->orderByDesc('id')
                ->first();
        }

        if ($payout && $payout->status !== OsCashPayout::STATUS_UNASSIGNED) {
            throw new \RuntimeException(__('message.money_transfer_switch_blocked_assigned'));
        }

        $due = round(max((float) $transfer->cash_amount + (float) $transfer->kpay_amount, 0), 2);
        if ($due <= 0) {
            if ($transfer->settlement_batch_id) {
                $batch = OsSettlementBatch::query()->find($transfer->settlement_batch_id);
                $due = $batch ? round(abs((float) $batch->amount), 2) : 0;
            }
        }

        $batch = $transfer->settlement_batch_id
            ? OsSettlementBatch::query()->find($transfer->settlement_batch_id)
            : null;

        $slipPath = $this->storeSwitchKpaySlip($transfer, $batch, $kpaySlip);

        $transfer->update([
            'payment_method' => 'kpay',
            'cash_amount' => 0,
            'kpay_amount' => $due,
            'updated_by' => auth()->id(),
        ]);

        if ($batch) {
            if ($batch->kpay_slip_path && $batch->kpay_slip_path !== $slipPath) {
                Storage::disk('public')->delete($batch->kpay_slip_path);
            }
            $batch->update([
                'payment_method' => 'kpay',
                'kpay_slip_path' => $slipPath,
            ]);
        } else {
            $fromDay = optional($transfer->period_from)->toDateString()
                ?? now('Asia/Yangon')->toDateString();
            $toDay = optional($transfer->period_to)->toDateString() ?? $fromDay;
            $batch = OsSettlementBatch::query()->create([
                'os_user_id' => (int) $transfer->os_user_id,
                'from_date' => $fromDay,
                'to_date' => $toDay,
                'amount' => $due,
                'payment_method' => 'kpay',
                'delivery_format' => 'table',
                'kpay_slip_path' => $slipPath,
                'item_ids' => [],
                'finished_by' => auth()->id(),
                'finished_at' => now(),
            ]);
            $transfer->update(['settlement_batch_id' => $batch->id]);
        }

        OsCashPayout::query()
            ->where('money_transfer_id', $transfer->id)
            ->where('status', OsCashPayout::STATUS_UNASSIGNED)
            ->delete();

        if ($transfer->settlement_batch_id) {
            OsCashPayout::query()
                ->where('settlement_batch_id', $transfer->settlement_batch_id)
                ->where('status', OsCashPayout::STATUS_UNASSIGNED)
                ->delete();
        }

        return $transfer->fresh();
    }

    protected function storeSwitchKpaySlip(
        OsMoneyTransfer $transfer,
        ?OsSettlementBatch $batch,
        \Illuminate\Http\UploadedFile $file
    ): string {
        $dir = $batch
            ? 'os-settlements/batch-'.$batch->id
            : 'os-settlements/money-transfer/'.$transfer->id;

        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            $ext = 'jpg';
        }

        return $file->storeAs($dir, 'kpay-slip-'.\Illuminate\Support\Str::random(8).'.'.$ext, 'public');
    }

    /**
     * Public URL for KBZ Pay slip uploaded at OS Finish.
     */
    protected function kpaySlipUrl(?OsSettlementBatch $batch): ?string
    {
        $path = $batch?->kpay_slip_path;
        if (! $path) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    /**
     * @return array<string, mixed>
     */
    protected function cashPayoutMeta(?OsCashPayout $payout): array
    {
        if (! $payout) {
            return [
                'cash_payout_id' => null,
                'cash_payout_status' => OsCashPayout::STATUS_UNASSIGNED,
                'delivery_man_id' => null,
                'delivery_man_name' => null,
                'delivery_man_phone' => null,
                'pending_note' => null,
                'done_note' => null,
                'pending_photo_url' => null,
                'done_photo_url' => null,
                'can_switch_to_kpay' => true,
            ];
        }

        $status = (string) ($payout->status ?: OsCashPayout::STATUS_UNASSIGNED);

        return [
            'cash_payout_id' => $payout->id,
            'cash_payout_status' => $status,
            'delivery_man_id' => $payout->delivery_man_id,
            'delivery_man_name' => $payout->deliveryMan?->name,
            'delivery_man_phone' => $payout->deliveryMan?->contact_number,
            'pending_note' => $payout->pending_note,
            'done_note' => $payout->done_note,
            'pending_photo_url' => $payout->pendingPhotoUrl(),
            'done_photo_url' => $payout->donePhotoUrl(),
            'can_switch_to_kpay' => $status === OsCashPayout::STATUS_UNASSIGNED,
        ];
    }

    /**
     * @param  array{cash_amount?: mixed, kpay_amount?: mixed, freight_amount?: mixed, remark?: mixed, payment_method?: mixed}  $data
     */
    public function upsertRow(
        string $fromDay,
        string $toDay,
        ?int $branchId,
        int $osUserId,
        array $data
    ): OsMoneyTransfer {
        $branchKey = $branchId ?? 0;
        $cash = max(0, (float) ($data['cash_amount'] ?? 0));
        $kpay = max(0, (float) ($data['kpay_amount'] ?? 0));
        $freightRaw = $data['freight_amount'] ?? null;
        $freight = $freightRaw === null || $freightRaw === ''
            ? null
            : max(0, (float) $freightRaw);
        $remark = trim((string) ($data['remark'] ?? ''));
        $remark = $remark !== '' ? mb_substr($remark, 0, 255) : null;
        $method = $data['payment_method'] ?? null;
        if (! in_array($method, ['kpay', 'cash'], true)) {
            $method = $cash > 0 && $kpay <= 0 ? 'cash' : 'kpay';
        }
        // Keep amounts consistent with chosen tab method.
        if ($method === 'cash') {
            $kpay = 0;
        } else {
            $cash = 0;
        }

        $row = OsMoneyTransfer::query()->updateOrCreate(
            [
                'period_from' => $fromDay,
                'period_to' => $toDay,
                'branch_id' => $branchKey,
                'os_user_id' => $osUserId,
            ],
            [
                'payment_method' => $method,
                'cash_amount' => $cash,
                'kpay_amount' => $kpay,
                'freight_amount' => $freight,
                'remark' => $remark,
                'updated_by' => auth()->id(),
            ]
        );

        return $row->fresh();
    }

    /**
     * Prefill KPay with amount due for rows that have no saved entry yet.
     *
     * @return int Number of rows created/updated
     */
    public function fillKpayFromDue(string $fromDay, string $toDay, ?int $branchId): int
    {
        $sheet = $this->listSheet($fromDay, $toDay, $branchId, null, 'kpay');
        $count = 0;
        foreach ($sheet['rows'] as $row) {
            if ($row->has_saved) {
                continue;
            }
            if ((float) $row->amount_due <= 0) {
                continue;
            }
            $this->upsertRow($fromDay, $toDay, $branchId, (int) $row->os_user_id, [
                'cash_amount' => 0,
                'kpay_amount' => $row->amount_due,
                'freight_amount' => null,
                'remark' => '',
                'payment_method' => 'kpay',
            ]);
            $count++;
        }

        return $count;
    }

    protected function statusFor(float $amountDue, float $paid, float $remaining): string
    {
        if ($paid <= 0.001) {
            return 'pending';
        }
        if (abs($remaining) < 0.001) {
            return 'matched';
        }
        if ($remaining < 0) {
            return 'over';
        }

        return 'partial';
    }

    /**
     * Finished OS counts per destination branch for the selected period.
     *
     * @return array<int, int>
     */
    public function branchFinishedCounts(string $fromDay, string $toDay): array
    {
        $this->backfillTransferBranches();
        $counts = [];
        foreach ($this->batchesInRange($fromDay, $toDay, null, null, null) as $batch) {
            $branchId = $this->branchIdForBatch($batch);
            if (! $branchId) {
                continue;
            }
            $counts[$branchId] = ($counts[$branchId] ?? 0) + 1;
        }

        return $counts;
    }

    public function backfillTransferBranches(): void
    {
        $transfers = OsMoneyTransfer::query()
            ->with('settlementBatch')
            ->where(function ($q) {
                $q->whereNull('branch_id')->orWhere('branch_id', 0);
            })
            ->whereNotNull('settlement_batch_id')
            ->get();

        foreach ($transfers as $transfer) {
            $branchId = $transfer->settlementBatch
                ? $this->branchIdForBatch($transfer->settlementBatch)
                : null;
            if ($branchId) {
                $transfer->forceFill(['branch_id' => $branchId])->save();
            }
        }
    }

    /**
     * @return Collection<int, OsSettlementBatch>
     */
    protected function batchesInRange(
        string $fromDay,
        string $toDay,
        ?int $osId,
        ?string $paymentMethod,
        ?int $branchId
    ): Collection {
        $batches = OsSettlementBatch::query()
            ->with('osUser')
            ->where(function ($q) use ($fromDay, $toDay) {
                $this->applyBatchPeriod($q, $fromDay, $toDay);
            })
            ->when($osId !== null, fn ($q) => $q->where('os_user_id', $osId))
            ->when($paymentMethod, fn ($q) => $q->where('payment_method', $paymentMethod))
            ->orderByDesc('id')
            ->get();

        if ($branchId && $branchId > 0) {
            $batches = $batches
                ->filter(fn (OsSettlementBatch $batch) => $this->branchIdForBatch($batch) === $branchId)
                ->values();
        }

        return $batches;
    }

    protected function applyBatchPeriod($query, string $fromDay, string $toDay): void
    {
        $query->where(function ($q) use ($fromDay, $toDay) {
            $q->where(function ($d) use ($fromDay, $toDay) {
                $d->whereDate('from_date', '<=', $toDay)
                    ->whereDate('to_date', '>=', $fromDay);
            })->orWhere(function ($d) use ($fromDay, $toDay) {
                $d->whereDate('finished_at', '>=', $fromDay)
                    ->whereDate('finished_at', '<=', $toDay);
            });
        });
    }

    protected function applyTransferPeriod($query, string $fromDay, string $toDay): void
    {
        $query->where(function ($q) use ($fromDay, $toDay) {
            $q->where(function ($d) use ($fromDay, $toDay) {
                $d->whereDate('period_from', '<=', $toDay)
                    ->whereDate('period_to', '>=', $fromDay);
            })->orWhereHas('settlementBatch', function ($b) use ($fromDay, $toDay) {
                $b->whereDate('finished_at', '>=', $fromDay)
                    ->whereDate('finished_at', '<=', $toDay);
            });
        });
    }

    protected function applyPayoutPeriod($query, string $fromDay, string $toDay): void
    {
        $query->where(function ($q) use ($fromDay, $toDay) {
            $q->where(function ($d) use ($fromDay, $toDay) {
                $d->whereDate('period_from', '<=', $toDay)
                    ->whereDate('period_to', '>=', $fromDay);
            })->orWhere(function ($d) use ($fromDay, $toDay) {
                $d->whereDate('created_at', '>=', $fromDay)
                    ->whereDate('created_at', '<=', $toDay);
            });
        });
    }

    protected function branchIdForBatch(OsSettlementBatch $batch): ?int
    {
        return $this->branchIdForItemIds((array) ($batch->item_ids ?? []));
    }

    /**
     * Destination branch for a settlement (to_branch, else from_branch).
     *
     * @param  list<int|string>  $itemIds
     */
    protected function branchIdForItemIds(array $itemIds): ?int
    {
        $ids = collect($itemIds)->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0)->unique()->values();
        if ($ids->isEmpty()) {
            return null;
        }

        $branch = DispatchOrderItem::query()
            ->whereIn('id', $ids->all())
            ->get(['to_branch_id', 'from_branch_id'])
            ->map(fn (DispatchOrderItem $item) => (int) ($item->to_branch_id ?: $item->from_branch_id ?: 0))
            ->filter()
            ->countBy()
            ->sortDesc()
            ->keys()
            ->first();

        return $branch ? (int) $branch : null;
    }

    protected function payoutMatchesBranch(OsCashPayout $payout, ?int $branchId): bool
    {
        if (! $branchId || $branchId <= 0) {
            return true;
        }

        if ($payout->settlementBatch) {
            return $this->branchIdForBatch($payout->settlementBatch) === $branchId;
        }

        $transferBranch = (int) ($payout->moneyTransfer?->branch_id ?? 0);

        return $transferBranch === $branchId;
    }

    /**
     * @param  list<int>  $invoiceIds
     */
    protected function sumGateForInvoices(array $invoiceIds): float
    {
        if ($invoiceIds === []) {
            return 0.0;
        }

        $itemIds = DailyCheckInvoice::query()
            ->whereIn('id', $invoiceIds)
            ->get(['item_ids'])
            ->flatMap(fn (DailyCheckInvoice $inv) => collect($inv->item_ids ?? [])->map(fn ($id) => (int) $id))
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($itemIds === []) {
            return 0.0;
        }

        return round((float) DispatchOrderItem::query()->whereIn('id', $itemIds)->sum('gate_amount'), 2);
    }
}
