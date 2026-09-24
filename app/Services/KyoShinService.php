<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\DispatchOrderItem;
use App\Models\KyoShinBatch;
use App\Models\KyoShinCap;
use App\Models\KyoShinDailyLedger;
use App\Models\KyoShinItem;
use App\Models\OsCashPayout;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class KyoShinService
{
    public const TAB_OS_LIST = 'os_list';
    public const TAB_ADVANCED_PAID = 'advanced_paid';
    public const TAB_FINISHED = 'finished';

    public function tablesReady(): bool
    {
        return Schema::hasTable('kyo_shin_caps') && Schema::hasTable('kyo_shin_items');
    }

    public function scopeKeyForBranch(int $branchId): string
    {
        return 'branch_'.$branchId;
    }

    public function branchIdFromScope(string $scopeKey): ?int
    {
        if (preg_match('/^branch_(\d+)$/', $scopeKey, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    /**
     * @return list<array{key: string, label: string, type: string, branch_id: int|null, hub_id: int|null}>
     */
    public function scopes(?User $user = null): array
    {
        $branches = function_exists('destinationBranchTabs')
            ? destinationBranchTabs($user)
            : Branch::query()->where('status', 1)->orderBy('name')->get(['id', 'name']);

        return $branches->map(function ($branch) {
            $id = (int) $branch->id;

            return [
                'key' => $this->scopeKeyForBranch($id),
                'label' => trim((string) $branch->name) !== '' ? (string) $branch->name : ('#'.$id),
                'type' => 'branch',
                'branch_id' => $id,
                'hub_id' => null,
            ];
        })->values()->all();
    }

    public function scopeByKey(string $key, ?User $user = null): ?array
    {
        foreach ($this->scopes($user) as $scope) {
            if ($scope['key'] === $key) {
                return $scope;
            }
        }

        $branchId = $this->branchIdFromScope($key);
        if (! $branchId) {
            return null;
        }

        $branch = Branch::query()->where('status', 1)->where('id', $branchId)->first(['id', 'name']);
        if (! $branch) {
            return null;
        }

        return [
            'key' => $key,
            'label' => trim((string) $branch->name) !== '' ? (string) $branch->name : ('#'.$branchId),
            'type' => 'branch',
            'branch_id' => $branchId,
            'hub_id' => null,
        ];
    }

    public function actorCanAccess(?User $user, string $scopeKey): bool
    {
        if (! $user) {
            return false;
        }

        return in_array($scopeKey, $this->allowedScopeKeys($user), true);
    }

    /**
     * @return list<string>
     */
    public function allowedScopeKeys(?User $user = null): array
    {
        $user = $user ?? auth()->user();
        if (! $user || ! $user->can('order-list')) {
            return [];
        }

        if (! isAdminPanelUser($user) && ! isSuperAdmin($user)) {
            return [];
        }

        return array_column($this->scopes($user), 'key');
    }

    public function defaultScopeKey(?User $user = null): ?string
    {
        $keys = $this->allowedScopeKeys($user);

        return $keys[0] ?? null;
    }

    public function setTotal(string $scopeKey, float $amount, ?User $actor = null): KyoShinCap
    {
        if (! $this->scopeByKey($scopeKey)) {
            abort(404);
        }

        $cap = KyoShinCap::query()->firstOrNew(['scope_key' => $scopeKey]);
        $cap->total_amount = round(max(0, $amount), 2);
        $cap->updated_by = $actor?->id;
        $cap->save();
        $this->recordDailyLedger($scopeKey);

        return $cap;
    }

    public function totalFor(string $scopeKey): float
    {
        if (! $this->tablesReady()) {
            return 0.0;
        }

        return (float) (KyoShinCap::query()->where('scope_key', $scopeKey)->value('total_amount') ?? 0);
    }

    public function givenAmountFor(string $scopeKey): float
    {
        if (! $this->tablesReady()) {
            return 0.0;
        }

        return (float) KyoShinItem::query()
            ->where('scope_key', $scopeKey)
            ->whereIn('status', [KyoShinItem::STATUS_ADVANCED_PAID, KyoShinItem::STATUS_FINISHED])
            ->sum('amount');
    }

    public function unfinishedAmountFor(string $scopeKey): float
    {
        return (float) $this->allTrackedItems($scopeKey, null, null)
            ->filter(fn (DispatchOrderItem $item) => $this->isOsReceivableItem($item))
            ->sum(fn (DispatchOrderItem $item) => $this->itemAmount($item));
    }

    public function finishedAmountFor(string $scopeKey): float
    {
        return (float) $this->allTrackedItems($scopeKey, null, null)
            ->filter(fn (DispatchOrderItem $item) => $this->isRecoveredKyoShinItem($item))
            ->sum(fn (DispatchOrderItem $item) => $this->itemAmount($item));
    }

    /**
     * @return array{
     *     total: float,
     *     cap: float,
     *     cash_on_hand: float,
     *     returned_amount: float,
     *     returned_today: float,
     *     cash_held: float,
     *     os_receivable: float,
     *     balance: float,
     *     balance_sign: string,
     *     advanced_paid: float,
     *     remain: float,
     *     thein_total: float,
     *     thein_cash_held: float,
     *     thein_os_receivable: float,
     *     thein_balance: float,
     *     thein_advanced: float,
     *     thein_remain: float
     * }
     */
    public function summary(string $scopeKey): array
    {
        $this->syncPendingFinished();

        $cap = $this->totalFor($scopeKey);
        $items = $this->allTrackedItems($scopeKey, null, null);
        $given = (float) $items->sum(fn (DispatchOrderItem $item) => $this->itemAmount($item));
        $returned = (float) $items
            ->filter(fn (DispatchOrderItem $item) => $this->isPendingReturnedMoney($item))
            ->sum(fn (DispatchOrderItem $item) => $this->itemAmount($item));
        $recovered = (float) $items
            ->filter(fn (DispatchOrderItem $item) => $this->isRecoveredKyoShinItem($item))
            ->sum(fn (DispatchOrderItem $item) => $this->itemAmount($item));
        $unfinished = max(0, $given - $returned - $recovered);
        $leftover = $cap - $given;
        $cashOnHand = $leftover + $recovered;
        $balance = $cap - $cashOnHand - $returned - $unfinished;

        return [
            'cap' => $cap,
            'total' => $cap,
            'cash_on_hand' => $cashOnHand,
            'returned_amount' => $returned,
            'returned_today' => $returned,
            'cash_held' => $cashOnHand,
            'os_receivable' => $unfinished,
            'balance' => $balance,
            'balance_sign' => $this->balanceSign($balance),
            'balance_display' => $this->formatSigned($balance),
            'thein_balance_display' => $this->formatSigned($this->toThein($balance), 2),
            'advanced_paid' => $recovered,
            'remain' => $unfinished,
            'thein_total' => $this->toThein($cap),
            'thein_cash_on_hand' => $this->toThein($cashOnHand),
            'thein_returned_today' => $this->toThein($returned),
            'thein_cash_held' => $this->toThein($cashOnHand),
            'thein_os_receivable' => $this->toThein($unfinished),
            'thein_balance' => $this->toThein($balance),
            'thein_advanced' => $this->toThein($recovered),
            'thein_remain' => $this->toThein($unfinished),
        ];
    }

    /**
     * @return list<array{key: string, label: string, total: float, cap: float, advanced_paid: float, remain: float}>
     */
    public function summaries(): array
    {
        $rows = [];
        foreach ($this->scopes() as $scope) {
            $sum = $this->summary($scope['key']);
            $rows[] = [
                'key' => $scope['key'],
                'label' => $scope['label'],
                'cap' => $sum['cap'],
                'total' => $sum['cap'],
                'cash_on_hand' => $sum['cash_on_hand'],
                'returned_today' => $sum['returned_today'],
                'cash_held' => $sum['cash_held'],
                'os_receivable' => $sum['os_receivable'],
                'balance' => $sum['balance'],
                'advanced_paid' => $sum['advanced_paid'],
                'remain' => $sum['remain'],
                'thein_total' => $this->toThein($sum['cap']),
                'thein_cash_on_hand' => $sum['thein_cash_on_hand'],
                'thein_returned_today' => $sum['thein_returned_today'],
                'thein_cash_held' => $sum['thein_cash_held'],
                'thein_os_receivable' => $sum['thein_os_receivable'],
                'thein_balance' => $sum['thein_balance'],
                'thein_advanced' => $sum['thein_advanced'],
                'thein_remain' => $sum['thein_remain'],
            ];
        }

        return $rows;
    }

    public function ledgerTableReady(): bool
    {
        return Schema::hasTable('kyo_shin_daily_ledgers');
    }

    /**
     * @param  array<string, mixed>|null  $summary
     */
    public function recordDailyLedger(string $scopeKey, ?array $summary = null): void
    {
        if (! $this->ledgerTableReady() || $scopeKey === '') {
            return;
        }

        $summary ??= $this->summary($scopeKey);
        $day = now('Asia/Yangon')->toDateString();
        $payload = [
            'sa_amount' => round((float) ($summary['cap'] ?? $summary['total'] ?? 0), 2),
            'balance' => round((float) ($summary['balance'] ?? 0), 2),
            'cash_held' => round((float) ($summary['cash_held'] ?? 0), 2),
            'os_receivable' => round((float) ($summary['os_receivable'] ?? 0), 2),
        ];
        if (Schema::hasColumn('kyo_shin_daily_ledgers', 'returned_amount')) {
            $payload['returned_amount'] = round((float) ($summary['returned_today'] ?? $summary['returned_amount'] ?? 0), 2);
        }

        KyoShinDailyLedger::query()->updateOrCreate(
            ['scope_key' => $scopeKey, 'ledger_date' => $day],
            $payload
        );
    }

    public function recordDailyLedgers(): int
    {
        if (! $this->ledgerTableReady()) {
            return 0;
        }

        $count = 0;
        foreach ($this->scopes() as $scope) {
            $this->recordDailyLedger($scope['key']);
            $count++;
        }

        return $count;
    }

    /**
     * @return Collection<int, KyoShinDailyLedger>
     */
    public function dailyLedgers(string $scopeKey, int $limit = 90): Collection
    {
        if (! $this->ledgerTableReady()) {
            return collect();
        }

        return KyoShinDailyLedger::query()
            ->where('scope_key', $scopeKey)
            ->orderByDesc('ledger_date')
            ->limit($limit)
            ->get()
            ->each(function (KyoShinDailyLedger $ledger) {
                $returned = (float) ($ledger->returned_amount ?? 0);
                $ledger->setAttribute('returned_today', $returned);
                $ledger->setAttribute('cash_on_hand', (float) $ledger->cash_held);
            });
    }

    public function formatSigned(float $amount, int $decimals = 0): string
    {
        $rounded = round($amount, $decimals);
        if (abs($rounded) < ($decimals > 0 ? 0.005 : 0.5)) {
            return $decimals > 0 ? number_format(0, $decimals) : '0';
        }

        return ($rounded > 0 ? '+' : '').number_format($rounded, $decimals);
    }

    public function balanceSign(float $amount): string
    {
        $rounded = round($amount, 2);
        if (abs($rounded) < 0.005) {
            return 'zero';
        }

        return $rounded > 0 ? 'plus' : 'minus';
    }

    public function toThein(float $ks): float
    {
        return round($ks / 100000, 2);
    }

    public function itemAmount(DispatchOrderItem $item): float
    {
        if ($item->relationLoaded('kyoShinItem') && $item->kyoShinItem) {
            return round((float) $item->kyoShinItem->amount, 2);
        }

        return round(max(0, (float) ($item->item_value ?? 0)), 2);
    }

    /**
     * SuperAdmin may revise Item Value on a ကြိုရှင်း parcel — keep ledger amount in sync.
     */
    public function syncAdvanceAmountFromDispatchItem(DispatchOrderItem $item): bool
    {
        if (! $this->tablesReady()) {
            return false;
        }

        $item->loadMissing('kyoShinItem.batch');
        $kyo = $item->kyoShinItem;
        if (! $kyo) {
            return false;
        }

        $newAmount = round(max(0, (float) ($item->item_value ?? 0)), 2);
        $oldAmount = round((float) ($kyo->amount ?? 0), 2);
        if (abs($newAmount - $oldAmount) < 0.005) {
            return false;
        }

        $kyo->forceFill(['amount' => $newAmount])->save();

        $batch = $kyo->batch;
        if ($batch) {
            $batchTotal = (float) KyoShinItem::query()
                ->where('batch_id', $batch->id)
                ->sum('amount');
            $batch->forceFill(['amount' => round($batchTotal, 2)])->save();
        }

        $scopeKey = (string) ($kyo->scope_key ?? $this->scopeKeyForBranch((int) ($item->to_branch_id ?? 0)));
        if ($scopeKey !== '') {
            $this->recordDailyLedger($scopeKey);
        }

        return true;
    }

    /**
     * @return Collection<int, object>
     */
    public function osRows(string $scopeKey, string $tab, ?string $fromDay, ?string $toDay): Collection
    {
        $allItems = $this->itemsForTab($scopeKey, self::TAB_OS_LIST, $fromDay, $toDay);
        $tabItems = $this->filterItemsByTab($allItems, $tab);
        $groupedAll = $allItems->groupBy(static fn (DispatchOrderItem $item) => (int) ($item->order?->client_id ?? 0));
        $osIds = $tabItems
            ->map(static fn (DispatchOrderItem $item) => (int) ($item->order?->client_id ?? 0))
            ->unique()
            ->values();
        $clients = User::query()
            ->whereIn('id', $osIds->filter(fn ($id) => (int) $id > 0)->all() ?: [0])
            ->with('city')
            ->get()
            ->keyBy('id');

        return $osIds->map(function ($osId) use ($clients, $tab, $groupedAll, $tabItems) {
            $osId = (int) $osId;
            $itemGroup = $groupedAll->get($osId, collect());
            $client = $osId > 0 ? $clients->get($osId) : null;
            $name = $osId > 0
                ? (trim((string) ($client?->name ?? '')) !== '' ? trim((string) $client->name) : ('#'.$osId))
                : __('message.no_os');
            $cityName = trim((string) ($client?->city?->name ?? ''));
            if ($cityName !== '') {
                $name .= ' ('.$cityName.')';
            }

            $osTabItems = $tabItems
                ->filter(static fn (DispatchOrderItem $item) => (int) ($item->order?->client_id ?? 0) === $osId)
                ->values();
            // OS List: lifetime advanced for that OS. Delivered / Return tabs: only this tab's parcel amounts.
            $tabAmount = (float) $osTabItems->sum(fn (DispatchOrderItem $item) => $this->itemAmount($item));
            $totalAdvanced = in_array($tab, [self::TAB_ADVANCED_PAID, self::TAB_FINISHED], true)
                ? $tabAmount
                : (float) $itemGroup->sum(fn (DispatchOrderItem $item) => $this->itemAmount($item));
            $advancedPaid = (float) $itemGroup
                ->filter(fn (DispatchOrderItem $item) => $this->isClosedKyoShinItem($item))
                ->sum(fn (DispatchOrderItem $item) => $this->itemAmount($item));
            $remainItems = $tab === self::TAB_OS_LIST
                ? $itemGroup->filter(function (DispatchOrderItem $item) {
                    return ! $this->isClosedKyoShinItem($item)
                        && ! $this->isRecoveredKyoShinItem($item)
                        && ! $this->isPendingReturnedMoney($item);
                })
                : $osTabItems->filter(fn (DispatchOrderItem $item) => ! $this->isRecoveredKyoShinItem($item));
            $remain = (float) $remainItems->sum(fn (DispatchOrderItem $item) => $this->itemAmount($item));
            $recoveredCount = $itemGroup
                ->filter(fn (DispatchOrderItem $item) => $this->isRecoveredKyoShinItem($item))
                ->count();
            $allChecked = $itemGroup->isNotEmpty() && $recoveredCount === $itemGroup->count();
            $pendingReturnedCount = $osTabItems
                ->filter(fn (DispatchOrderItem $item) => $this->isPendingReturnedMoney($item))
                ->count();
            $dueDates = $itemGroup
                ->map(fn (DispatchOrderItem $item) => $item->kyoShinItem?->due_finished_at)
                ->filter()
                ->map(fn ($day) => $day instanceof Carbon ? $day->toDateString() : Carbon::parse((string) $day)->toDateString())
                ->sort()
                ->values();
            $createdDates = $itemGroup
                ->map(fn (DispatchOrderItem $item) => $this->yangonDay($item->kyoShinItem?->advanced_paid_at))
                ->filter()
                ->sort()
                ->values();
            $finishedDates = $itemGroup
                ->filter(fn (DispatchOrderItem $item) => $this->isDeliveredKyoShinItem($item))
                ->map(fn (DispatchOrderItem $item) => $item->kyoShinItem?->finished_at ?: $item->admin_finished_at ?: $item->admin_completed_at)
                ->filter()
                ->map(fn ($day) => $day instanceof Carbon ? $day->timezone('Asia/Yangon')->toDateString() : Carbon::parse((string) $day, 'Asia/Yangon')->toDateString())
                ->sort()
                ->values();
            $returnDates = $itemGroup
                ->filter(fn (DispatchOrderItem $item) => $this->isReturnKyoShinItem($item))
                ->map(fn (DispatchOrderItem $item) => $item->admin_updated_at ?: $item->kyoShinItem?->finished_at)
                ->filter()
                ->map(fn ($day) => $day instanceof Carbon ? $day->timezone('Asia/Yangon')->toDateString() : Carbon::parse((string) $day, 'Asia/Yangon')->toDateString())
                ->sort()
                ->values();
            $dueDay = $dueDates->last();
            $overdueDays = 0;
            if ($dueDay) {
                $today = now('Asia/Yangon')->toDateString();
                if ($dueDay < $today) {
                    $overdueDays = (int) Carbon::parse($dueDay, 'Asia/Yangon')->diffInDays(Carbon::parse($today, 'Asia/Yangon'));
                }
            }

            return (object) [
                'id' => $osId,
                'name' => $name,
                'phone' => $client?->contact_number ?? '-',
                'amount' => $totalAdvanced,
                'total_advanced_paid' => $totalAdvanced,
                'advanced_paid' => $advancedPaid,
                'remain' => max(0, $remain),
                'item_count' => $itemGroup->count(),
                'tab_item_count' => $osTabItems->count(),
                'unchecked_count' => $remainItems->count(),
                'pending_returned_count' => $pendingReturnedCount,
                'all_checked' => $allChecked,
                'tab' => $tab,
                'due_finished_at' => $dueDay,
                'created_at' => $createdDates->first(),
                'finished_at' => $finishedDates->last(),
                'return_at' => $returnDates->last(),
                'overdue_days' => $overdueDays,
            ];
        })->sortBy(fn ($row) => mb_strtolower($row->name), SORT_NATURAL)->values();
    }

    /**
     * @return Collection<int, DispatchOrderItem>
     */
    public function itemsForOs(string $scopeKey, string $tab, int $osId, ?string $fromDay, ?string $toDay): Collection
    {
        return $this->itemsForTab($scopeKey, $tab, $fromDay, $toDay)
            ->filter(fn (DispatchOrderItem $item) => (int) ($item->order?->client_id ?? 0) === $osId)
            ->values();
    }

    /**
     * @return Collection<int, DispatchOrderItem>
     */
    public function itemsForTab(string $scopeKey, string $tab, ?string $fromDay, ?string $toDay): Collection
    {
        $this->syncPendingFinished();

        $fromDay = $fromDay ? Carbon::parse($fromDay)->toDateString() : null;
        $toDay = $toDay ? Carbon::parse($toDay)->toDateString() : null;

        return $this->filterItemsByTab(
            $this->allTrackedItems($scopeKey, $fromDay, $toDay),
            $tab
        );
    }

    /**
     * @param  Collection<int, DispatchOrderItem>  $items
     * @return Collection<int, DispatchOrderItem>
     */
    protected function filterItemsByTab(Collection $items, string $tab): Collection
    {
        // Only after that OS is fully Checked (every parcel checked) do rows leave
        // OS List / ပို့ပြီး / Return. Partial Check keeps them visible.
        $items = $this->excludeFullyCheckedOsItems($items);

        if ($tab === self::TAB_ADVANCED_PAID) {
            return $items->filter(fn (DispatchOrderItem $item) => $this->isDeliveredKyoShinItem($item))->values();
        }
        if ($tab === self::TAB_FINISHED) {
            return $items->filter(fn (DispatchOrderItem $item) => $this->isReturnKyoShinItem($item))->values();
        }

        return $items->values();
    }

    /**
     * Drop parcels whose OS has every tracked parcel Checked.
     *
     * @param  Collection<int, DispatchOrderItem>  $items
     * @return Collection<int, DispatchOrderItem>
     */
    protected function excludeFullyCheckedOsItems(Collection $items): Collection
    {
        if ($items->isEmpty()) {
            return $items->values();
        }

        $fullyCheckedOsIds = $items
            ->groupBy(static fn (DispatchOrderItem $item) => (int) ($item->order?->client_id ?? 0))
            ->filter(function (Collection $group) {
                return $group->isNotEmpty()
                    && $group->every(fn (DispatchOrderItem $item) => $this->isRecoveredKyoShinItem($item));
            })
            ->keys()
            ->map(static fn ($id) => (int) $id)
            ->all();

        if ($fullyCheckedOsIds === []) {
            return $items->values();
        }

        return $items
            ->filter(static fn (DispatchOrderItem $item) => ! in_array(
                (int) ($item->order?->client_id ?? 0),
                $fullyCheckedOsIds,
                true
            ))
            ->values();
    }

    protected function isReturnKyoShinItem(DispatchOrderItem $item): bool
    {
        $status = (string) ($item->status ?? '');

        // Still on Admin Return tab, or no-fee cycle completed as Os Returned.
        return in_array($status, ['return', 'os_returned'], true);
    }

    protected function isDeliveredKyoShinItem(DispatchOrderItem $item): bool
    {
        return (string) ($item->status ?? '') === 'completed';
    }

    protected function isClosedKyoShinItem(DispatchOrderItem $item): bool
    {
        return $this->isDeliveredKyoShinItem($item) || $this->isReturnKyoShinItem($item);
    }

    protected function isReceivedKyoShinItem(DispatchOrderItem $item): bool
    {
        return ! empty($item->kyoShinItem?->received_at);
    }

    /**
     * ငွေရှင်းတမ်း ကြိုရှင်းသမား ပေးရန် Finished — money comes back to ပြန်ရငွေ.
     */
    protected function isSettlementFinishedKyoShinItem(DispatchOrderItem $item): bool
    {
        if (! $this->isDeliveredKyoShinItem($item)) {
            return false;
        }

        $kyo = $item->kyoShinItem;
        if (! empty($item->admin_finished_at) || ! empty($kyo?->finished_at)) {
            return true;
        }

        return (string) ($kyo?->status ?? '') === KyoShinItem::STATUS_FINISHED;
    }

    /**
     * Received money waiting in ပြန်ရငွေ until Check.
     * Return / Os Returned ကြိုရှင်း parcels auto-enter ပြန်ရငွေ (no Admin Received step).
     */
    protected function isPendingReturnedMoney(DispatchOrderItem $item): bool
    {
        if ($this->isRecoveredKyoShinItem($item)) {
            return false;
        }

        return $this->isReceivedKyoShinItem($item);
    }

    /**
     * Still with OS — not yet Return/Received, and not Checked into cash.
     */
    protected function isOsReceivableItem(DispatchOrderItem $item): bool
    {
        return ! $this->isRecoveredKyoShinItem($item)
            && ! $this->isPendingReturnedMoney($item);
    }

    /**
     * Checked amounts leave ပြန်ရငွေ and go into လက်ရှိရှိတဲ့ငွေ.
     */
    protected function isRecoveredKyoShinItem(DispatchOrderItem $item): bool
    {
        return $this->recoveredYangonDate($item) !== null;
    }

    protected function recoveredYangonDate(DispatchOrderItem $item): ?string
    {
        $checked = $item->kyoShinItem?->checked_at;
        if (empty($checked)) {
            return null;
        }

        $checkedAt = $checked instanceof Carbon ? $checked : Carbon::parse((string) $checked);

        return $checkedAt->timezone('Asia/Yangon')->toDateString();
    }

    /**
     * @param  list<int>  $osIds
     */
    public function markChecked(string $scopeKey, string $tab, array $osIds, User $actor): int
    {
        if (! $this->tablesReady() || ! Schema::hasColumn('kyo_shin_items', 'checked_at')) {
            return 0;
        }

        $osIds = array_values(array_unique(array_filter(array_map('intval', $osIds))));
        if ($osIds === [] || ! in_array($tab, [self::TAB_ADVANCED_PAID, self::TAB_FINISHED], true)) {
            return 0;
        }

        $now = now('Asia/Yangon');
        $updated = 0;

        foreach ($osIds as $osId) {
            $items = $this->itemsForOs($scopeKey, $tab, $osId, null, null);
            foreach ($items as $item) {
                $row = $item->kyoShinItem;
                if (! $row || ! empty($row->checked_at) || ! $this->isPendingReturnedMoney($item)) {
                    continue;
                }
                $row->checked_at = $now;
                $row->checked_by = $actor->id;
                $row->save();
                $updated++;
            }
        }

        if ($updated > 0) {
            $this->recordDailyLedger($scopeKey);
        }

        return $updated;
    }

    /**
     * @param  list<int>  $itemIds
     */
    public function markItemsReceived(string $scopeKey, string $tab, int $osId, array $itemIds, User $actor, ?string $fromDay = null, ?string $toDay = null): int
    {
        if (! $this->tablesReady() || $osId <= 0) {
            return 0;
        }

        $itemIds = array_values(array_unique(array_filter(array_map('intval', $itemIds))));
        if ($itemIds === [] || ! in_array($tab, [self::TAB_OS_LIST, self::TAB_ADVANCED_PAID, self::TAB_FINISHED], true)) {
            return 0;
        }

        $items = $this->itemsForOs($scopeKey, $tab, $osId, $fromDay, $toDay)
            ->filter(fn (DispatchOrderItem $item) => in_array((int) $item->id, $itemIds, true))
            ->values();
        $now = now('Asia/Yangon');
        $updated = 0;

        $canReceive = Schema::hasColumn('kyo_shin_items', 'received_at');
        foreach ($items as $item) {
            $row = $item->kyoShinItem;
            if (! $row || ! empty($row->checked_at)) {
                continue;
            }
            if ($canReceive) {
                if (! empty($row->received_at)) {
                    continue;
                }
                $row->received_at = $now;
                $row->received_by = $actor->id;
            } else {
                $row->checked_at = $now;
                $row->checked_by = $actor->id;
            }
            $row->save();
            $updated++;
        }

        if ($updated > 0) {
            $this->recordDailyLedger($scopeKey);
        }

        return $updated;
    }

    /**
     * @param  Collection<int, DispatchOrderItem>  $items
     * @param  array{
     *     payment_method?: string,
     *     kpay_name?: string,
     *     kpay_no?: string,
     *     slip?: UploadedFile|null,
     *     slips?: list<UploadedFile>,
     *     order_id?: int|null
     * }  $payment
     */
    public function giveAdvance(Collection $items, string $dueDay, User $actor, array $payment = []): int
    {
        if (! $this->tablesReady()) {
            return 0;
        }

        $dueDay = Carbon::parse($dueDay, 'Asia/Yangon')->toDateString();
        $method = in_array(($payment['payment_method'] ?? 'kpay'), ['kpay', 'cash'], true)
            ? (string) $payment['payment_method']
            : 'kpay';
        $eligible = collect();

        foreach ($items as $item) {
            if (! $item instanceof DispatchOrderItem) {
                continue;
            }
            if (KyoShinItem::query()->where('dispatch_order_item_id', $item->id)->exists()) {
                continue;
            }
            if ((int) ($item->to_branch_id ?? 0) <= 0) {
                continue;
            }
            $eligible->push($item);
        }

        if ($eligible->isEmpty()) {
            return 0;
        }

        $osId = (int) ($eligible->first()->order?->client_id ?? 0);
        $amount = (float) $eligible->sum(fn (DispatchOrderItem $item) => $this->itemAmount($item));
        $itemIds = $eligible->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        $slipFiles = $this->normalizeSlipFiles($payment);
        $slipPaths = $this->storeGiveSlips($osId, $slipFiles);
        $slipPath = $slipPaths[0] ?? null;

        $batch = null;
        if (Schema::hasTable('kyo_shin_batches')) {
            $batchPayload = [
                'os_user_id' => $osId,
                'order_id' => (int) ($payment['order_id'] ?? $eligible->first()->order_id ?? 0) ?: null,
                'payment_method' => $method,
                'kpay_name' => $method === 'kpay' ? trim((string) ($payment['kpay_name'] ?? '')) : null,
                'kpay_no' => $method === 'kpay' ? trim((string) ($payment['kpay_no'] ?? '')) : null,
                'slip_photo_path' => $slipPath,
            ];
            if (Schema::hasColumn('kyo_shin_batches', 'slip_photo_paths')) {
                $batchPayload['slip_photo_paths'] = $slipPaths;
            }
            $batch = KyoShinBatch::query()->create($batchPayload + [
                'due_finished_at' => $dueDay,
                'amount' => $amount,
                'item_ids' => $itemIds,
                'created_by' => $actor->id,
            ]);
            $this->writeSlipTable($batch, $eligible, $dueDay);
            if ($method === 'cash' && Schema::hasTable('os_cash_payouts')) {
                $today = now('Asia/Yangon')->toDateString();
                $payoutPayload = [
                    'os_user_id' => $osId,
                    'branch_id' => (int) ($eligible->first()->to_branch_id ?? 0) ?: null,
                    'period_from' => $today,
                    'period_to' => $today,
                    'amount' => $amount,
                    'slip_photo_path' => $slipPath,
                    'status' => OsCashPayout::STATUS_UNASSIGNED,
                    'created_by' => $actor->id,
                ];
                if (! Schema::hasColumn('os_cash_payouts', 'branch_id')) {
                    unset($payoutPayload['branch_id']);
                }
                if (Schema::hasColumn('os_cash_payouts', 'kyo_shin_batch_id')) {
                    $payoutPayload['kyo_shin_batch_id'] = $batch->id;
                }
                $payout = OsCashPayout::query()->create($payoutPayload);
                $batch->update(['cash_payout_id' => $payout->id]);
            }
            $this->notifyOsGiven($batch, $eligible);
        }

        $count = 0;
        foreach ($eligible as $item) {
            $branchId = (int) ($item->to_branch_id ?? 0);
            $alreadyFinished = ! empty($item->admin_finished_at);
            $finishedAt = $alreadyFinished
                ? ($item->admin_finished_at instanceof Carbon
                    ? $item->admin_finished_at
                    : Carbon::parse((string) $item->admin_finished_at))
                : null;

            KyoShinItem::query()->create([
                'batch_id' => $batch?->id,
                'dispatch_order_item_id' => $item->id,
                'scope_key' => $this->scopeKeyForBranch($branchId),
                'branch_id' => $branchId,
                'os_user_id' => (int) ($item->order?->client_id ?? 0),
                'amount' => $this->itemAmount($item),
                'payment_method' => $method,
                'status' => $alreadyFinished ? KyoShinItem::STATUS_FINISHED : KyoShinItem::STATUS_ADVANCED_PAID,
                'advanced_paid_at' => now('Asia/Yangon'),
                'advanced_paid_by' => $actor->id,
                'due_finished_at' => $dueDay,
                'finished_at' => $finishedAt,
                'finished_by' => $alreadyFinished ? $actor->id : null,
            ]);
            $count++;
        }

        if ($count > 0) {
            $eligible
                ->map(fn (DispatchOrderItem $item) => $this->scopeKeyForBranch((int) ($item->to_branch_id ?? 0)))
                ->unique()
                ->each(fn (string $scopeKey) => $this->recordDailyLedger($scopeKey));
        }

        return $count;
    }

    /**
     * @param  array<string, mixed>  $payment
     * @return list<UploadedFile>
     */
    protected function normalizeSlipFiles(array $payment): array
    {
        $files = [];
        $slips = $payment['slips'] ?? [];
        if ($slips instanceof UploadedFile) {
            $slips = [$slips];
        }
        if (is_array($slips)) {
            foreach ($slips as $file) {
                if ($file instanceof UploadedFile) {
                    $files[] = $file;
                }
            }
        }
        if ($files === [] && ($payment['slip'] ?? null) instanceof UploadedFile) {
            $files[] = $payment['slip'];
        }

        return $files;
    }

    /**
     * @param  list<UploadedFile>  $files
     * @return list<string>
     */
    protected function storeGiveSlips(int $osId, array $files): array
    {
        $paths = [];
        foreach ($files as $file) {
            $path = $this->storeGiveSlip($osId, $file);
            if ($path) {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    protected function storeGiveSlip(int $osId, $file): ?string
    {
        if (! $file instanceof UploadedFile) {
            return null;
        }

        $dir = 'kyo-shin/os-'.$osId.'/'.now('Asia/Yangon')->format('Ymd');
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            $ext = 'jpg';
        }

        return $file->storeAs($dir, 'slip-'.Str::random(8).'.'.$ext, 'public');
    }

    protected function writeSlipTable(KyoShinBatch $batch, Collection $items, string $dueDay): void
    {
        $settlement = app(OsSettlementService::class);
        $invoiceDate = now('Asia/Yangon')->format('d-m-Y');
        $slipData = $settlement->buildSlipRows($items, $invoiceDate);
        foreach ($slipData['rows'] as $i => $row) {
            $slipData['rows'][$i]['is_kyo_shin'] = true;
        }

        $osClient = User::query()->find((int) $batch->os_user_id);
        $osName = trim((string) ($osClient?->name ?? '')) ?: ('#'.$batch->os_user_id);
        $slipSender = $osClient
            ? $settlement->resolveSlipSender($osClient, $items, $osName)
            : ['name' => $osName, 'phone' => '-', 'address' => '-'];

        $dir = 'kyo-shin/batch-'.$batch->id;
        Storage::disk('public')->makeDirectory($dir);
        $tablePath = $dir.'/slip-table.html';
        Storage::disk('public')->put($tablePath, view('order.os-settlement-slip-table', [
            'slipCompany' => $this->slipCompany(),
            'slipSender' => $slipSender,
            'slipRows' => $slipData['rows'],
            'slipTotals' => $slipData['totals'],
            'slipInvoiceDate' => $invoiceDate,
            'kpayImageUrl' => $batch->slipPhotoUrl(),
            'kpayImageUrls' => $batch->slipPhotoUrls(),
            'slipIsKyoShin' => true,
            'slipDueDate' => Carbon::parse($dueDay)->format('d-m-Y'),
            'standalone' => true,
        ])->render());

        $batch->update(['slip_table_path' => $tablePath]);
    }

    protected function writeReturnSlipTable(KyoShinBatch $batch, Collection $items, string $dueDay): string
    {
        $settlement = app(OsSettlementService::class);
        $invoiceDate = now('Asia/Yangon')->format('d-m-Y');
        $slipData = $settlement->buildSlipRows($items, $invoiceDate);
        foreach ($slipData['rows'] as $i => $row) {
            $slipData['rows'][$i]['is_kyo_shin'] = true;
        }

        $osClient = User::query()->find((int) $batch->os_user_id);
        $osName = trim((string) ($osClient?->name ?? '')) ?: ('#'.$batch->os_user_id);
        $slipSender = $osClient
            ? $settlement->resolveSlipSender($osClient, $items, $osName)
            : ['name' => $osName, 'phone' => '-', 'address' => '-'];

        $dir = 'kyo-shin/batch-'.$batch->id;
        Storage::disk('public')->makeDirectory($dir);
        $tablePath = $dir.'/slip-table-return-'.Str::random(8).'.html';
        Storage::disk('public')->put($tablePath, view('order.os-settlement-slip-table', [
            'slipCompany' => $this->slipCompany(),
            'slipSender' => $slipSender,
            'slipRows' => $slipData['rows'],
            'slipTotals' => $slipData['totals'],
            'slipInvoiceDate' => $invoiceDate,
            'kpayImageUrl' => $batch->slipPhotoUrl(),
            'kpayImageUrls' => $batch->slipPhotoUrls(),
            'slipIsKyoShin' => true,
            'slipDueDate' => Carbon::parse($dueDay)->format('d-m-Y'),
            'standalone' => true,
        ])->render());

        return Storage::disk('public')->url($tablePath);
    }

    public function notifyOsReturn(string $scopeKey, int $osId, ?string $fromDay, ?string $toDay, array $itemIds = []): int
    {
        if (! $this->tablesReady() || $osId <= 0) {
            return 0;
        }

        $items = $this->itemsForOs($scopeKey, self::TAB_FINISHED, $osId, $fromDay, $toDay);
        $itemIds = array_values(array_unique(array_filter(array_map('intval', $itemIds))));
        if ($itemIds !== []) {
            $items = $items
                ->filter(fn (DispatchOrderItem $item) => in_array((int) $item->id, $itemIds, true))
                ->values();
        }
        if ($items->isEmpty()) {
            return 0;
        }

        $os = User::query()->find($osId);
        if (! $os) {
            return 0;
        }

        $items->each(fn (DispatchOrderItem $item) => $item->loadMissing('kyoShinItem.batch'));
        $batches = $items
            ->map(fn (DispatchOrderItem $item) => $item->kyoShinItem?->batch)
            ->filter()
            ->unique(fn (KyoShinBatch $batch) => (int) $batch->id)
            ->values();

        if ($batches->isEmpty()) {
            return 0;
        }

        $amount = (float) $items->sum(fn (DispatchOrderItem $item) => $this->itemAmount($item));
        $orderId = (int) ($items->first()?->order_id ?? 0);

        $batch = $batches->sortByDesc(fn (KyoShinBatch $row) => (int) $row->id)->first();
        $dueDay = $batch->due_finished_at
            ? Carbon::parse($batch->due_finished_at)->toDateString()
            : now('Asia/Yangon')->toDateString();
        $slipTableUrl = $this->writeReturnSlipTable($batch, $items, $dueDay);

        app(AppPushService::class)->notifyUser(
            $os,
            AppPushService::TYPE_KYO_SHIN_GIVEN,
            __('message.kyo_shin_return_notification_title'),
            __('message.kyo_shin_return_notification_body', [
                'count' => $items->count(),
                'amount' => number_format($amount),
            ]),
            [
                'id' => 'KYO_SHIN_RETURN_'.$batch->id.'_'.implode('-', $items->pluck('id')->all()),
                'kyo_shin_batch_id' => (string) $batch->id,
                'settlement_id' => (string) $batch->id,
                'order_id' => (string) $orderId,
                'category' => 'kyo_shin',
                'slip_table_url' => $slipTableUrl,
                'kpay_image_url' => $batch->slipPhotoUrl(),
                'kpay_image_urls' => $batch->slipPhotoUrls(),
                'slip_photo_urls' => $batch->slipPhotoUrls(),
                'slip_image_url' => $batch->slipPhotoUrl(),
                'amount' => (string) $amount,
                'from_date' => now('Asia/Yangon')->format('d-m-Y'),
                'to_date' => optional($batch->due_finished_at)->format('d-m-Y'),
                'delivery_format' => 'table',
                'app' => 'os',
            ]
        );

        return $items->count();
    }

    protected function notifyOsGiven(KyoShinBatch $batch, Collection $items): void
    {
        $os = User::query()->find((int) $batch->os_user_id);
        if (! $os) {
            return;
        }

        $orderId = (int) ($batch->order_id ?? $items->first()?->order_id ?? 0);
        app(AppPushService::class)->notifyUser(
            $os,
            AppPushService::TYPE_KYO_SHIN_GIVEN,
            __('message.kyo_shin_notification_title'),
            __('message.kyo_shin_notification_body', [
                'count' => $items->count(),
                'amount' => number_format((float) $batch->amount),
            ]),
            [
                'id' => 'KYO_SHIN_'.$batch->id,
                'kyo_shin_batch_id' => (string) $batch->id,
                'settlement_id' => (string) $batch->id,
                'order_id' => (string) $orderId,
                'category' => 'kyo_shin',
                'slip_table_url' => $batch->slipTableUrl(),
                'kpay_image_url' => $batch->slipPhotoUrl(),
                'kpay_image_urls' => $batch->slipPhotoUrls(),
                'slip_photo_urls' => $batch->slipPhotoUrls(),
                'slip_image_url' => $batch->slipPhotoUrl(),
                'amount' => (string) $batch->amount,
                'from_date' => now('Asia/Yangon')->format('d-m-Y'),
                'to_date' => optional($batch->due_finished_at)->format('d-m-Y'),
                'delivery_format' => 'table',
                'app' => 'os',
            ]
        );
    }

    protected function slipCompany(): array
    {
        return [
            'name' => config('app.name', 'Point Delivery'),
            'address' => '',
            'phone' => '',
            'email' => '',
        ];
    }

    public function markAdvancedPaid(string $scopeKey, Collection $items, User $actor): int
    {
        return $this->giveAdvance($items, now('Asia/Yangon')->toDateString(), $actor);
    }

    public function markFinished(string $scopeKey, Collection $items, User $actor): int
    {
        $ids = $items->map(fn ($item) => (int) $item->id)->filter()->all();

        return $this->syncFinishedForItemIds($ids, $actor->id);
    }

    public function updateOsDueFinishedAt(string $scopeKey, int $osId, string $dueDay): int
    {
        if (! $this->tablesReady() || ! Schema::hasColumn('kyo_shin_items', 'due_finished_at')) {
            return 0;
        }

        $ids = $this->itemsForOs($scopeKey, self::TAB_OS_LIST, $osId, null, null)
            ->map(fn (DispatchOrderItem $item) => (int) ($item->kyoShinItem?->id ?? 0))
            ->filter()
            ->unique()
            ->values()
            ->all();
        if ($ids === []) {
            return 0;
        }

        $dueDay = Carbon::parse($dueDay, 'Asia/Yangon')->toDateString();

        return KyoShinItem::query()
            ->whereIn('id', $ids)
            ->update([
                'due_finished_at' => $dueDay,
                'last_overdue_notified_on' => null,
                'updated_at' => now(),
            ]);
    }

    public function updateItemDueFinishedAt(int $dispatchItemId, string $dueDay, User $actor): int
    {
        if (! $this->tablesReady() || ! Schema::hasColumn('kyo_shin_items', 'due_finished_at')) {
            return 0;
        }

        $row = KyoShinItem::query()
            ->where('dispatch_order_item_id', $dispatchItemId)
            ->first();
        if (! $row || ! $this->actorCanAccess($actor, (string) $row->scope_key)) {
            return 0;
        }

        $row->due_finished_at = Carbon::parse($dueDay, 'Asia/Yangon')->toDateString();
        $row->last_overdue_notified_on = null;
        $row->save();

        return 1;
    }

    /**
     * @param  list<int>  $itemIds
     */
    public function syncFinishedForItemIds(array $itemIds, ?int $actorId = null): int
    {
        if (! $this->tablesReady()) {
            return 0;
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $itemIds))));
        if ($ids === []) {
            return 0;
        }

        $now = now('Asia/Yangon');

        $updated = KyoShinItem::query()
            ->where('status', KyoShinItem::STATUS_ADVANCED_PAID)
            ->whereIn('dispatch_order_item_id', $ids)
            ->update([
                'status' => KyoShinItem::STATUS_FINISHED,
                'finished_at' => $now,
                'finished_by' => $actorId,
                'updated_at' => $now,
            ]);

        $this->markReceivedForDispatchItemIds($ids, $now, $actorId);

        return $updated;
    }

    /**
     * @param  list<int>  $dispatchItemIds
     */
    protected function markReceivedForDispatchItemIds(array $dispatchItemIds, $at, ?int $actorId = null): int
    {
        if (! $this->tablesReady() || ! Schema::hasColumn('kyo_shin_items', 'received_at')) {
            return 0;
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $dispatchItemIds))));
        if ($ids === []) {
            return 0;
        }

        $payload = [
            'received_at' => $at,
            'updated_at' => now(),
        ];
        if ($actorId && Schema::hasColumn('kyo_shin_items', 'received_by')) {
            $payload['received_by'] = $actorId;
        }

        return KyoShinItem::query()
            ->whereIn('dispatch_order_item_id', $ids)
            ->whereNull('received_at')
            ->whereNull('checked_at')
            ->whereHas('dispatchItem', function ($q) {
                $q->whereIn('status', ['completed', 'return', 'os_returned']);
            })
            ->update($payload);
    }

    /**
     * Rider/Admin Os Returned (or Return) → ပြန်ရငွေ immediately.
     *
     * @param  list<int>  $dispatchItemIds
     */
    public function markReturnMoneyReceivedForItemIds(array $dispatchItemIds, ?int $actorId = null): int
    {
        if (! $this->tablesReady() || ! Schema::hasColumn('kyo_shin_items', 'received_at')) {
            return 0;
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $dispatchItemIds))));
        if ($ids === []) {
            return 0;
        }

        $at = now('Asia/Yangon');
        $payload = [
            'received_at' => $at,
            'updated_at' => now(),
        ];
        if ($actorId && Schema::hasColumn('kyo_shin_items', 'received_by')) {
            $payload['received_by'] = $actorId;
        }

        // Ensure kyo_shin row is Finished when parcel returns.
        KyoShinItem::query()
            ->whereIn('dispatch_order_item_id', $ids)
            ->where('status', KyoShinItem::STATUS_ADVANCED_PAID)
            ->update([
                'status' => KyoShinItem::STATUS_FINISHED,
                'finished_at' => $at,
                'updated_at' => now(),
            ]);

        $updated = KyoShinItem::query()
            ->whereIn('dispatch_order_item_id', $ids)
            ->whereNull('received_at')
            ->whereNull('checked_at')
            ->whereHas('dispatchItem', function ($q) {
                $q->whereIn('status', ['return', 'os_returned']);
            })
            ->update($payload);

        return $updated;
    }

    /**
     * Parcel left Return (re-assigned) — pull amount back out of ပြန်ရငွေ.
     *
     * @param  list<int>  $dispatchItemIds
     */
    public function clearReturnMoneyReceivedForItemIds(array $dispatchItemIds): int
    {
        if (! $this->tablesReady() || ! Schema::hasColumn('kyo_shin_items', 'received_at')) {
            return 0;
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $dispatchItemIds))));
        if ($ids === []) {
            return 0;
        }

        $payload = [
            'received_at' => null,
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('kyo_shin_items', 'received_by')) {
            $payload['received_by'] = null;
        }

        return KyoShinItem::query()
            ->whereIn('dispatch_order_item_id', $ids)
            ->whereNotNull('received_at')
            ->whereNull('checked_at')
            ->update($payload);
    }

    /**
     * Completed + ငွေရှင်းတမ်း Finished parcels sit in ပြန်ရငွေ.
     */
    protected function syncReceivedForFinishedItems(): int
    {
        if (! $this->tablesReady() || ! Schema::hasColumn('kyo_shin_items', 'received_at')) {
            return 0;
        }

        $ids = KyoShinItem::query()
            ->whereNull('received_at')
            ->whereNull('checked_at')
            ->whereHas('dispatchItem', function ($q) {
                $q->where('status', 'completed')
                    ->whereNotNull('admin_finished_at');
            })
            ->pluck('dispatch_order_item_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return $this->markReceivedForDispatchItemIds($ids, now('Asia/Yangon'));
    }

    /**
     * Return / Os Returned ကြိုရှင်း → ပြန်ရငွေ (no Admin Received click).
     */
    protected function syncReceivedForReturnItems(): int
    {
        if (! $this->tablesReady() || ! Schema::hasColumn('kyo_shin_items', 'received_at')) {
            return 0;
        }

        $ids = KyoShinItem::query()
            ->whereNull('received_at')
            ->whereNull('checked_at')
            ->whereHas('dispatchItem', function ($q) {
                $q->whereIn('status', ['return', 'os_returned']);
            })
            ->pluck('dispatch_order_item_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return $this->markReturnMoneyReceivedForItemIds($ids);
    }

    public function syncPendingFinished(): int
    {
        $updated = 0;

        if ($this->tablesReady() && Schema::hasColumn('dispatch_order_items', 'admin_finished_at')) {
            $ids = KyoShinItem::query()
                ->where('status', KyoShinItem::STATUS_ADVANCED_PAID)
                ->whereHas('dispatchItem', function ($q) {
                    $q->where(function ($inner) {
                        $inner->whereNotNull('admin_finished_at')
                            ->orWhereIn('status', ['return', 'os_returned']);
                    });
                })
                ->pluck('dispatch_order_item_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if ($ids !== []) {
                $now = now('Asia/Yangon');
                $rows = KyoShinItem::query()
                    ->where('status', KyoShinItem::STATUS_ADVANCED_PAID)
                    ->whereIn('dispatch_order_item_id', $ids)
                    ->with('dispatchItem')
                    ->get();

                foreach ($rows as $row) {
                    $finishedAt = $row->dispatchItem?->admin_finished_at ?: $now;
                    $row->status = KyoShinItem::STATUS_FINISHED;
                    $row->finished_at = $finishedAt;
                    $itemStatus = (string) ($row->dispatchItem?->status ?? '');
                    $isReturn = in_array($itemStatus, ['return', 'os_returned'], true);
                    if (
                        Schema::hasColumn('kyo_shin_items', 'received_at')
                        && empty($row->received_at)
                        && ($isReturn || ! empty($row->dispatchItem?->admin_finished_at))
                    ) {
                        $row->received_at = $finishedAt;
                    }
                    $row->save();
                    $updated++;
                }
            }
        }

        $updated += $this->syncReceivedForFinishedItems();
        $updated += $this->syncReceivedForReturnItems();

        return $updated;
    }

    public function notifyOverdue(): int
    {
        if (! $this->tablesReady() || ! Schema::hasColumn('kyo_shin_items', 'due_finished_at')) {
            return 0;
        }

        $today = now('Asia/Yangon')->toDateString();
        $rows = KyoShinItem::query()
            ->where('status', KyoShinItem::STATUS_ADVANCED_PAID)
            ->whereNotNull('due_finished_at')
            ->whereDate('due_finished_at', '<', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('last_overdue_notified_on')
                    ->orWhereDate('last_overdue_notified_on', '<', $today);
            })
            ->with(['dispatchItem.order.client', 'osUser'])
            ->get();

        if ($rows->isEmpty()) {
            return 0;
        }

        $push = app(AppPushService::class);
        $admins = $this->overdueAdminRecipients();
        $sent = 0;

        foreach ($rows as $row) {
            $due = $row->due_finished_at instanceof Carbon
                ? $row->due_finished_at->timezone('Asia/Yangon')->toDateString()
                : Carbon::parse((string) $row->due_finished_at, 'Asia/Yangon')->toDateString();
            $days = max(1, (int) Carbon::parse($due, 'Asia/Yangon')->diffInDays(Carbon::parse($today, 'Asia/Yangon')));
            $code = (string) ($row->dispatchItem?->code ?: ('#'.$row->dispatch_order_item_id));
            $subject = __('message.kyo_shin_overdue_subject');
            $message = __('message.kyo_shin_overdue_body', [
                'code' => $code,
                'days' => $days,
                'date' => $this->formatDay($due),
            ]);
            $extra = [
                'id' => 'KYO_SHIN_'.$row->dispatch_order_item_id,
                'item_id' => (int) $row->dispatch_order_item_id,
                'order_id' => (int) ($row->dispatchItem?->order_id ?? 0),
                'days' => $days,
            ];

            $os = $row->osUser ?: $row->dispatchItem?->order?->client;
            if ($os instanceof User) {
                $push->notifyUser($os, AppPushService::TYPE_KYO_SHIN_OVERDUE, $subject, $message, $extra);
                $sent++;
            }

            $branchId = (int) ($row->branch_id ?: $row->dispatchItem?->to_branch_id ?: 0);
            foreach ($admins as $admin) {
                if (! $this->adminShouldReceiveOverdue($admin, $branchId)) {
                    continue;
                }
                $push->notifyUser($admin, AppPushService::TYPE_KYO_SHIN_OVERDUE, $subject, $message, $extra);
                $sent++;
            }

            $row->last_overdue_notified_on = $today;
            $row->save();
        }

        return $sent;
    }

    public function parseDay(?string $raw, ?string $fallback = null): string
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            $raw = $fallback ?: yangonSettlementDefaultDate('Y-m-d');
        }

        try {
            if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $raw)) {
                return Carbon::createFromFormat('d-m-Y', $raw, 'Asia/Yangon')->toDateString();
            }

            return Carbon::parse($raw, 'Asia/Yangon')->timezone('Asia/Yangon')->toDateString();
        } catch (\Throwable $e) {
            return Carbon::parse($fallback ?: yangonSettlementDefaultDate('Y-m-d'), 'Asia/Yangon')->toDateString();
        }
    }

    public function formatDay(string $day): string
    {
        return Carbon::parse($day, 'Asia/Yangon')->format('d-m-Y');
    }

    protected function yangonDay(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $carbon = $value instanceof Carbon
            ? $value->copy()
            : Carbon::parse((string) $value);

        return $carbon->timezone('Asia/Yangon')->toDateString();
    }

    /**
     * Every ကြိုရှင်း parcel for the branch, including Finished / Return.
     *
     * @return Collection<int, DispatchOrderItem>
     */
    protected function allTrackedItems(string $scopeKey, ?string $fromDay, ?string $toDay): Collection
    {
        if (! $this->tablesReady()) {
            return collect();
        }

        $itemIds = KyoShinItem::query()
            ->where('scope_key', $scopeKey)
            ->whereIn('status', [KyoShinItem::STATUS_ADVANCED_PAID, KyoShinItem::STATUS_FINISHED])
            ->get(['dispatch_order_item_id', 'advanced_paid_at'])
            ->filter(function (KyoShinItem $row) use ($fromDay, $toDay) {
                if ($fromDay === null && $toDay === null) {
                    return true;
                }
                $ts = $row->advanced_paid_at;
                if (! $ts) {
                    return false;
                }
                $day = $ts instanceof Carbon
                    ? $ts->copy()->timezone('Asia/Yangon')->toDateString()
                    : Carbon::parse((string) $ts, config('app.timezone') ?: 'UTC')
                        ->timezone('Asia/Yangon')
                        ->toDateString();

                if ($fromDay !== null && $day < $fromDay) {
                    return false;
                }
                if ($toDay !== null && $day > $toDay) {
                    return false;
                }

                return true;
            })
            ->pluck('dispatch_order_item_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($itemIds === []) {
            return collect();
        }

        return DispatchOrderItem::query()
            ->with(['order.client.city', 'fromBranch', 'toBranch', 'kyoShinItem.batch'])
            ->whereIn('id', $itemIds)
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    protected function overdueAdminRecipients(): Collection
    {
        return User::query()
            ->where(function ($query) {
                $query->whereIn('user_type', ['admin', 'super_admin', 'demo_admin']);
            })
            ->get()
            ->unique('id')
            ->values();
    }

    protected function adminShouldReceiveOverdue(User $admin, int $branchId): bool
    {
        if (isSuperAdmin($admin) || canAccessAllBranches($admin)) {
            return true;
        }

        $adminBranch = (int) ($admin->branch_id ?? 0);
        if ($branchId > 0 && $adminBranch > 0) {
            return $adminBranch === $branchId;
        }

        return isAdminPanelUser($admin);
    }
}
