<?php

namespace App\Services;

use App\Models\DispatchOrderItem;
use App\Models\RiderRemit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RiderRemitService
{
    public function parseDate(string $value): Carbon
    {
        try {
            return Carbon::createFromFormat('d-m-Y', trim($value))->startOfDay();
        } catch (\Throwable $e) {
            return Carbon::parse($value)->startOfDay();
        }
    }

    /**
     * @return array{riders: Collection<int, object>, summary: object}
     */
    public function sheet(string $day, ?int $branchId): array
    {
        $dues = $this->dueByRider($day, $branchId);
        $saved = RiderRemit::query()
            ->with('deliveryMan:id,name,username,contact_number')
            ->whereDate('remit_date', $day)
            ->where('branch_id', $branchId && $branchId > 0 ? $branchId : 0)
            ->get()
            ->keyBy(fn (RiderRemit $row) => (int) $row->delivery_man_id);

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
            ->get(['id', 'name', 'username', 'contact_number'])
            ->keyBy('id');

        $riders = $riderIds->map(function ($riderId) use ($dues, $saved, $users, $day, $branchId) {
            $user = $users->get($riderId);
            $remit = $saved->get($riderId);
            $due = (float) ($dues->get($riderId)?->due ?? 0);
            $itemCount = (int) ($dues->get($riderId)?->item_count ?? 0);
            $denoms = $this->normalizeDenoms($remit?->denominations);

            $prepaid = (float) ($remit->prepaid_amount ?? 0);
            $fuel = (float) ($remit->fuel_amount ?? 0);
            $fee = (float) ($remit->fee_amount ?? 0);
            $kpay = (float) ($remit->kpay_amount ?? 0);
            $cash = $this->cashFromDenoms($denoms);
            $remaining = round($due - $prepaid - $fuel - $fee, 2);
            $combined = round($cash + $kpay, 2);
            $match = $this->matchStatus($combined, $remaining);

            return (object) [
                'delivery_man_id' => $riderId,
                'name' => $this->displayName($user, $riderId),
                'short_name' => $this->displayName($user, $riderId),
                'phone' => trim((string) ($user?->contact_number ?? '')),
                'item_count' => $itemCount,
                'due_amount' => $due,
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
                'remaining_total' => round($riders->sum('remaining'), 2),
                'cash_total' => round($riders->sum('cash_total'), 2),
                'kpay_total' => round($riders->sum('kpay_amount'), 2),
                'balanced_count' => $riders->filter(fn ($r) => $r->balanced)->count(),
            ],
        ];
    }

    public function save(array $data, int $userId): RiderRemit
    {
        $day = $data['remit_date'];
        $branchId = (int) ($data['branch_id'] ?? 0);
        $riderId = (int) $data['delivery_man_id'];
        $denoms = $this->normalizeDenoms($data['denominations'] ?? []);

        $dues = $this->dueByRider($day, $branchId > 0 ? $branchId : null);
        $due = (float) ($dues->get($riderId)?->due ?? 0);

        $row = RiderRemit::query()->updateOrCreate(
            [
                'remit_date' => $day,
                'branch_id' => $branchId,
                'delivery_man_id' => $riderId,
            ],
            [
                'due_amount' => $due,
                'prepaid_amount' => round((float) ($data['prepaid_amount'] ?? 0), 2),
                'fuel_amount' => round((float) ($data['fuel_amount'] ?? 0), 2),
                'fee_amount' => round((float) ($data['fee_amount'] ?? 0), 2),
                'denominations' => $denoms,
                'kpay_amount' => round((float) ($data['kpay_amount'] ?? 0), 2),
                'updated_by' => $userId,
            ]
        );

        return $row->fresh('deliveryMan');
    }

    /**
     * @return Collection<int, object>
     */
    protected function dueByRider(string $day, ?int $branchId): Collection
    {
        $items = DispatchOrderItem::query()
            ->where('status', 'completed')
            ->whereNotNull('admin_completed_at')
            ->whereNotNull('admin_finished_at')
            ->whereNotNull('delivery_man_id')
            ->where(function ($dateQuery) use ($day) {
                $dateQuery->whereDate('received_date', $day)
                    ->orWhere(function ($fallback) use ($day) {
                        $fallback->whereNull('received_date')
                            ->where(function ($assigned) use ($day) {
                                $assigned->where(function ($q) use ($day) {
                                    $q->whereNotNull('assigned_at')->whereDate('assigned_at', $day);
                                })->orWhere(function ($q) use ($day) {
                                    $q->whereNull('assigned_at')->whereDate('created_at', $day);
                                });
                            });
                    });
            })
            ->when($branchId && $branchId > 0, function ($q) use ($branchId) {
                $q->where(function ($inner) use ($branchId) {
                    $inner->where('from_branch_id', $branchId)
                        ->orWhere('to_branch_id', $branchId);
                });
            })
            ->get(['id', 'delivery_man_id', 'cust_get']);

        return $items
            ->groupBy(fn (DispatchOrderItem $item) => (int) $item->delivery_man_id)
            ->map(function (Collection $group, $riderId) {
                return (object) [
                    'delivery_man_id' => (int) $riderId,
                    'due' => round($group->sum(fn (DispatchOrderItem $i) => (float) ($i->cust_get ?? 0)), 2),
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
