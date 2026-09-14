<?php

namespace App\Services;

use App\Models\DispatchOrderItem;
use App\Models\KyoShinCap;
use App\Models\KyoShinItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class KyoShinService
{
    public const SCOPE_MDY = 'mdy';
    public const SCOPE_YGN_NLS = 'ygn_nls';
    public const SCOPE_YGN_M2M = 'ygn_m2m';

    public const TAB_OS_LIST = 'os_list';
    public const TAB_ADVANCED_PAID = 'advanced_paid';
    public const TAB_FINISHED = 'finished';

    public function __construct(
        protected DispatchHubService $hubs
    ) {}

    public function tablesReady(): bool
    {
        return Schema::hasTable('kyo_shin_caps') && Schema::hasTable('kyo_shin_items');
    }

    /**
     * @return list<array{key: string, label: string, type: string, branch_id: int|null, hub_id: int|null}>
     */
    public function scopes(): array
    {
        $mdyId = $this->hubs->mandalayBranchId();
        $scopes = [
            self::SCOPE_MDY => [
                'key' => self::SCOPE_MDY,
                'label' => 'MDY Branch',
                'type' => 'branch',
                'branch_id' => $mdyId,
                'hub_id' => null,
            ],
        ];

        foreach ($this->hubs->accounts() as $hub) {
            $key = $this->scopeKeyForHub($hub);
            if (! $key || isset($scopes[$key])) {
                continue;
            }
            $scopes[$key] = [
                'key' => $key,
                'label' => $this->labelForHub($hub, $key),
                'type' => 'hub',
                'branch_id' => (int) ($hub->branch_id ?? 0) ?: $this->hubs->yangonBranchId(),
                'hub_id' => (int) $hub->id,
            ];
        }

        foreach ([self::SCOPE_YGN_NLS => 'YGN Ngwe Latt Saung', self::SCOPE_YGN_M2M => 'YGN M2M'] as $key => $label) {
            if (! isset($scopes[$key])) {
                $scopes[$key] = [
                    'key' => $key,
                    'label' => $label,
                    'type' => 'hub',
                    'branch_id' => $this->hubs->yangonBranchId(),
                    'hub_id' => null,
                ];
            }
        }

        return array_values($scopes);
    }

    public function scopeByKey(string $key): ?array
    {
        foreach ($this->scopes() as $scope) {
            if ($scope['key'] === $key) {
                return $scope;
            }
        }

        return null;
    }

    public function actorCanAccess(?User $user, string $scopeKey): bool
    {
        if (! $user) {
            return false;
        }

        $allowed = $this->allowedScopeKeys($user);

        return in_array($scopeKey, $allowed, true);
    }

    /**
     * @return list<string>
     */
    public function allowedScopeKeys(?User $user = null): array
    {
        $user = $user ?? auth()->user();
        if (! $user) {
            return [];
        }

        if (isSuperAdmin($user) || canAccessAllBranches($user)) {
            return array_column($this->scopes(), 'key');
        }

        if ($this->hubs->isHub($user)) {
            $key = $this->scopeKeyForHub($user);

            return $key ? [$key] : [];
        }

        $forced = forcedBranchId($user);
        $mdyId = $this->hubs->mandalayBranchId();
        if ($forced && $mdyId && (int) $forced === (int) $mdyId) {
            return [self::SCOPE_MDY];
        }

        return [];
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

        return $cap;
    }

    public function totalFor(string $scopeKey): float
    {
        if (! $this->tablesReady()) {
            return 0.0;
        }

        return (float) (KyoShinCap::query()->where('scope_key', $scopeKey)->value('total_amount') ?? 0);
    }

    public function advancedPaidFor(string $scopeKey): float
    {
        if (! $this->tablesReady()) {
            return 0.0;
        }

        return (float) KyoShinItem::query()
            ->where('scope_key', $scopeKey)
            ->whereIn('status', [KyoShinItem::STATUS_ADVANCED_PAID, KyoShinItem::STATUS_FINISHED])
            ->sum('amount');
    }

    /**
     * @return array{total: float, advanced_paid: float, remain: float, thein_total: float, thein_advanced: float, thein_remain: float}
     */
    public function summary(string $scopeKey): array
    {
        $total = $this->totalFor($scopeKey);
        $advanced = $this->advancedPaidFor($scopeKey);
        $remain = $total - $advanced;

        return [
            'total' => $total,
            'advanced_paid' => $advanced,
            'remain' => $remain,
            'thein_total' => $this->toThein($total),
            'thein_advanced' => $this->toThein($advanced),
            'thein_remain' => $this->toThein($remain),
        ];
    }

    /**
     * @return list<array{key: string, label: string, total: float, advanced_paid: float, remain: float}>
     */
    public function summaries(): array
    {
        $rows = [];
        foreach ($this->scopes() as $scope) {
            $sum = $this->summary($scope['key']);
            $rows[] = [
                'key' => $scope['key'],
                'label' => $scope['label'],
                'total' => $sum['total'],
                'advanced_paid' => $sum['advanced_paid'],
                'remain' => $sum['remain'],
                'thein_total' => $sum['thein_total'],
                'thein_advanced' => $sum['thein_advanced'],
                'thein_remain' => $sum['thein_remain'],
            ];
        }

        return $rows;
    }

    public function toThein(float $ks): float
    {
        return round($ks / 100000, 2);
    }

    public function itemAmount(DispatchOrderItem $item): float
    {
        return round(abs((float) $item->displayOsToPay()), 2);
    }

    /**
     * @return Collection<int, object>
     */
    public function osRows(string $scopeKey, string $tab, string $fromDay, string $toDay): Collection
    {
        $items = $this->itemsForTab($scopeKey, $tab, $fromDay, $toDay);
        $grouped = $items->groupBy(static fn (DispatchOrderItem $item) => (int) ($item->order?->client_id ?? 0));
        $clients = User::query()
            ->whereIn('id', $grouped->keys()->filter(fn ($id) => (int) $id > 0)->all() ?: [0])
            ->with('city')
            ->get()
            ->keyBy('id');

        return $grouped->map(function ($itemGroup, $osId) use ($clients, $tab) {
            $osId = (int) $osId;
            $client = $osId > 0 ? $clients->get($osId) : null;
            $name = $osId > 0
                ? (trim((string) ($client?->name ?? '')) !== '' ? trim((string) $client->name) : ('#'.$osId))
                : __('message.no_os');
            $cityName = trim((string) ($client?->city?->name ?? ''));
            if ($cityName !== '') {
                $name .= ' ('.$cityName.')';
            }

            $amount = (float) $itemGroup->sum(fn (DispatchOrderItem $item) => $this->itemAmount($item));

            return (object) [
                'id' => $osId,
                'name' => $name,
                'phone' => $client?->contact_number ?? '-',
                'amount' => $amount,
                'item_count' => $itemGroup->count(),
                'tab' => $tab,
            ];
        })->sortBy(fn ($row) => mb_strtolower($row->name), SORT_NATURAL)->values();
    }

    /**
     * @return Collection<int, DispatchOrderItem>
     */
    public function itemsForOs(string $scopeKey, string $tab, int $osId, string $fromDay, string $toDay): Collection
    {
        return $this->itemsForTab($scopeKey, $tab, $fromDay, $toDay)
            ->filter(fn (DispatchOrderItem $item) => (int) ($item->order?->client_id ?? 0) === $osId)
            ->values();
    }

    /**
     * @return Collection<int, DispatchOrderItem>
     */
    public function itemsForTab(string $scopeKey, string $tab, string $fromDay, string $toDay): Collection
    {
        $fromDay = Carbon::parse($fromDay)->toDateString();
        $toDay = Carbon::parse($toDay)->toDateString();

        if ($tab === self::TAB_ADVANCED_PAID) {
            return $this->trackedItems($scopeKey, KyoShinItem::STATUS_ADVANCED_PAID, $fromDay, $toDay, 'advanced_paid_at');
        }
        if ($tab === self::TAB_FINISHED) {
            return $this->trackedItems($scopeKey, KyoShinItem::STATUS_FINISHED, $fromDay, $toDay, 'finished_at');
        }

        return $this->osListItems($scopeKey, $fromDay, $toDay);
    }

    public function markAdvancedPaid(string $scopeKey, Collection $items, User $actor): int
    {
        $count = 0;
        foreach ($items as $item) {
            if (! $item instanceof DispatchOrderItem) {
                continue;
            }
            $existing = KyoShinItem::query()->where('dispatch_order_item_id', $item->id)->first();
            if ($existing) {
                continue;
            }

            KyoShinItem::query()->create([
                'dispatch_order_item_id' => $item->id,
                'scope_key' => $scopeKey,
                'os_user_id' => (int) ($item->order?->client_id ?? 0),
                'amount' => $this->itemAmount($item),
                'status' => KyoShinItem::STATUS_ADVANCED_PAID,
                'advanced_paid_at' => now(),
                'advanced_paid_by' => $actor->id,
            ]);
            $count++;
        }

        return $count;
    }

    public function markFinished(string $scopeKey, Collection $items, User $actor): int
    {
        $ids = $items->map(fn ($item) => (int) $item->id)->filter()->all();
        if ($ids === []) {
            return 0;
        }

        return KyoShinItem::query()
            ->where('scope_key', $scopeKey)
            ->where('status', KyoShinItem::STATUS_ADVANCED_PAID)
            ->whereIn('dispatch_order_item_id', $ids)
            ->update([
                'status' => KyoShinItem::STATUS_FINISHED,
                'finished_at' => now(),
                'finished_by' => $actor->id,
                'updated_at' => now(),
            ]);
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

    protected function osListItems(string $scopeKey, string $fromDay, string $toDay): Collection
    {
        $trackedIds = $this->trackedItemIds($scopeKey);
        $boundsStart = dailyCheckListDayBounds($fromDay)['start'];
        $boundsEnd = dailyCheckListDayBounds($toDay)['end'];

        $query = DispatchOrderItem::query()
            ->with(['order.client.city', 'fromBranch', 'toBranch'])
            ->where('status', 'completed')
            ->whereNotNull('admin_completed_at')
            ->where('admin_completed_at', '>=', $boundsStart)
            ->where('admin_completed_at', '<', $boundsEnd);

        $this->applyScope($query, $scopeKey);
        if ($trackedIds !== []) {
            $query->whereNotIn('id', $trackedIds);
        }

        return $query
            ->orderByDesc('id')
            ->get()
            ->filter(fn (DispatchOrderItem $item) => (float) $item->displayOsToPay() < 0)
            ->filter(fn (DispatchOrderItem $item) => dailyCheckListItemInPeriod($item, $fromDay, $toDay))
            ->values();
    }

    protected function trackedItems(string $scopeKey, string $status, string $fromDay, string $toDay, string $dateColumn): Collection
    {
        if (! $this->tablesReady()) {
            return collect();
        }

        $start = Carbon::parse($fromDay, 'Asia/Yangon')->startOfDay();
        $end = Carbon::parse($toDay, 'Asia/Yangon')->addDay()->startOfDay();

        $itemIds = KyoShinItem::query()
            ->where('scope_key', $scopeKey)
            ->where('status', $status)
            ->where($dateColumn, '>=', $start)
            ->where($dateColumn, '<', $end)
            ->pluck('dispatch_order_item_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($itemIds === []) {
            return collect();
        }

        return DispatchOrderItem::query()
            ->with(['order.client.city', 'fromBranch', 'toBranch'])
            ->whereIn('id', $itemIds)
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return list<int>
     */
    protected function trackedItemIds(string $scopeKey): array
    {
        if (! $this->tablesReady()) {
            return [];
        }

        return KyoShinItem::query()
            ->where('scope_key', $scopeKey)
            ->pluck('dispatch_order_item_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    protected function applyScope($query, string $scopeKey)
    {
        $scope = $this->scopeByKey($scopeKey);
        if (! $scope) {
            return $query->whereRaw('0 = 1');
        }

        if ($scope['type'] === 'hub') {
            $hubId = (int) ($scope['hub_id'] ?? 0);
            if ($hubId <= 0 || ! Schema::hasColumn('dispatch_order_items', 'hub_user_id')) {
                return $query->whereRaw('0 = 1');
            }

            return $query->where('hub_user_id', $hubId);
        }

        $mdyId = (int) ($scope['branch_id'] ?? 0);
        if ($mdyId <= 0) {
            return $query->whereRaw('0 = 1');
        }

        $query->where(function ($inner) use ($mdyId) {
            $inner->where('from_branch_id', $mdyId)->orWhere('to_branch_id', $mdyId);
        });

        if (Schema::hasColumn('dispatch_order_items', 'hub_user_id')) {
            $query->where(function ($inner) {
                $inner->whereNull('hub_user_id')->orWhere('hub_user_id', 0);
            });
        }

        return $query;
    }

    protected function scopeKeyForHub(User $hub): ?string
    {
        $email = strtolower((string) $hub->email);
        $name = (string) $hub->name;
        if (str_contains($email, 'ygn2') || str_contains($name, 'M2M')) {
            return self::SCOPE_YGN_M2M;
        }
        if (str_contains($email, 'ygn1') || str_contains($name, 'Ngwe Latt') || str_contains($name, 'ငွေလတ်')) {
            return self::SCOPE_YGN_NLS;
        }

        return str_contains($email, 'ygn') ? self::SCOPE_YGN_NLS : null;
    }

    protected function labelForHub(User $hub, string $key): string
    {
        if ($key === self::SCOPE_YGN_M2M) {
            return 'YGN M2M';
        }
        if ($key === self::SCOPE_YGN_NLS) {
            return 'YGN Ngwe Latt Saung';
        }

        return trim((string) $hub->name) !== '' ? (string) $hub->name : $key;
    }
}
