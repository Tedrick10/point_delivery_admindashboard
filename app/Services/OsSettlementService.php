<?php

namespace App\Services;

use App\Models\AdminKpayAccount;
use App\Models\DispatchOrderItem;
use App\Models\OsSettlementBatch;
use App\Models\OsSettlementDraft;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OsSettlementService
{
    public const NOTIFICATION_TYPE = 'os_settlement';

    public function __construct(
        protected AppPushService $pushService
    ) {}

    /**
     * Settlement Online Shop id for a parcel (same rules as Daily Check).
     * Photo / Viber orders often keep the uploader as order.client_id while
     * pickup_point.name is the real shop — never group slips by client_id alone.
     */
    public function itemOsPartyUserId($item): int
    {
        return resolveDispatchOsPartyUserId($item->order ?? null);
    }

    /**
     * Keep only parcels that belong to the given Online Shop party.
     *
     * @param  \Illuminate\Support\Collection<int, DispatchOrderItem>|iterable  $items
     * @return \Illuminate\Support\Collection<int, DispatchOrderItem>
     */
    public function filterItemsForOsParty($items, int $osId)
    {
        return collect($items)
            ->filter(fn ($item) => $this->itemOsPartyUserId($item) === $osId)
            ->values();
    }

    /**
     * Unfinished Completed items for ငွေရှင်းတမ်း within a date range.
     * Delivered-only (no admin_completed_at) never appears — Completed first.
     * List day: first Completed on Yangon day C → C−1; later Completeds that rider on C → C.
     * Pass null for $osId to include all Online Shops.
     *
     * OS scoping is applied in completedItemsForPeriod via resolveDispatchOsPartyUserId
     * (not order.client_id), so $osId is ignored here.
     */
    public function completedItemsQuery(?int $osId, string $fromDay, string $toDay, ?int $branchId = null)
    {
        $query = DispatchOrderItem::query()
            ->with(['order.client.city', 'order.city', 'fromBranch', 'toBranch', 'kyoShinItem']);

        $this->applyUnfinishedSettlementConstraints($query, $fromDay, $toDay);

        return $query
            ->when($branchId !== null && $branchId > 0, function ($query) use ($branchId) {
                applyDestinationBranchFilter($query, $branchId);
            })
            ->when(true, function ($query) {
                applyForcedItemOwnership($query);
            })
            ->orderByDesc('id');
    }

    /**
     * Unfinished ငွေရှင်းတမ်း rows: Completed items only.
     * ကြိုရှင်း Return parcels stay on ကြိုရှင်း ပို့မရသော ပါဆယ် — not Os ဆီမှ ရရန်.
     */
    public function applyUnfinishedSettlementConstraints($query, string $fromDay, string $toDay)
    {
        $fromDay = Carbon::parse($fromDay)->toDateString();
        $toDay = Carbon::parse($toDay)->toDateString();
        $boundsStart = dailyCheckListDayBounds($fromDay)['start'];
        $boundsEnd = dailyCheckListDayBounds($toDay)['end'];

        $query->whereNull('admin_finished_at')
            ->where('status', 'completed')
            ->whereNotNull('admin_completed_at')
            ->where(function ($q) use ($fromDay, $toDay, $boundsStart, $boundsEnd) {
                $q->where(function ($byCompleted) use ($boundsStart, $boundsEnd) {
                    $byCompleted->where('admin_completed_at', '>=', $boundsStart)
                        ->where('admin_completed_at', '<', $boundsEnd);
                })->orWhere(function ($byRemit) use ($fromDay, $toDay) {
                    $byRemit->whereNotNull('rider_remit_date')
                        ->whereDate('rider_remit_date', '>=', $fromDay)
                        ->whereDate('rider_remit_date', '<=', $toDay);
                });
            });
    }

    /**
     * Unfinished Completed items whose ငွေရှင်းတမ်း list day falls in [from, to].
     * When $osId is set, parcels are scoped by Online Shop party (pickup shop → client),
     * not raw order.client_id — matches Daily Check / Money Transfer.
     */
    public function completedItemsForPeriod(?int $osId, string $fromDay, string $toDay, ?int $branchId = null)
    {
        $items = $this->completedItemsQuery(null, $fromDay, $toDay, $branchId)
            ->get()
            ->filter(fn ($item) => osSettlementItemInPeriod($item, $fromDay, $toDay))
            ->values();

        if ($osId === null) {
            return $items;
        }

        return $this->filterItemsForOsParty($items, (int) $osId);
    }

    /**
     * Unfinished ကြိုရှင်း parcels for ကြိုရှင်းသမား ပေးရန် (Finish only, no proof).
     * Completed ကြိုရှင်း only (fee Return Delivery that completed like a normal parcel).
     */
    public function finishedKyoShinItemsForPeriod(string $fromDay, string $toDay, ?int $branchId = null)
    {
        $fromDay = Carbon::parse($fromDay)->toDateString();
        $toDay = Carbon::parse($toDay)->toDateString();
        $boundsStart = dailyCheckListDayBounds($fromDay)['start'];
        $boundsEnd = dailyCheckListDayBounds($toDay)['end'];

        $query = DispatchOrderItem::query()
            ->with(['order.client.city', 'order.city', 'fromBranch', 'toBranch', 'kyoShinItem.batch'])
            ->whereNull('admin_finished_at')
            ->where('status', 'completed')
            ->whereHas('kyoShinItem')
            ->whereNotNull('admin_completed_at')
            ->where(function ($q) use ($fromDay, $toDay, $boundsStart, $boundsEnd) {
                $q->where(function ($byCompleted) use ($boundsStart, $boundsEnd) {
                    $byCompleted->where('admin_completed_at', '>=', $boundsStart)
                        ->where('admin_completed_at', '<', $boundsEnd);
                })->orWhere(function ($byRemit) use ($fromDay, $toDay) {
                    $byRemit->whereNotNull('rider_remit_date')
                        ->whereDate('rider_remit_date', '>=', $fromDay)
                        ->whereDate('rider_remit_date', '<=', $toDay);
                });
            });

        applyForcedItemOwnership($query);
        if ($branchId !== null && $branchId > 0) {
            applyDestinationBranchFilter($query, $branchId);
        }

        return $query
            ->orderByDesc('id')
            ->get()
            ->filter(fn ($item) => osSettlementItemInPeriod($item, $fromDay, $toDay))
            ->values();
    }

    /**
     * Unfinished + finished ကြိုရှင်း parcels for OS drill-down / slip preview.
     */
    public function kyoShinDetailItemsForPeriod(int $osId, string $fromDay, string $toDay, ?int $branchId = null)
    {
        $fromDay = Carbon::parse($fromDay)->toDateString();
        $toDay = Carbon::parse($toDay)->toDateString();
        $boundsStart = dailyCheckListDayBounds($fromDay)['start'];
        $boundsEnd = dailyCheckListDayBounds($toDay)['end'];

        $query = DispatchOrderItem::query()
            ->with(['order.client.city', 'order.city', 'fromBranch', 'toBranch', 'kyoShinItem.batch'])
            ->where('status', 'completed')
            ->whereHas('kyoShinItem')
            ->whereNotNull('admin_completed_at')
            ->where(function ($q) use ($fromDay, $toDay, $boundsStart, $boundsEnd) {
                $q->where(function ($byCompleted) use ($boundsStart, $boundsEnd) {
                    $byCompleted->where('admin_completed_at', '>=', $boundsStart)
                        ->where('admin_completed_at', '<', $boundsEnd);
                })->orWhere(function ($byRemit) use ($fromDay, $toDay) {
                    $byRemit->whereNotNull('rider_remit_date')
                        ->whereDate('rider_remit_date', '>=', $fromDay)
                        ->whereDate('rider_remit_date', '<=', $toDay);
                });
            });

        applyForcedItemOwnership($query);
        if ($branchId !== null && $branchId > 0) {
            applyDestinationBranchFilter($query, $branchId);
        }

        return $this->filterItemsForOsParty(
            $query
                ->orderByDesc('id')
                ->get()
                ->filter(fn ($item) => osSettlementItemInPeriod($item, $fromDay, $toDay))
                ->values(),
            $osId
        );
    }

    /**
     * Settlement / Money Transfer period from each item's list day
     * (10:00 AM Yangon cutoff / stamped rider_remit_date).
     *
     * @param  \Illuminate\Support\Collection<int, DispatchOrderItem>  $items
     * @return array{0: string, 1: string}
     */
    protected function listDayRangeFromItems($items, string $fallbackFrom, string $fallbackTo): array
    {
        $days = $items
            ->map(function ($item) {
                if (empty($item->admin_completed_at)
                    && ! ((string) ($item->status ?? '') === 'return' && ! empty($item->admin_updated_at))
                ) {
                    return null;
                }

                return osSettlementListDateForItem($item)->toDateString();
            })
            ->filter()
            ->unique()
            ->sort()
            ->values();

        if ($days->isEmpty()) {
            return [
                Carbon::parse($fallbackFrom)->toDateString(),
                Carbon::parse($fallbackTo)->toDateString(),
            ];
        }

        return [$days->first(), $days->last()];
    }

    public function buildSlipRows($items, string $invoiceDate, bool $excludeKyoShinAmounts = false, bool $kyoShinPayAmounts = false): array
    {
        $rows = [];
        $totals = [
            'os_paid' => 0.0,
            'item_value' => 0.0,
            'deli_amount' => 0.0,
            'os_to_pay' => 0.0,
            'gate_amount' => 0.0,
        ];

        foreach ($items as $slipItem) {
            $osPaid = (float) ($slipItem->os_paid ?? 0);
            $itemValue = (float) ($slipItem->item_value ?? 0);
            $deliAmount = (float) ($slipItem->deli_amount ?? 0);
            $excludeAmount = ! $kyoShinPayAmounts && $excludeKyoShinAmounts && $slipItem->excludeFromSettlementAmount();
            $osToPay = $kyoShinPayAmounts
                ? -1 * $slipItem->kyoShinPayAmount()
                : ($excludeAmount ? 0.0 : $slipItem->displayOsToPay());
            $osToPaySlip = formatDispatchOsToPaySlip($osToPay);
            $date = $slipItem->received_date
                ? Carbon::parse($slipItem->received_date)->format('d-m-Y')
                : $invoiceDate;
            $orderCreatedDate = $this->slipOrderCreatedDate($slipItem, $date);

            $gateAmount = (float) ($slipItem->gate_amount ?? 0);

            $rows[] = [
                'date' => $date,
                'order_created_date' => $orderCreatedDate,
                'customer' => $slipItem->customer_name ?: '-',
                'phone' => $slipItem->customer_phone ?: '-',
                'address' => $slipItem->customer_address ?: '-',
                'gate' => number_format($gateAmount),
                'gate_amount' => $gateAmount,
                'os_paid' => $osPaid,
                'item_value' => $itemValue,
                'deli_amount' => $deliAmount,
                'deli_amount_display' => formatDispatchDeliAmount($slipItem, $deliAmount),
                'os_to_pay' => $osToPaySlip['value'],
                'os_to_pay_display' => $osToPaySlip['formatted'],
                'os_to_pay_is_receive' => $osToPaySlip['is_receive'],
                'is_kyo_shin' => $slipItem->isKyoShinGiven() || (string) ($slipItem->status ?? '') === 'return',
                'exclude_from_settlement_amount' => $excludeAmount,
            ];

            if ($excludeAmount) {
                continue;
            }

            $totals['os_paid'] += $osPaid;
            $totals['item_value'] += $itemValue;
            $totals['deli_amount'] += $deliAmount;
            $totals['os_to_pay'] += $osToPaySlip['value'];
            $totals['gate_amount'] += $gateAmount;
        }

        return ['rows' => $rows, 'totals' => $totals];
    }

    /**
     * Order start / created day for Show Slip tables (Asia/Yangon).
     */
    protected function slipOrderCreatedDate($slipItem, string $fallback): string
    {
        $order = null;
        try {
            $order = $slipItem->order ?? null;
        } catch (\Throwable $e) {
            $order = null;
        }

        foreach (['created_at', 'date', 'pickup_datetime'] as $field) {
            $raw = $order->{$field} ?? null;
            if (empty($raw)) {
                continue;
            }
            try {
                return Carbon::parse($raw)->timezone('Asia/Yangon')->format('d-m-Y');
            } catch (\Throwable $e) {
                continue;
            }
        }

        return $fallback;
    }

    public function resolveSlipSender(User $osClient, $items, string $osName): array
    {
        // Slip Sender must match the settlement Online Shop row (User), not the
        // first parcel's pickup/client name — those can differ when pickup text
        // is a short alias ("Shwe Yee Min") while the OS account is "6 & 9".
        $phone = normalizeContactNumber((string) ($osClient->contact_number ?? ''));
        $address = formatOsClientAddress($osClient->id ? $osClient : null);
        $sender = [
            'name' => $osName !== '' && $osName !== '-'
                ? $osName
                : (trim((string) ($osClient->name ?? '')) ?: '-'),
            'phone' => $phone !== '' ? $phone : '-',
            'address' => $address !== '' ? $address : '-',
        ];

        // Fallback only when the OS user record is missing identity fields.
        if ($items->isNotEmpty()) {
            $firstOrder = $items->first()->order;
            if ($firstOrder) {
                if ($sender['name'] === '-' || $sender['name'] === '') {
                    $resolvedName = resolveDispatchOsName($firstOrder);
                    if ($resolvedName !== '-') {
                        $sender['name'] = $resolvedName;
                    }
                }
                if ($sender['phone'] === '-') {
                    $resolvedPhone = resolveDispatchOsPhone($firstOrder);
                    if ($resolvedPhone !== '-') {
                        $sender['phone'] = $resolvedPhone;
                    }
                }
                if ($sender['address'] === '-') {
                    $resolvedAddress = resolveDispatchOsAddress($firstOrder);
                    if ($resolvedAddress !== '-') {
                        $sender['address'] = $resolvedAddress;
                    }
                }
            }
        }

        return $sender;
    }

    public function kpayNameFromUser(User $user): string
    {
        $profile = is_array($user->os_profile) ? $user->os_profile : [];

        return trim((string) ($profile['kpay_name'] ?? ''));
    }

    public function kpayNoFromUser(User $user): string
    {
        $profile = is_array($user->os_profile) ? $user->os_profile : [];

        return trim((string) ($profile['kpay_no'] ?? ''));
    }

    /**
     * @param  mixed  $paths
     * @return list<string>
     */
    protected function normalizePathList($paths): array
    {
        if (! is_array($paths)) {
            $path = trim((string) $paths);

            return $path === '' ? [] : [$path];
        }

        return array_values(array_filter(array_map(
            static fn ($path) => trim((string) $path),
            $paths
        )));
    }

    /**
     * Flat path list (legacy string / JSON array, or all sides merged).
     *
     * @return list<string>
     */
    public function normalizeSlipPaths(?string $stored): array
    {
        $raw = trim((string) $stored);
        if ($raw === '') {
            return [];
        }

        if (str_starts_with($raw, '{')) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && (array_key_exists('pay', $decoded) || array_key_exists('receive', $decoded))) {
                return array_values(array_unique(array_merge(
                    $this->normalizePathList($decoded['pay'] ?? []),
                    $this->normalizePathList($decoded['receive'] ?? [])
                )));
            }
        }

        if (str_starts_with($raw, '[')) {
            $decoded = json_decode($raw, true);
            if (! is_array($decoded)) {
                return [];
            }

            return $this->normalizePathList($decoded);
        }

        return [$raw];
    }

    /**
     * Side-aware draft store: {"pay":[...],"receive":[...]}.
     * Legacy string/array is kept on pay only so receive never auto-inherits it.
     *
     * @return array{pay: list<string>, receive: list<string>, legacy: bool}
     */
    public function slipPathsBySide(?string $stored): array
    {
        $raw = trim((string) $stored);
        if ($raw === '') {
            return ['pay' => [], 'receive' => [], 'legacy' => false];
        }

        if (str_starts_with($raw, '{')) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && (array_key_exists('pay', $decoded) || array_key_exists('receive', $decoded))) {
                return [
                    'pay' => $this->normalizePathList($decoded['pay'] ?? []),
                    'receive' => $this->normalizePathList($decoded['receive'] ?? []),
                    'legacy' => false,
                ];
            }
        }

        $legacy = $this->normalizeSlipPaths($raw);

        return [
            'pay' => $legacy,
            'receive' => [],
            'legacy' => true,
        ];
    }

    /**
     * @return list<string>
     */
    public function slipPathsForSide(?string $stored, ?string $side): array
    {
        $side = $this->normalizeSettlementSide($side) === 'receive' ? 'receive' : 'pay';
        $bySide = $this->slipPathsBySide($stored);

        return $bySide[$side] ?? [];
    }

    /**
     * @param  list<string>  $paths
     */
    public function encodeSlipPaths(array $paths): ?string
    {
        $paths = array_values(array_unique(array_filter(array_map(
            static fn ($path) => trim((string) $path),
            $paths
        ))));

        if ($paths === []) {
            return null;
        }

        return count($paths) === 1 ? $paths[0] : json_encode($paths);
    }

    /**
     * @param  array{pay?: list<string>, receive?: list<string>}  $bySide
     */
    public function encodeSlipPathsBySide(array $bySide): ?string
    {
        $payload = [
            'pay' => array_values(array_unique($this->normalizePathList($bySide['pay'] ?? []))),
            'receive' => array_values(array_unique($this->normalizePathList($bySide['receive'] ?? []))),
        ];

        if ($payload['pay'] === [] && $payload['receive'] === []) {
            return null;
        }

        return json_encode($payload);
    }

    /**
     * @return list<string>
     */
    public function urlsForStored(?string $stored, ?string $side = null): array
    {
        $paths = $side
            ? $this->slipPathsForSide($stored, $side)
            : $this->normalizeSlipPaths($stored);
        $urls = [];
        foreach ($paths as $path) {
            $url = $this->urlForPath($path);
            if ($url) {
                $urls[] = $url;
            }
        }

        return $urls;
    }

    public function uploadKpaySlip(
        int $osId,
        string $fromDay,
        string $toDay,
        UploadedFile $file,
        ?string $settlementSide = 'pay'
    ): OsSettlementDraft {
        $side = $this->normalizeSettlementSide($settlementSide) === 'receive' ? 'receive' : 'pay';
        $dir = $this->storageDir($osId, $fromDay, $toDay);
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            $ext = 'jpg';
        }
        $filename = 'kpay-slip-'.Str::random(8).'.'.$ext;
        $path = $file->storeAs($dir, $filename, 'public');

        $draft = OsSettlementDraft::query()->firstOrNew([
            'os_user_id' => $osId,
            'from_date' => $fromDay,
            'to_date' => $toDay,
        ]);

        $bySide = $this->slipPathsBySide($draft->kpay_slip_path);
        $bySide[$side][] = $path;
        unset($bySide['legacy']);
        $draft->kpay_slip_path = $this->encodeSlipPathsBySide($bySide);
        $draft->save();

        return $draft;
    }

    /**
     * @param  list<UploadedFile>  $files
     */
    public function uploadKpaySlips(
        int $osId,
        string $fromDay,
        string $toDay,
        array $files,
        ?string $settlementSide = 'pay'
    ): OsSettlementDraft {
        $draft = null;
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }
            $draft = $this->uploadKpaySlip($osId, $fromDay, $toDay, $file, $settlementSide);
        }

        if (! $draft) {
            throw new \InvalidArgumentException('No image files provided.');
        }

        return $draft;
    }

    /**
     * Remove one proof image from the draft by zero-based index (within that side).
     */
    public function removeKpaySlipAt(
        int $osId,
        string $fromDay,
        string $toDay,
        int $index,
        ?string $settlementSide = 'pay'
    ): OsSettlementDraft {
        $side = $this->normalizeSettlementSide($settlementSide) === 'receive' ? 'receive' : 'pay';
        $draft = $this->getDraft($osId, $fromDay, $toDay);
        if (! $draft) {
            throw new \InvalidArgumentException(__('message.something_went_wrong'));
        }

        $bySide = $this->slipPathsBySide($draft->kpay_slip_path);
        $paths = $bySide[$side] ?? [];
        if ($index < 0 || $index >= count($paths)) {
            throw new \InvalidArgumentException(__('message.something_went_wrong'));
        }

        $removed = $paths[$index];
        array_splice($paths, $index, 1);
        $bySide[$side] = $paths;
        unset($bySide['legacy']);

        if (
            $removed
            && ! str_starts_with($removed, 'http://')
            && ! str_starts_with($removed, 'https://')
            && ! str_starts_with($removed, '/storage/')
            && Storage::disk('public')->exists($removed)
        ) {
            Storage::disk('public')->delete($removed);
        }

        $draft->kpay_slip_path = $this->encodeSlipPathsBySide($bySide);
        $draft->save();

        return $draft;
    }

    /**
     * Drop proof images for one settlement tab so the other tab never inherits them.
     */
    public function clearDraftSide(int $osId, string $fromDay, string $toDay, ?string $settlementSide): void
    {
        $side = $this->normalizeSettlementSide($settlementSide);
        if (! in_array($side, ['pay', 'receive'], true)) {
            return;
        }

        $draft = $this->getDraft($osId, $fromDay, $toDay);
        if (! $draft) {
            return;
        }

        $bySide = $this->slipPathsBySide($draft->kpay_slip_path);
        if (! empty($bySide['legacy'])) {
            $draft->kpay_slip_path = null;
            $draft->save();

            return;
        }

        $bySide[$side] = [];
        unset($bySide['legacy']);
        $encoded = $this->encodeSlipPathsBySide($bySide);
        if ($encoded === null) {
            $draft->delete();

            return;
        }

        $draft->kpay_slip_path = $encoded;
        $draft->save();
    }

    public function getDraft(int $osId, string $fromDay, string $toDay): ?OsSettlementDraft
    {
        return OsSettlementDraft::query()
            ->where('os_user_id', $osId)
            ->whereDate('from_date', Carbon::parse($fromDay)->toDateString())
            ->whereDate('to_date', Carbon::parse($toDay)->toDateString())
            ->first();
    }

    /**
     * A proof belongs to the sheet day it was uploaded on.
     * Later list days (next ငွေရှင်းတမ်း) must not inherit that screenshot.
     */
    public function draftSlipAppliesToItems(?OsSettlementDraft $draft, $items, ?string $settlementSide = null): bool
    {
        if (! $draft) {
            return false;
        }

        $paths = $settlementSide
            ? $this->slipPathsForSide($draft->kpay_slip_path, $settlementSide)
            : $this->normalizeSlipPaths($draft->kpay_slip_path);
        if ($paths === []) {
            return false;
        }

        $items = collect($items);
        if ($items->isEmpty() || empty($draft->updated_at)) {
            return false;
        }

        $newestDay = $items
            ->map(static fn ($item) => osSettlementListDateForItem($item)->toDateString())
            ->filter()
            ->sort()
            ->last();
        if (! $newestDay) {
            return false;
        }

        $slipDay = resolveRiderRemitDate(null, Carbon::parse($draft->updated_at));

        return $newestDay <= $slipDay;
    }

    public function getDraftKpayPath(
        int $osId,
        string $fromDay,
        string $toDay,
        $items = null,
        ?string $settlementSide = null
    ): ?string {
        $draft = $this->getDraft($osId, $fromDay, $toDay);
        if (! $draft) {
            return null;
        }

        if ($items !== null && ! $this->draftSlipAppliesToItems($draft, $items, $settlementSide)) {
            return null;
        }

        $side = $this->normalizeSettlementSide($settlementSide);
        if (in_array($side, ['pay', 'receive'], true)) {
            return $this->encodeSlipPaths($this->slipPathsForSide($draft->kpay_slip_path, $side));
        }

        return $draft->kpay_slip_path;
    }

    public function filterItemsBySettlementSide($items, ?string $settlementSide)
    {
        $side = $this->normalizeSettlementSide($settlementSide);
        if ($side === null) {
            return $items;
        }

        if ($side === 'kyo_shin') {
            return $items
                ->filter(static function ($item) {
                    if (! $item->isKyoShinGiven() || ! empty($item->admin_finished_at)) {
                        return false;
                    }

                    return (string) ($item->status ?? '') === 'completed';
                })
                ->values();
        }

        // Net by Online Shop party (not per-item sign / not raw client_id):
        // - net < 0 → Os ဆီသို့လွှဲရန် (all nettable items for that OS)
        // - net > 0 → Os ဆီမှရရန် only when there is nothing to transfer
        // - net == 0 → finish on pay side (nothing to move either way)
        return $items
            ->groupBy(fn ($item) => $this->itemOsPartyUserId($item))
            ->flatMap(function ($osItems) use ($side) {
                $nettable = $this->nettableSettlementItems($osItems);
                if ($nettable->isEmpty()) {
                    return collect();
                }

                $net = $this->netSettlementOsToPay($nettable);
                if ($side === 'pay') {
                    return $net <= 0 ? $nettable : collect();
                }

                return $net > 0 ? $nettable : collect();
            })
            ->values();
    }

    /**
     * Items that participate in Os ဆီသို့လွှဲရန် / Os ဆီမှရရန် netting.
     * ကြိုရှင်း pay-outs and Returns are excluded.
     */
    public function nettableSettlementItems($items)
    {
        return collect($items)
            ->filter(static function ($item) {
                if ($item->excludeFromSettlementAmount()) {
                    return false;
                }

                return (string) ($item->status ?? '') !== 'return';
            })
            ->values();
    }

    public function netSettlementOsToPay($items): float
    {
        return round(
            (float) $this->nettableSettlementItems($items)->sum(
                static fn ($item) => $item->settlementOsToPay()
            ),
            2
        );
    }

    public function normalizeSettlementSide(?string $settlementSide): ?string
    {
        return in_array($settlementSide, ['pay', 'receive', 'kyo_shin'], true) ? $settlementSide : null;
    }

    public function finishOs(
        int $osId,
        string $fromDay,
        string $toDay,
        int $finishedBy,
        array $slipCompany,
        string $osName,
        User $osClient,
        string $deliveryFormat = 'table',
        string $paymentMethod = 'kpay',
        ?string $settlementSide = null,
        ?array $itemIds = null,
        ?int $branchId = null,
        ?int $adminKpayAccountId = null
    ): OsSettlementBatch {
        $settlementSide = $this->normalizeSettlementSide($settlementSide);
        $isKyoShinSide = $settlementSide === 'kyo_shin';
        $wantedIds = array_values(array_unique(array_filter(array_map('intval', $itemIds ?? []))));

        if ($isKyoShinSide) {
            if ($wantedIds !== []) {
                $kyoShinQuery = DispatchOrderItem::query()
                    ->with(['order.client.city', 'order.city', 'fromBranch', 'toBranch', 'kyoShinItem'])
                    ->whereIn('id', $wantedIds)
                    ->whereNull('admin_finished_at')
                    ->where('status', 'completed')
                    ->whereHas('kyoShinItem');
                applyForcedItemOwnership($kyoShinQuery);
                if ($branchId !== null && $branchId > 0) {
                    applyDestinationBranchFilter($kyoShinQuery, $branchId);
                }
                $items = $this->filterItemsForOsParty($kyoShinQuery->get(), $osId);
            } else {
                $items = $this->filterItemsForOsParty(
                    $this->finishedKyoShinItemsForPeriod($fromDay, $toDay, $branchId),
                    $osId
                );
            }
        } else {
            $items = $this->completedItemsForPeriod($osId, $fromDay, $toDay, $branchId);
            $items = $this->filterItemsBySettlementSide($items, $settlementSide);
            if ($wantedIds !== []) {
                $items = $items
                    ->filter(static fn ($item) => in_array((int) $item->id, $wantedIds, true))
                    ->values();
            }
        }

        if ($items->isEmpty()) {
            throw new \RuntimeException(__('message.os_settlement_no_completed_items'));
        }

        $paymentMethod = in_array($paymentMethod, ['kpay', 'cash'], true) ? $paymentMethod : ($isKyoShinSide ? 'cash' : 'kpay');
        $kpayPath = $isKyoShinSide ? null : $this->sanitizeStoredSlipPath(
            $this->getDraftKpayPath($osId, $fromDay, $toDay, $items, $settlementSide)
        );

        $toDateRaw = Carbon::parse($toDay)->format('d-m-Y');
        $slipData = $this->buildSlipRows($items, $toDateRaw, ! $isKyoShinSide, $isKyoShinSide);
        $slipSender = $this->resolveSlipSender($osClient, $items, $osName);
        $amount = $isKyoShinSide
            ? -1 * (float) $items->sum(static fn ($item) => $item->kyoShinPayAmount())
            : (float) $slipData['totals']['os_to_pay'];
        $itemIds = $items->pluck('id')->map(fn ($id) => (int) $id)->values()->all();

        // Pay / receive need proof when money moves. Net-zero or ကြိုရှင်း is Finish-only.
        if (! $isKyoShinSide && abs((float) $amount) > 0.00001 && ! $kpayPath) {
            throw new \RuntimeException(__('message.os_settlement_kpay_slip_required'));
        }

        $needsAdminKpay = $settlementSide === 'pay'
            && $paymentMethod === 'kpay'
            && abs((float) $amount) > 0.00001;
        $adminAccount = null;
        if ($needsAdminKpay) {
            if (! $adminKpayAccountId) {
                throw new \RuntimeException(__('message.admin_kpay_account_required'));
            }
            $adminAccount = AdminKpayAccount::query()->find($adminKpayAccountId);
            if (! $adminAccount) {
                throw new \RuntimeException(__('message.admin_kpay_account_not_found'));
            }
            $transferNeed = round(abs((float) $amount), 2);
            if ((float) $adminAccount->balance + 0.00001 < $transferNeed) {
                throw new \RuntimeException(__('message.admin_kpay_insufficient_balance'));
            }
        }

        // Money Transfer / batch period = items' list day (10:00 AM Yangon cutoff),
        // not the filter day when unfinished rows were carried into "today".
        [$batchFromDay, $batchToDay] = $this->listDayRangeFromItems($items, $fromDay, $toDay);

        @set_time_limit(120);

        $batch = DB::transaction(function () use (
            $osId,
            $batchFromDay,
            $batchToDay,
            $amount,
            $paymentMethod,
            $settlementSide,
            $deliveryFormat,
            $osClient,
            $isKyoShinSide,
            $kpayPath,
            $itemIds,
            $finishedBy,
            $adminAccount,
            $needsAdminKpay,
            $fromDay,
            $toDay
        ) {
            if ($needsAdminKpay && $adminAccount) {
                $adminAccount->deduct(abs((float) $amount));
            }

            $created = OsSettlementBatch::create([
                'os_user_id' => $osId,
                'from_date' => $batchFromDay,
                'to_date' => $batchToDay,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'settlement_side' => $settlementSide,
                'delivery_format' => $this->normalizeDeliveryFormat($deliveryFormat),
                'kpay_name' => $this->kpayNameFromUser($osClient),
                'kpay_no' => $this->kpayNoFromUser($osClient),
                'admin_kpay_account_id' => $adminAccount?->id,
                'kpay_slip_path' => $isKyoShinSide ? null : $kpayPath,
                'item_ids' => $itemIds,
                'finished_by' => $finishedBy,
                'finished_at' => now(),
            ]);

            DispatchOrderItem::query()
                ->whereIn('id', $itemIds)
                ->update([
                    'admin_finished_at' => now(),
                    'admin_updated_at' => now(),
                    'updated_at' => now(),
                ]);

            app(\App\Services\KyoShinService::class)->syncFinishedForItemIds($itemIds, $finishedBy);

            // Clear this tab's proof so the other tab (pay↔receive) never inherits it.
            if (! $isKyoShinSide) {
                $this->clearDraftSide($osId, $fromDay, $toDay, $settlementSide);
            }
            $remaining = $this->completedItemsForPeriod($osId, $fromDay, $toDay);
            if ($remaining->isEmpty()) {
                $this->getDraft($osId, $fromDay, $toDay)?->delete();
            }

            return $created;
        });

        $isReceiveSide = $settlementSide === 'receive';
        $receiveId = null;

        if ($isReceiveSide) {
            // Receive row first (no push yet) — push runs after response with file generation.
            try {
                $receive = app(OsReceiveSettlementService::class)->createFromBatch($batch, $finishedBy, false);
                $receiveId = (int) $receive->id;
            } catch (\Throwable $e) {
                Log::warning('os receive settlement create failed', [
                    'batch_id' => $batch->id,
                    'error' => $e->getMessage(),
                ]);
            }
        } elseif (! $isKyoShinSide) {
            try {
                app(MoneyTransferService::class)->recordFromSettlementBatch($batch);
            } catch (\Throwable $e) {
                Log::warning('os settlement money transfer failed', [
                    'batch_id' => $batch->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $batchId = (int) $batch->id;
        $osClientId = ($osId > 0 && $osClient && (int) $osClient->id > 0) ? (int) $osClient->id : 0;

        // Slip files + FCM are slow (especially Finish All × N OS). Do them after the HTTP response.
        dispatch(function () use (
            $batchId,
            $osClientId,
            $isReceiveSide,
            $isKyoShinSide,
            $receiveId,
            $slipCompany,
            $slipSender,
            $slipData,
            $toDateRaw
        ) {
            @set_time_limit(180);
            $freshBatch = OsSettlementBatch::query()->find($batchId);
            if (! $freshBatch) {
                return;
            }

            try {
                app(OsSettlementService::class)->generateLightSettlementFiles(
                    $freshBatch,
                    $slipCompany,
                    $slipSender,
                    $slipData,
                    $toDateRaw
                );
            } catch (\Throwable $e) {
                Log::warning('os settlement light files failed', [
                    'batch_id' => $batchId,
                    'error' => $e->getMessage(),
                ]);
            }

            if ($isReceiveSide) {
                if ($receiveId > 0 && $osClientId > 0) {
                    try {
                        $receive = \App\Models\OsReceiveSettlement::query()->find($receiveId);
                        $osUser = User::query()->find($osClientId);
                        if ($receive && $osUser) {
                            app(OsReceiveSettlementService::class)->notifyPending($osUser, $receive);
                        }
                    } catch (\Throwable $e) {
                        Log::warning('os receive pending notify failed', [
                            'batch_id' => $batchId,
                            'receive_id' => $receiveId,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            } elseif ($osClientId > 0 && ! $isKyoShinSide) {
                try {
                    $osUser = User::query()->find($osClientId);
                    if ($osUser) {
                        app(OsSettlementService::class)->notifyOsSettlement($osUser, $freshBatch->fresh());
                    }
                } catch (\Throwable $e) {
                    Log::warning('os settlement notify failed', [
                        'batch_id' => $batchId,
                        'os_user_id' => $osClientId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            try {
                app(OsSettlementService::class)->generatePdfSettlementFiles(
                    $freshBatch->fresh(),
                    $slipCompany,
                    $slipSender,
                    $slipData,
                    $toDateRaw
                );
            } catch (\Throwable $e) {
                Log::warning('os settlement pdf files failed', [
                    'batch_id' => $batchId,
                    'error' => $e->getMessage(),
                ]);
            }
        })->afterResponse();

        return $batch->fresh();
    }

    /**
     * Lightweight unfinished check (no eager loads) used after finishing one side.
     */
    public function hasUnfinishedCompletedItems(int $osId, string $fromDay, string $toDay): bool
    {
        return $this->completedItemsForPeriod($osId, $fromDay, $toDay)->isNotEmpty();
    }

    public function generateSettlementFiles(
        OsSettlementBatch $batch,
        array $slipCompany,
        array $slipSender,
        array $slipData,
        string $invoiceDate
    ): void {
        $this->generateLightSettlementFiles($batch, $slipCompany, $slipSender, $slipData, $invoiceDate);
        $this->generatePdfSettlementFiles($batch, $slipCompany, $slipSender, $slipData, $invoiceDate);
    }

    protected function settlementViewData(
        array $slipCompany,
        array $slipSender,
        array $slipData,
        string $invoiceDate
    ): array {
        return [
            'slipCompany' => $slipCompany,
            'slipSender' => $slipSender,
            'slipRows' => $slipData['rows'],
            'slipTotals' => $slipData['totals'],
            'slipInvoiceDate' => $invoiceDate,
            'standalone' => true,
        ];
    }

    public function generateLightSettlementFiles(
        OsSettlementBatch $batch,
        array $slipCompany,
        array $slipSender,
        array $slipData,
        string $invoiceDate
    ): void {
        $dir = 'os-settlements/batch-'.$batch->id;
        Storage::disk('public')->makeDirectory($dir);

        $viewData = $this->settlementViewData($slipCompany, $slipSender, $slipData, $invoiceDate);
        $tablePath = $dir.'/slip-table.html';
        Storage::disk('public')->put($tablePath, view('order.os-settlement-slip-table', $viewData)->render());

        $imagePath = $this->generateSlipImage($dir, $slipData, $slipCompany, $slipSender, $invoiceDate);

        $batch->update([
            'slip_table_path' => $tablePath,
            'slip_image_path' => $imagePath,
        ]);
    }

    public function generatePdfSettlementFiles(
        OsSettlementBatch $batch,
        array $slipCompany,
        array $slipSender,
        array $slipData,
        string $invoiceDate
    ): void {
        $dir = 'os-settlements/batch-'.$batch->id;
        Storage::disk('public')->makeDirectory($dir);
        $viewData = $this->settlementViewData($slipCompany, $slipSender, $slipData, $invoiceDate);

        try {
            $pdf = Pdf::loadView('order.os-settlement-slip-pdf', $viewData)->setPaper('a4', 'landscape');
            $pdfPath = $dir.'/slip.pdf';
            Storage::disk('public')->put($pdfPath, $pdf->output());
            $batch->update(['slip_pdf_path' => $pdfPath]);
        } catch (\Throwable $e) {
            Log::warning('os settlement slip pdf failed', [
                'batch_id' => $batch->id,
                'error' => $e->getMessage(),
            ]);
        }

        if ($batch->kpay_slip_path) {
            try {
                $kpayPdfPath = $this->generateKpayPdf($batch, $dir);
                $batch->update(['kpay_pdf_path' => $kpayPdfPath]);
            } catch (\Throwable $e) {
                Log::warning('os settlement kpay pdf failed', [
                    'batch_id' => $batch->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        try {
            $combinedPdfPath = $this->generateCombinedPdf($batch, $dir, $viewData);
            $batch->update(['combined_pdf_path' => $combinedPdfPath]);
        } catch (\Throwable $e) {
            Log::warning('os settlement combined pdf failed', [
                'batch_id' => $batch->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function generateCombinedPdf(OsSettlementBatch $batch, string $dir, array $viewData): string
    {
        $kpayPath = $batch->kpay_slip_path;
        $absolute = $kpayPath ? Storage::disk('public')->path($kpayPath) : null;

        $pdf = Pdf::loadView('order.os-settlement-combined-pdf', array_merge($viewData, [
            'kpayName' => $batch->kpay_name,
            'kpayNo' => $batch->kpay_no,
            'kpayImagePath' => $absolute,
            'amount' => $batch->amount,
        ]))->setPaper('a4', 'landscape');

        $pdfPath = $dir.'/combined.pdf';
        Storage::disk('public')->put($pdfPath, $pdf->output());

        return $pdfPath;
    }

    protected function generateSlipImage(
        string $dir,
        array $slipData,
        array $slipCompany,
        array $slipSender,
        string $invoiceDate
    ): string {
        $rows = $slipData['rows'];
        $lineHeight = 22;
        $headerHeight = 120;
        $tableHeader = 28;
        $rowCount = max(1, count($rows));
        $footerHeight = 28;
        $width = 900;
        $height = $headerHeight + $tableHeader + ($rowCount * $lineHeight) + $footerHeight + 40;

        $img = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 30, 30, 30);
        $red = imagecolorallocate($img, 220, 38, 38);
        $orange = imagecolorallocate($img, 249, 115, 22);
        $gray = imagecolorallocate($img, 120, 120, 120);
        imagefill($img, 0, 0, $white);

        $font = 3;
        $fontBold = 4;
        imagestring($img, $fontBold, 20, 12, (string) ($slipCompany['name'] ?? 'Point Delivery'), $black);
        imagestring($img, $font, 20, 32, (string) ($slipCompany['address'] ?? ''), $gray);
        imagestring($img, $font, 20, 48, (string) ($slipCompany['phone'] ?? ''), $gray);
        imagestring($img, $font, 600, 12, 'INVOICE DATE: '.$invoiceDate, $black);
        imagestring($img, $font, 20, 72, 'Sender: '.($slipSender['name'] ?? ''), $black);
        imagestring($img, $font, 20, 88, 'Phone: '.($slipSender['phone'] ?? ''), $black);

        $y = $headerHeight;
        imagefilledrectangle($img, 10, $y, $width - 10, $y + $tableHeader, $orange);
        $cols = ['NO', 'DATE', 'CUSTOMER', 'PHONE', 'ITEM', 'DELI', 'Gate', 'OSTOPAY'];
        $xPositions = [15, 45, 120, 250, 370, 450, 560, 680];
        foreach ($cols as $i => $label) {
            imagestring($img, $fontBold, $xPositions[$i], $y + 8, $label, $white);
        }

        $y += $tableHeader;
        foreach ($rows as $index => $row) {
            if ($index % 2 === 1) {
                imagefilledrectangle($img, 10, $y, $width - 10, $y + $lineHeight, imagecolorallocate($img, 245, 245, 245));
            }
            $osToPayText = (string) ($row['deli_amount_display'] ?? number_format((float) $row['deli_amount']));
            $osPayDisplay = (string) $row['os_to_pay_display'];
            $osPayColor = ! empty($row['os_to_pay_is_receive']) ? $red : $black;
            imagestring($img, $font, 15, $y + 6, (string) ($index + 1), $black);
            imagestring($img, $font, 45, $y + 6, Str::limit((string) $row['date'], 10, ''), $black);
            imagestring($img, $font, 120, $y + 6, Str::limit((string) $row['customer'], 16, ''), $black);
            imagestring($img, $font, 250, $y + 6, Str::limit((string) $row['phone'], 12, ''), $black);
            imagestring($img, $font, 370, $y + 6, number_format((float) $row['item_value']), $black);
            imagestring($img, $font, 450, $y + 6, Str::limit($osToPayText, 16, ''), $black);
            imagestring($img, $font, 560, $y + 6, Str::limit((string) ($row['gate'] ?? '-'), 10, ''), $black);
            imagestring($img, $font, 680, $y + 6, $osPayDisplay, $osPayColor);
            $y += $lineHeight;
        }

        imagefilledrectangle($img, 10, $y, $width - 10, $y + $footerHeight, $orange);
        $totals = $slipData['totals'];
        $totalOsToPay = (float) $totals['os_to_pay'];
        imagestring($img, $fontBold, 120, $y + 8, 'TOTAL', $white);
        imagestring($img, $fontBold, 370, $y + 8, number_format((float) $totals['item_value']), $white);
        imagestring($img, $fontBold, 450, $y + 8, number_format((float) $totals['deli_amount']), $white);
        imagestring($img, $fontBold, 560, $y + 8, number_format((float) ($totals['gate_amount'] ?? 0)), $white);
        imagestring($img, $fontBold, 680, $y + 8, number_format($totalOsToPay), $totalOsToPay < 0 ? $red : $white);

        $imagePath = $dir.'/slip.png';
        $fullPath = Storage::disk('public')->path($imagePath);
        imagepng($img, $fullPath);
        imagedestroy($img);

        return $imagePath;
    }

    protected function generateKpayPdf(OsSettlementBatch $batch, string $dir): string
    {
        $kpayPath = $batch->kpay_slip_path;
        $absolute = Storage::disk('public')->path($kpayPath);
        $pdf = Pdf::loadView('order.os-settlement-kpay-pdf', [
            'kpayName' => $batch->kpay_name,
            'kpayNo' => $batch->kpay_no,
            'kpayImagePath' => $absolute,
            'amount' => $batch->amount,
        ])->setPaper('a4');
        $pdfPath = $dir.'/kpay.pdf';
        Storage::disk('public')->put($pdfPath, $pdf->output());

        return $pdfPath;
    }

    public function notifyOsSettlement(User $osClient, OsSettlementBatch $batch): void
    {
        $batch->refresh();
        $urls = $this->publicUrls($batch);

        $this->pushService->notifyUser(
            $osClient,
            self::NOTIFICATION_TYPE,
            __('message.os_settlement_notification_title'),
            __('message.os_settlement_notification_body', [
                'amount' => number_format((float) $batch->amount),
                'from' => Carbon::parse($batch->from_date)->format('d-m-Y'),
                'to' => Carbon::parse($batch->to_date)->format('d-m-Y'),
            ]),
            [
                'id' => 'SETTLEMENT_'.$batch->id,
                'settlement_id' => (string) $batch->id,
                'category' => 'os_settlement',
                'delivery_format' => $batch->delivery_format ?? 'table',
                'slip_table_url' => $urls['slip_table_url'],
                'slip_image_url' => $urls['slip_image_url'],
                'slip_pdf_url' => $urls['slip_pdf_url'],
                'kpay_image_url' => $urls['kpay_image_url'],
                'kpay_pdf_url' => $urls['kpay_pdf_url'],
                'combined_pdf_url' => $urls['combined_pdf_url'],
                'amount' => (string) $batch->amount,
                'from_date' => Carbon::parse($batch->from_date)->format('d-m-Y'),
                'to_date' => Carbon::parse($batch->to_date)->format('d-m-Y'),
                'app' => 'os',
            ]
        );
    }

    public function publicUrls(OsSettlementBatch $batch): array
    {
        return [
            'slip_table_url' => $this->urlForPath($batch->slip_table_path),
            'slip_image_url' => $this->urlForPath($batch->slip_image_path),
            'slip_pdf_url' => $this->urlForPath($batch->slip_pdf_path),
            'kpay_image_url' => $this->urlForPath($batch->kpay_slip_path),
            'kpay_pdf_url' => $this->urlForPath($batch->kpay_pdf_path),
            'combined_pdf_url' => $this->urlForPath($batch->combined_pdf_path),
        ];
    }

    public function normalizeDeliveryFormat(?string $format): string
    {
        // Pay slip is table-only (Image / PDF removed).
        return 'table';
    }

    public function slipRowsForBatch(OsSettlementBatch $batch): array
    {
        $itemIds = is_array($batch->item_ids) ? $batch->item_ids : [];
        if ($itemIds === []) {
            return ['rows' => [], 'totals' => [
                'os_paid' => 0.0,
                'item_value' => 0.0,
                'deli_amount' => 0.0,
                'os_to_pay' => 0.0,
            ]];
        }

        $osId = (int) ($batch->os_user_id ?? 0);
        $items = DispatchOrderItem::query()
            ->with(['order.client.city', 'order.city', 'fromBranch', 'toBranch', 'kyoShinItem'])
            ->whereIn('id', $itemIds)
            ->get();
        if ($osId > 0) {
            $items = $this->filterItemsForOsParty($items, $osId);
        }
        $items = $items
            ->sortBy(fn ($item) => array_search((int) $item->id, $itemIds, true) ?: 9999)
            ->values();

        $invoiceDate = $batch->to_date
            ? Carbon::parse($batch->to_date)->format('d-m-Y')
            : yangonTodayDate();

        return $this->buildSlipRows($items, $invoiceDate, true);
    }

    public function urlForPath(?string $path): ?string
    {
        // Accept a single storage path, or a JSON list (use the first image).
        $paths = $this->normalizeSlipPaths($path);
        $first = $paths[0] ?? null;
        if (! $first) {
            return null;
        }

        // Already-resolved absolute/relative URL.
        if (str_starts_with($first, 'http://') || str_starts_with($first, 'https://') || str_starts_with($first, '/storage/')) {
            return $first;
        }

        return Storage::disk('public')->url($first);
    }

    /**
     * Keep only slip paths that still exist on the public disk.
     * Multi-image drafts store a JSON array — never pass that raw string to Storage::exists().
     */
    public function sanitizeStoredSlipPath(?string $stored): ?string
    {
        $existing = [];
        foreach ($this->normalizeSlipPaths($stored) as $path) {
            if ($path === '') {
                continue;
            }
            if (
                str_starts_with($path, 'http://')
                || str_starts_with($path, 'https://')
                || str_starts_with($path, '/storage/')
                || Storage::disk('public')->exists($path)
            ) {
                $existing[] = $path;
            }
        }

        return $this->encodeSlipPaths($existing);
    }

    protected function storageDir(int $osId, string $fromDay, string $toDay): string
    {
        return 'os-settlements/drafts/'.$osId.'_'.$fromDay.'_'.$toDay;
    }
}
