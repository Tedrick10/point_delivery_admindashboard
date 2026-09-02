<?php

namespace App\Services;

use App\Models\DispatchOrderItem;
use App\Models\RiderRemit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RiderRemitService
{
    /** Fallback auto ဆီဖိုး when no setting is saved. */
    public const DEFAULT_FUEL_AMOUNT = 10000.0;

    public function defaultFuelAmount(): float
    {
        $raw = SettingData('rider_remit', 'default_fuel_amount');
        if ($raw === null || $raw === '') {
            return self::DEFAULT_FUEL_AMOUNT;
        }

        $amount = (float) $raw;

        return $amount >= 0 ? $amount : self::DEFAULT_FUEL_AMOUNT;
    }

    public function setDefaultFuelAmount(float $amount): float
    {
        $amount = max(0, round($amount, 2));
        \App\Models\Setting::query()->updateOrCreate(
            ['type' => 'rider_remit', 'key' => 'default_fuel_amount'],
            ['value' => (string) $amount]
        );

        return $amount;
    }

    /**
     * Effective ဆီဖိုး for a rider (per-rider override, else global default).
     */
    public function riderFuelAmount(int $riderId): float
    {
        if ($riderId > 0) {
            $raw = SettingData('rider_remit', 'rider_fuel_'.$riderId);
            if ($raw !== null && $raw !== '') {
                $amount = (float) $raw;

                return $amount >= 0 ? round($amount, 2) : $this->defaultFuelAmount();
            }
        }

        return $this->defaultFuelAmount();
    }

    public function setRiderFuelAmount(int $riderId, float $amount): float
    {
        $riderId = (int) $riderId;
        $amount = max(0, round($amount, 2));

        \App\Models\Setting::query()->updateOrCreate(
            ['type' => 'rider_remit', 'key' => 'rider_fuel_'.$riderId],
            ['value' => (string) $amount]
        );

        $this->applyRiderFuelToOpenRemits($riderId, $amount);

        return $amount;
    }

    /**
     * Push a rider's ဆီဖိုး into all open (unsubmitted) remit rows.
     */
    public function applyRiderFuelToOpenRemits(int $riderId, float $newFuel): int
    {
        $riderId = (int) $riderId;
        $newFuel = max(0, round($newFuel, 2));
        if ($riderId < 1) {
            return 0;
        }

        $days = RiderRemit::query()
            ->where('delivery_man_id', $riderId)
            ->whereNull('submitted_at')
            ->pluck('remit_date')
            ->map(fn ($d) => $d instanceof \Carbon\Carbon ? $d->toDateString() : (string) $d)
            ->filter()
            ->unique()
            ->values();

        $updated = RiderRemit::query()
            ->where('delivery_man_id', $riderId)
            ->whereNull('submitted_at')
            ->update([
                'fuel_amount' => $newFuel,
                'updated_by' => auth()->id(),
            ]);

        if ($updated > 0) {
            $userId = (int) (auth()->id() ?? 0);
            foreach ($days as $day) {
                app(\App\Services\ExpenseRiderFuelSyncService::class)->syncDate($day, $userId ?: null);
            }
        }

        return (int) $updated;
    }

    /**
     * Active delivery riders for Super Admin ဆီဖိုး controls.
     *
     * @return \Illuminate\Support\Collection<int, object{id:int,name:string,fuel_amount:float}>
     */
    public function fuelControlRiders(): Collection
    {
        return User::query()
            ->where('user_type', 'delivery_man')
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'username', 'contact_number', 'rider_work_on', 'rider_work_off_date'])
            ->filter(fn (User $user) => $this->isDisplayableRider($user))
            ->map(fn (User $user) => (object) [
                'id' => (int) $user->id,
                'name' => $this->displayName($user, (int) $user->id),
                'fuel_amount' => $this->riderFuelAmount((int) $user->id),
            ])
            ->values();
    }

    protected function resolveFuelAmount(int $riderId, ?float $savedFuel, int $itemCount): float
    {
        $saved = round((float) ($savedFuel ?? 0), 2);
        if ($saved > 0) {
            return $saved;
        }

        return $itemCount >= 1 ? $this->riderFuelAmount($riderId) : 0.0;
    }

    protected function resolveFeeAmount($savedFee, float $gate): float
    {
        if ($savedFee !== null && (float) $savedFee > 0) {
            return round((float) $savedFee, 2);
        }

        return round($gate, 2);
    }

    /**
     * Apply new default fuel to open remits still on the previous auto value (or 0).
     */
    public function applyDefaultFuelToOpenRemits(string $day, ?int $branchId, float $oldDefault, float $newDefault): int
    {
        if (abs($oldDefault - $newDefault) < 0.001) {
            return 0;
        }

        $customRiderIds = \App\Models\Setting::query()
            ->where('type', 'rider_remit')
            ->where('key', 'like', 'rider_fuel_%')
            ->pluck('key')
            ->map(fn ($key) => (int) str_replace('rider_fuel_', '', (string) $key))
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();

        $updated = RiderRemit::query()
            ->whereDate('remit_date', $day)
            ->where('branch_id', $this->branchStore($branchId))
            ->whereNull('submitted_at')
            ->when($customRiderIds !== [], fn ($q) => $q->whereNotIn('delivery_man_id', $customRiderIds))
            ->where(function ($q) use ($oldDefault) {
                $q->where('fuel_amount', '<=', 0)
                    ->orWhereRaw('ABS(fuel_amount - ?) < 0.001', [$oldDefault]);
            })
            ->update([
                'fuel_amount' => $newDefault,
                'updated_by' => auth()->id(),
            ]);

        if ($updated > 0) {
            app(\App\Services\ExpenseRiderFuelSyncService::class)->syncDate($day, (int) auth()->id());
        }

        return (int) $updated;
    }

    public function parseDate(string $value): Carbon
    {
        try {
            return Carbon::createFromFormat('d-m-Y', trim($value))->startOfDay();
        } catch (\Throwable $e) {
            return Carbon::parse($value)->startOfDay();
        }
    }

    /**
     * Persist default ဆီဖိုး / တန်ဆာခ / due for riders with open Delivered ways (display → DB).
     * Keeps Expense Rider fuel sync aligned with the Rider ငွေအပ် sheet.
     */
    public function ensureOpenRemitDefaults(string $day, ?int $branchId, ?int $userId = null): int
    {
        if ($branchId && $branchId > 0) {
            return $this->ensureOpenRemitDefaultsForBranch($day, $branchId, $userId);
        }

        $items = $this->remittableItemsQuery(null, $day)
            ->get(['delivery_man_id', 'from_branch_id', 'to_branch_id']);

        $branchIds = $items
            ->map(fn (DispatchOrderItem $item) => (int) ($item->from_branch_id ?: $item->to_branch_id ?: 0))
            ->unique()
            ->values();

        if ($branchIds->isEmpty()) {
            return $this->ensureOpenRemitDefaultsForBranch($day, null, $userId);
        }

        $updated = 0;
        foreach ($branchIds as $bid) {
            $updated += $this->ensureOpenRemitDefaultsForBranch($day, $bid > 0 ? (int) $bid : null, $userId);
        }

        return $updated;
    }

    protected function ensureOpenRemitDefaultsForBranch(string $day, ?int $branchId, ?int $userId = null): int
    {
        $branchStore = $this->branchStore($branchId);
        $dues = $this->dueByRider($branchId, $day);
        $updated = 0;

        foreach ($dues as $dueRow) {
            $riderId = (int) ($dueRow->delivery_man_id ?? 0);
            $itemCount = (int) ($dueRow->item_count ?? 0);
            if ($riderId < 1 || $itemCount < 1) {
                continue;
            }

            $open = $this->openRemitForRider($riderId, $branchStore, $day);
            if ($open?->submitted_at !== null) {
                continue;
            }

            $due = round((float) ($dueRow->due ?? 0), 2);
            $gate = round((float) ($dueRow->gate ?? 0), 2);
            $savedFuel = (float) ($open?->fuel_amount ?? 0);
            $savedFee = $open?->fee_amount;
            $savedDue = (float) ($open?->due_amount ?? 0);

            $fuel = $this->resolveFuelAmount($riderId, $savedFuel, $itemCount);
            $fee = $this->resolveFeeAmount($savedFee, $gate);
            $nextDue = $savedDue > 0 ? $savedDue : $due;

            if ($open && abs($savedFuel - $fuel) < 0.001 && abs((float) $savedFee - $fee) < 0.001 && abs($savedDue - $nextDue) < 0.001) {
                continue;
            }

            RiderRemit::query()->updateOrCreate(
                [
                    'remit_date' => $day,
                    'branch_id' => $branchStore,
                    'delivery_man_id' => $riderId,
                ],
                [
                    'due_amount' => $nextDue,
                    'prepaid_amount' => (float) ($open?->prepaid_amount ?? 0),
                    'fuel_amount' => $fuel,
                    'fee_amount' => $fee,
                    'kpay_amount' => (float) ($open?->kpay_amount ?? 0),
                    'denominations' => $this->normalizeDenoms($open?->denominations ?? []),
                    'is_off' => false,
                    'submitted_at' => null,
                    'updated_by' => $userId ?? auth()->id(),
                ]
            );
            $updated++;
        }

        if ($updated > 0) {
            app(ExpenseRiderFuelSyncService::class)->syncDate($day, $userId);
        }

        return $updated;
    }

    /**
     * @return array{riders: Collection<int, object>, summary: object}
     */
    public function sheet(string $day, ?int $branchId): array
    {
        $dues = $this->dueByRider($branchId, $day);
        $saved = $this->openRemitsByRider($branchId, $day);

        $active = User::query()
            ->where('user_type', 'delivery_man')
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'username', 'contact_number'])
            ->filter(fn (User $user) => $this->isDisplayableRider($user))
            ->keyBy('id');

        $riderIds = $active->keys()
            ->merge($dues->keys()->filter(fn ($id) => (float) ($dues->get($id)?->due ?? 0) > 0))
            ->merge($saved->keys())
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $users = User::query()
            ->whereIn('id', $riderIds->all())
            ->get(['id', 'name', 'username', 'contact_number', 'rider_work_on', 'rider_work_off_date'])
            ->keyBy('id');

        $submitted = $this->latestSubmittedByRider($branchId, $day);

        $riders = $riderIds->map(function ($riderId) use ($dues, $saved, $submitted, $users, $day, $branchId) {
            $user = $users->get($riderId);
            $remit = $saved->get($riderId);
            $openItemCount = (int) ($dues->get($riderId)?->item_count ?? 0);
            $done = $submitted->get($riderId);
            $isSubmitted = $openItemCount < 1 && $remit === null && $done !== null;

            if ($isSubmitted) {
                return $this->zeroedSheetRider($riderId, $user, $day, $branchId, $done, false);
            }

            if ($remit?->submitted_at !== null && $openItemCount >= 1) {
                $remit = $this->freshBatchFromSubmitted($remit);
            }

            $due = (float) ($dues->get($riderId)?->due ?? 0);
            $gate = (float) ($dues->get($riderId)?->gate ?? 0);
            $itemCount = (int) ($dues->get($riderId)?->item_count ?? 0);
            $denoms = $this->normalizeDenoms($remit?->denominations);

            $prepaid = (float) ($remit?->prepaid_amount ?? 0);
            // Delivered ways → auto ဆီဖိုး from rider/global default until a positive saved value exists.
            $savedFuel = (float) ($remit?->fuel_amount ?? 0);
            $fuel = $this->resolveFuelAmount($riderId, $savedFuel, $itemCount);
            // Auto-fill တန်ဆာခ from Rider List Gate when unset / still 0.
            $fee = $this->resolveFeeAmount($remit?->fee_amount, $gate);
            $kpay = (float) ($remit?->kpay_amount ?? 0);
            $cash = $this->cashFromDenoms($denoms);
            $remaining = round($due - $prepaid - $fuel - $fee, 2);
            $combined = round($cash + $kpay, 2);
            $match = $this->matchStatus($combined, $remaining);
            $canEdit = $itemCount >= 1;

            return (object) [
                'delivery_man_id' => $riderId,
                'name' => $this->displayName($user, $riderId),
                'short_name' => $this->displayName($user, $riderId),
                'phone' => trim((string) ($user?->contact_number ?? '')),
                'item_count' => $itemCount,
                'due_amount' => $due,
                'gate_amount' => $gate,
                'prepaid_amount' => $prepaid,
                'fuel_amount' => $fuel,
                'fee_amount' => $fee,
                'remaining' => $remaining,
                'denoms' => $denoms,
                'cash_total' => $cash,
                'kpay_amount' => $kpay,
                'combined' => $combined,
                'is_off' => false,
                'is_submitted' => false,
                'submitted_at' => $remit?->submitted_at,
                'can_edit' => $canEdit,
                'has_ways' => $itemCount >= 1,
                'balanced' => $itemCount < 1 || $match->ok,
                'match_class' => $match->class,
                'match_label' => $match->label,
                'remit_id' => $remit?->id,
                'remit_date' => $day,
                'branch_id' => $branchId && $branchId > 0 ? $branchId : 0,
            ];
        })->filter()->sortBy(fn ($row) => mb_strtolower($row->name), SORT_NATURAL)->values();

        return [
            'riders' => $riders,
            'summary' => (object) [
                'rider_count' => $riders->count(),
                'due_total' => round($riders->sum('due_amount'), 2),
                'prepaid_total' => round($riders->sum('prepaid_amount'), 2),
                'fuel_total' => round($riders->sum('fuel_amount'), 2),
                'fee_total' => round($riders->sum('fee_amount'), 2),
                'remaining_total' => round($riders->sum('remaining'), 2),
                'cash_total' => round($riders->sum('cash_total'), 2),
                'kpay_total' => round($riders->sum('kpay_amount'), 2),
                'balanced_count' => $riders->filter(fn ($r) => $r->balanced)->count(),
                'off_count' => 0,
                'submitted_count' => $riders->filter(fn ($r) => $r->is_submitted ?? false)->count(),
                'has_open_items' => (int) $dues->sum(fn ($d) => (int) ($d->item_count ?? 0)) > 0,
            ],
        ];
    }

    protected function assertRiderCanEnterData(int $branchId, int $riderId, string $day): void
    {
        $dues = $this->dueByRider($branchId > 0 ? $branchId : null, $day);
        if ((int) ($dues->get($riderId)?->item_count ?? 0) < 1) {
            throw ValidationException::withMessages([
                'delivery_man_id' => [__('message.rider_remit_no_ways_locked')],
            ]);
        }
    }

    protected function freshBatchFromSubmitted(RiderRemit $remit): RiderRemit
    {
        $clone = $remit->replicate();
        $clone->id = $remit->id;
        $clone->exists = true;
        $clone->submitted_at = null;
        $clone->prepaid_amount = 0;
        $clone->fuel_amount = 0;
        $clone->fee_amount = 0;
        $clone->kpay_amount = 0;
        $clone->denominations = $this->normalizeDenoms([]);
        $clone->is_off = false;

        return $clone;
    }

    protected function finalizeRiderRemit(string $day, int $branchId, int $riderId, int $userId, bool $markOff = false): void
    {
        RiderRemit::query()->updateOrCreate(
            [
                'remit_date' => $day,
                'branch_id' => $branchId,
                'delivery_man_id' => $riderId,
            ],
            [
                'due_amount' => 0,
                'prepaid_amount' => 0,
                'fuel_amount' => 0,
                'fee_amount' => 0,
                'kpay_amount' => 0,
                'denominations' => $this->normalizeDenoms([]),
                'is_off' => $markOff,
                'submitted_at' => now(),
                'updated_by' => $userId,
            ]
        );
    }

    protected function finalizeOpenRiderRemits(string $day, int $branchId, int $riderId, int $userId, bool $markOff = false): void
    {
        $openRows = RiderRemit::query()
            ->where('branch_id', $branchId)
            ->where('delivery_man_id', $riderId)
            ->whereDate('remit_date', $day)
            ->whereNull('submitted_at')
            ->get();

        if ($openRows->isEmpty()) {
            $this->finalizeRiderRemit($day, $branchId, $riderId, $userId, $markOff);

            return;
        }

        foreach ($openRows as $row) {
            $row->fill([
                'due_amount' => 0,
                'prepaid_amount' => 0,
                'fuel_amount' => 0,
                'fee_amount' => 0,
                'kpay_amount' => 0,
                'denominations' => $this->normalizeDenoms([]),
                'is_off' => $markOff,
                'submitted_at' => now(),
                'updated_by' => $userId,
            ]);
            $row->save();
        }
    }

    protected function branchStore(?int $branchId): int
    {
        return $branchId && $branchId > 0 ? $branchId : 0;
    }

    /**
     * @return Collection<int, RiderRemit>
     */
    protected function openRemitsByRider(?int $branchId, string $day): Collection
    {
        return RiderRemit::query()
            ->with('deliveryMan:id,name,username,contact_number')
            ->where('branch_id', $this->branchStore($branchId))
            ->whereDate('remit_date', $day)
            ->whereNull('submitted_at')
            ->orderByDesc('id')
            ->get()
            ->unique(fn (RiderRemit $row) => (int) $row->delivery_man_id)
            ->keyBy(fn (RiderRemit $row) => (int) $row->delivery_man_id);
    }

    protected function openRemitForRider(int $riderId, int $branchStore, ?string $day = null): ?RiderRemit
    {
        return RiderRemit::query()
            ->where('branch_id', $branchStore)
            ->where('delivery_man_id', $riderId)
            ->when($day, fn ($q) => $q->whereDate('remit_date', $day))
            ->whereNull('submitted_at')
            ->orderByDesc('remit_date')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @return Collection<int, RiderRemit>
     */
    protected function latestSubmittedByRider(?int $branchId, string $day): Collection
    {
        return RiderRemit::query()
            ->where('branch_id', $this->branchStore($branchId))
            ->whereDate('remit_date', $day)
            ->whereNotNull('submitted_at')
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->get()
            ->unique(fn (RiderRemit $row) => (int) $row->delivery_man_id)
            ->keyBy(fn (RiderRemit $row) => (int) $row->delivery_man_id);
    }

    protected function riderRequestedOffToday(int $riderId): bool
    {
        $user = User::query()->find($riderId);

        return $user ? ! $user->isRiderWorkOn() : false;
    }

    protected function zeroedSheetRider(int $riderId, ?User $user, string $day, ?int $branchId, RiderRemit $remit, bool $isOff = false): object
    {
        return (object) [
            'delivery_man_id' => $riderId,
            'name' => $this->displayName($user, $riderId),
            'short_name' => $this->displayName($user, $riderId),
            'phone' => trim((string) ($user?->contact_number ?? '')),
            'item_count' => 0,
            'due_amount' => 0,
            'gate_amount' => 0,
            'prepaid_amount' => 0,
            'fuel_amount' => 0,
            'fee_amount' => 0,
            'remaining' => 0,
            'denoms' => $this->normalizeDenoms([]),
            'cash_total' => 0,
            'kpay_amount' => 0,
            'combined' => 0,
            'is_off' => false,
            'is_submitted' => true,
            'submitted_at' => $remit->submitted_at,
            'can_edit' => false,
            'has_ways' => false,
            'balanced' => true,
            'match_class' => 'is-ok',
            'match_label' => '0',
            'remit_id' => $remit->id,
            'remit_date' => $day,
            'branch_id' => $branchId && $branchId > 0 ? $branchId : 0,
        ];
    }

    protected function markRiderItemsRemitted(?int $branchId, int $riderId, string $day): int
    {
        return $this->remittableItemsQuery($branchId, $day)
            ->where('delivery_man_id', $riderId)
            ->update(['rider_remit_at' => now()]);
    }

    /**
     * Open Delivered parcels for Rider ငွေအပ် on sheet day $day.
     * Uses rider_remit_date (Delivered before today’s Completed → yesterday; after → today).
     */
    protected function remittableItemsQuery(?int $branchId, string $day)
    {
        $bounds = riderRemitBusinessDayBounds($day);

        return DispatchOrderItem::query()
            ->where('status', 'completed')
            ->whereNotNull('delivery_man_id')
            ->where(function ($q) {
                $q->whereNull('rider_remit_at')
                    ->orWhereColumn('rider_remit_at', '<', 'created_at');
            })
            ->where(function ($q) use ($day, $bounds) {
                $q->whereDate('rider_remit_date', $day)
                    ->orWhere(function ($legacy) use ($bounds) {
                        // Legacy rows without rider_remit_date: Yangon calendar day of delivered_at.
                        $legacy->whereNull('rider_remit_date')
                            ->where(function ($inner) use ($bounds) {
                                $inner->where(function ($d) use ($bounds) {
                                    $d->whereNotNull('delivered_at')
                                        ->where('delivered_at', '>=', $bounds['start'])
                                        ->where('delivered_at', '<', $bounds['end']);
                                })->orWhere(function ($u) use ($bounds) {
                                    $u->whereNull('delivered_at')
                                        ->where('updated_at', '>=', $bounds['start'])
                                        ->where('updated_at', '<', $bounds['end']);
                                });
                            });
                    });
            })
            ->when($branchId && $branchId > 0, function ($q) use ($branchId) {
                $q->where(function ($inner) use ($branchId) {
                    $inner->where('from_branch_id', $branchId)
                        ->orWhere('to_branch_id', $branchId);
                });
            });
    }

    public function save(array $data, int $userId, bool $audit = true): RiderRemit
    {
        $branchId = (int) ($data['branch_id'] ?? 0);
        $riderId = (int) $data['delivery_man_id'];
        $denoms = $this->normalizeDenoms($data['denominations'] ?? []);
        $day = $this->parseDate((string) ($data['remit_date'] ?? now('Asia/Yangon')->toDateString()))->toDateString();
        $open = $this->openRemitForRider($riderId, $this->branchStore($branchId), $day);

        $dues = $this->dueByRider($branchId > 0 ? $branchId : null, $day);
        $due = (float) ($dues->get($riderId)?->due ?? 0);
        $gate = (float) ($dues->get($riderId)?->gate ?? 0);

        $this->assertRiderCanEnterData($branchId, $riderId, $day);

        $before = $open;

        $itemCount = (int) ($dues->get($riderId)?->item_count ?? 0);
        // ဆီဖိုး / တန်ဆာခ are system-controlled (SA / Gate) — never overwrite from sheet posts.
        $fuel = $this->resolveFuelAmount(
            $riderId,
            $open !== null ? (float) $open->fuel_amount : null,
            $itemCount
        );
        $fee = $this->resolveFeeAmount($open?->fee_amount, $gate);

        $row = RiderRemit::query()->updateOrCreate(
            [
                'remit_date' => $day,
                'branch_id' => $this->branchStore($branchId),
                'delivery_man_id' => $riderId,
            ],
            [
                'due_amount' => $due,
                'prepaid_amount' => round((float) ($data['prepaid_amount'] ?? 0), 2),
                'fuel_amount' => $fuel,
                'fee_amount' => $fee,
                'denominations' => $denoms,
                'kpay_amount' => round((float) ($data['kpay_amount'] ?? 0), 2),
                'is_off' => false,
                'submitted_at' => null,
                'updated_by' => $userId,
            ]
        );

        // Push day-total Rider ဆီဖိုး into Expenses for that date.
        app(\App\Services\ExpenseRiderFuelSyncService::class)->syncDate($day, $userId);

        $fresh = $row->fresh('deliveryMan');
        if ($audit) {
            app(RiderRemitAuditService::class)->logSaveChanges($day, $branchId, $riderId, $userId, $before, $fresh);
        }

        return $fresh;
    }

    /**
     * Save all riders for a day in one submit action.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{riders: Collection<int, object>, summary: object}
     */
    public function submitAll(string $day, ?int $branchId, array $rows, int $userId): array
    {
        $sheet = $this->sheet($day, $branchId);
        $allowedIds = $sheet['riders']->pluck('delivery_man_id')->map(fn ($id) => (int) $id)->all();
        $branchStore = $branchId && $branchId > 0 ? $branchId : 0;

        if ($allowedIds === []) {
            throw ValidationException::withMessages([
                'riders' => [__('message.rider_remit_empty')],
            ]);
        }

        $openItems = (int) $this->dueByRider($branchId, $day)->sum(fn ($d) => (int) ($d->item_count ?? 0));
        if ($openItems === 0) {
            $alreadySubmitted = RiderRemit::query()
                ->where('branch_id', $branchStore)
                ->whereDate('remit_date', $day)
                ->whereNotNull('submitted_at')
                ->exists();

            throw ValidationException::withMessages([
                'riders' => [$alreadySubmitted
                    ? __('message.rider_remit_day_already_submitted')
                    : __('message.rider_remit_submit_nothing_open')],
            ]);
        }

        $postedIds = [];
        foreach ($rows as $index => $rowData) {
            $riderId = (int) ($rowData['delivery_man_id'] ?? 0);
            if ($riderId <= 0 || ! in_array($riderId, $allowedIds, true)) {
                throw ValidationException::withMessages([
                    "riders.$index.delivery_man_id" => [__('message.something_went_wrong')],
                ]);
            }
            $postedIds[] = $riderId;

            $riderRow = $sheet['riders']->firstWhere('delivery_man_id', $riderId);
            $isOff = (bool) ($riderRow?->is_off ?? false);
            $hasWays = (int) ($riderRow?->item_count ?? 0) >= 1;
            if ($isOff || ! $hasWays) {
                continue;
            }

            $due = (float) ($riderRow?->due_amount ?? 0);
            $prepaid = round((float) ($rowData['prepaid_amount'] ?? 0), 2);
            $fuel = round((float) ($riderRow?->fuel_amount ?? 0), 2);
            $fee = round((float) ($riderRow?->fee_amount ?? 0), 2);
            $kpay = round((float) ($rowData['kpay_amount'] ?? 0), 2);
            $denoms = $this->normalizeDenoms($rowData['denominations'] ?? []);
            $remaining = round($due - $prepaid - $fuel - $fee, 2);
            $combined = round($this->cashFromDenoms($denoms) + $kpay, 2);

            if (! $this->matchStatus($combined, $remaining)->ok) {
                throw ValidationException::withMessages([
                    'riders' => [__('message.rider_remit_submit_unbalanced')],
                ]);
            }
        }

        if (count(array_unique($postedIds)) !== count($postedIds)) {
            throw ValidationException::withMessages([
                'riders' => [__('message.something_went_wrong')],
            ]);
        }

        if (count($postedIds) !== count($allowedIds)) {
            throw ValidationException::withMessages([
                'riders' => [__('message.rider_remit_submit_missing_riders')],
            ]);
        }

        DB::transaction(function () use ($day, $branchStore, $rows, $userId, $sheet) {
            foreach ($rows as $rowData) {
                $riderId = (int) $rowData['delivery_man_id'];
                $riderRow = $sheet['riders']->firstWhere('delivery_man_id', $riderId);
                $existing = $this->openRemitForRider($riderId, $branchStore, $day);

                if ($existing === null && (int) ($riderRow?->item_count ?? 0) < 1) {
                    continue;
                }

                if ((int) ($riderRow?->item_count ?? 0) >= 1 && ! ($riderRow?->is_off ?? false)) {
                    $this->save([
                        'remit_date' => $day,
                        'branch_id' => $branchStore,
                        'delivery_man_id' => $riderId,
                        'prepaid_amount' => $rowData['prepaid_amount'] ?? 0,
                        'fuel_amount' => $rowData['fuel_amount'] ?? 0,
                        'fee_amount' => $rowData['fee_amount'] ?? 0,
                        'kpay_amount' => $rowData['kpay_amount'] ?? 0,
                        'denominations' => $rowData['denominations'] ?? [],
                    ], $userId, false);

                    $this->markRiderItemsRemitted($branchStore > 0 ? $branchStore : null, $riderId, $day);
                }

                $this->finalizeOpenRiderRemits(
                    $day,
                    $branchStore,
                    $riderId,
                    $userId,
                    $this->riderRequestedOffToday((int) $riderId)
                );
            }

            app(RiderWorkStatusService::class)->applyPendingOffAfterSubmit(
                collect($rows)->pluck('delivery_man_id')->map(fn ($id) => (int) $id)->all()
            );
        });

        app(\App\Services\ExpenseRiderFuelSyncService::class)->syncDate($day, $userId);

        app(RiderRemitAuditService::class)->logSubmitted($day, $branchStore, $userId, count($allowedIds));

        return $this->sheet($day, $branchId);
    }

    /**
     * Delivered parcels (status=completed) for Rider ငွေအပ် sheet day.
     * Bucketed by rider_remit_date (Completed lock: before → yesterday, after → today). Does not wait for Finished.
     *
     * @return Collection<int, object{delivery_man_id:int, due:float, gate:float, item_count:int}>
     */
    protected function dueByRider(?int $branchId, string $day): Collection
    {
        $items = $this->remittableItemsQuery($branchId, $day)
            ->get(['id', 'delivery_man_id', 'cust_get', 'gate_amount']);

        return $items
            ->groupBy(fn (DispatchOrderItem $item) => (int) $item->delivery_man_id)
            ->map(function (Collection $group, $riderId) {
                return (object) [
                    'delivery_man_id' => (int) $riderId,
                    'due' => round($group->sum(fn (DispatchOrderItem $i) => (float) ($i->cust_get ?? 0)), 2),
                    'gate' => round($group->sum(fn (DispatchOrderItem $i) => (float) ($i->gate_amount ?? 0)), 2),
                    'item_count' => $group->count(),
                ];
            });
    }

    /**
     * @param  mixed  $raw
     * @return array<string, int>
     */
    public function normalizeDenoms($raw): array
    {
        $src = is_array($raw) ? $raw : [];
        $out = [];
        foreach (RiderRemit::DENOMS as $note) {
            $out[(string) $note] = max(0, (int) ($src[(string) $note] ?? $src[$note] ?? 0));
        }

        return $out;
    }

    /**
     * @param  array<string, int>  $denoms
     */
    public function cashFromDenoms(array $denoms): float
    {
        $total = 0.0;
        foreach (RiderRemit::DENOMS as $note) {
            $total += $note * (int) ($denoms[(string) $note] ?? 0);
        }

        return round($total, 2);
    }

    public function matchStatus(float $combined, float $remaining): object
    {
        $diff = round($combined - $remaining, 2);
        if (abs($diff) < 0.51) {
            return (object) [
                'ok' => true,
                'class' => 'is-ok',
                'label' => '0',
            ];
        }

        return (object) [
            'ok' => false,
            'class' => $diff > 0 ? 'is-over' : 'is-off',
            'label' => ($diff > 0 ? '+' : '-').number_format(abs($diff)),
        ];
    }

    public function serializeRider(RiderRemit $row, float $liveDue): object
    {
        $denoms = $this->normalizeDenoms($row->denominations);
        $prepaid = (float) $row->prepaid_amount;
        $fuel = (float) $row->fuel_amount;
        $fee = (float) $row->fee_amount;
        $kpay = (float) $row->kpay_amount;
        $cash = $this->cashFromDenoms($denoms);
        $remaining = round($liveDue - $prepaid - $fuel - $fee, 2);
        $combined = round($cash + $kpay, 2);
        $match = $this->matchStatus($combined, $remaining);

        return (object) [
            'delivery_man_id' => (int) $row->delivery_man_id,
            'due_amount' => $liveDue,
            'prepaid_amount' => $prepaid,
            'fuel_amount' => $fuel,
            'fee_amount' => $fee,
            'remaining' => $remaining,
            'denoms' => $denoms,
            'cash_total' => $cash,
            'kpay_amount' => $kpay,
            'combined' => $combined,
            'balanced' => $match->ok,
            'match_class' => $match->class,
            'match_label' => $match->label,
            'remit_id' => $row->id,
        ];
    }

    protected function isDisplayableRider(?User $user): bool
    {
        if (! $user) {
            return false;
        }
        $username = trim((string) ($user->username ?? ''));
        $name = trim((string) ($user->name ?? ''));
        if (str_contains($username, '@') || str_contains($name, '@')) {
            return false;
        }

        return $name !== '';
    }

    protected function displayName(?User $user, int $riderId): string
    {
        $name = trim((string) ($user?->name ?? ''));
        if ($name === '' || str_contains($name, '@')) {
            $name = trim((string) ($user?->username ?? ''));
        }
        if ($name === '' || str_contains($name, '@')) {
            return 'Rider #'.$riderId;
        }

        return $name;
    }
}
