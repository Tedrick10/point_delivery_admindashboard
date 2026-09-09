<?php

namespace App\Services;

use App\Models\DispatchOrderItem;
use App\Models\ExpenseCard;
use App\Models\ExpenseItem;
use App\Models\RiderRemit;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpenseRiderFuelSyncService
{
    public function subject(): string
    {
        return (string) __('message.expenses_rider_fuel');
    }

    /**
     * Expense card date for a Rider ငွေအပ် day (same calendar day).
     */
    public function expenseDateForRemitDay(string $remitDay): string
    {
        return Carbon::parse($remitDay)->toDateString();
    }

    /**
     * Rider ငွေအပ် day that feeds an Expense card date (same calendar day).
     */
    public function remitDateForExpenseDay(string $expenseDay): string
    {
        return Carbon::parse($expenseDay)->toDateString();
    }

    /**
     * Sum Rider ဆီဖိုး for remit day and upsert onto Expense card for the same day.
     */
    public function syncDate(string $remitDay, ?int $userId = null): void
    {
        $this->syncRemitDate($remitDay, $userId);
    }

    public function syncRemitDate(string $remitDay, ?int $userId = null, ?int $branchId = null): void
    {
        $this->syncExpenseDate($remitDay, $userId, $branchId);
    }

    public function syncExpenseDate(string $expenseDay, ?int $userId = null, ?int $branchId = null): void
    {
        $expenseDay = Carbon::parse($expenseDay)->toDateString();
        foreach ($this->branchIdsForExpenseDay($expenseDay, $branchId) as $id) {
            $this->upsertFuelOnExpenseDay(
                $expenseDay,
                $this->fuelTotalForRemitDay($expenseDay, $id),
                $userId,
                $id
            );
        }
    }

    public function fuelTotalForRemitDay(string $remitDay, ?int $branchId = null): float
    {
        return round((float) RiderRemit::query()
            ->whereDate('remit_date', Carbon::parse($remitDay)->toDateString())
            ->when($branchId && $branchId > 0, fn ($q) => $q->where('branch_id', $branchId))
            ->sum('fuel_amount'), 2);
    }

    public function fuelTotalForExpenseDay(string $expenseDay, ?int $branchId = null): float
    {
        return $this->fuelTotalForRemitDay($this->remitDateForExpenseDay($expenseDay), $branchId);
    }

    /**
     * Sync every Expense day in [from, to].
     */
    public function syncDateRange(string $from, string $to, ?int $userId = null): void
    {
        $from = Carbon::parse($from)->toDateString();
        $to = Carbon::parse($to)->toDateString();
        $subject = $this->subject();
        $remitService = app(RiderRemitService::class);

        $remitDays = RiderRemit::query()
            ->whereBetween('remit_date', [$from, $to])
            ->selectRaw('DATE(remit_date) as d')
            ->groupBy('d')
            ->pluck('d')
            ->map(fn ($d) => Carbon::parse($d)->toDateString());

        $itemDays = DispatchOrderItem::query()
            ->where('status', 'completed')
            ->whereNotNull('rider_remit_date')
            ->whereBetween('rider_remit_date', [$from, $to])
            ->selectRaw('DATE(rider_remit_date) as d')
            ->groupBy('d')
            ->pluck('d')
            ->map(fn ($d) => Carbon::parse($d)->toDateString());

        $cardDays = ExpenseCard::query()
            ->whereBetween('expense_date', [$from, $to])
            ->whereHas('items', function ($q) use ($subject) {
                $q->where('source', ExpenseItem::SOURCE_RIDER_FUEL)
                    ->orWhere('subject', $subject)
                    ->orWhere('subject', 'Rider ဆီဖိုး')
                    ->orWhere('subject', 'Rider fuel cost');
            })
            ->pluck('expense_date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString());

        $days = $remitDays
            ->merge($itemDays)
            ->merge($cardDays)
            ->unique()
            ->filter()
            ->values();

        foreach ($days as $day) {
            $remitService->ensureOpenRemitDefaults((string) $day, null, $userId);
            $this->syncExpenseDate((string) $day, $userId);
        }
    }

    /**
     * Create a card for every Yangon calendar day in [from, to] that is already
     * one day old (today = 10th → through the 9th). Missing days get an empty card.
     */
    public function ensureDailyCards(string $from, string $to, ?int $branchId = null, ?int $userId = null): void
    {
        $fromDay = Carbon::parse($from, 'Asia/Yangon')->startOfDay();
        $toDay = Carbon::parse($to, 'Asia/Yangon')->startOfDay();
        $yesterday = now('Asia/Yangon')->subDay()->startOfDay();

        if ($toDay->gt($yesterday)) {
            $toDay = $yesterday->copy();
        }
        if ($fromDay->gt($toDay)) {
            return;
        }

        $branchId = $branchId && $branchId > 0
            ? $branchId
            : (function_exists('defaultDestinationBranchId') ? defaultDestinationBranchId() : null);

        for ($day = $fromDay->copy(); $day->lte($toDay); $day->addDay()) {
            $date = $day->toDateString();
            $query = ExpenseCard::query()->whereDate('expense_date', $date);
            if ($branchId) {
                $query->where('branch_id', $branchId);
            } else {
                $query->whereNull('branch_id');
            }

            if (! $query->exists()) {
                ExpenseCard::query()->create([
                    'expense_date' => $date,
                    'branch_id' => $branchId,
                    'total_amount' => 0,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
            }

            $this->syncExpenseDate($date, $userId, $branchId);
        }
    }

    /**
     * @return list<int>
     */
    protected function branchIdsForExpenseDay(string $expenseDay, ?int $branchId = null): array
    {
        if ($branchId && $branchId > 0) {
            return [$branchId];
        }

        $tabIds = function_exists('destinationBranchTabs')
            ? destinationBranchTabs()->pluck('id')->map(fn ($id) => (int) $id)->filter()->values()
            : collect();

        $fromRemits = RiderRemit::query()
            ->whereDate('remit_date', $expenseDay)
            ->where('branch_id', '>', 0)
            ->pluck('branch_id')
            ->map(fn ($id) => (int) $id);

        $fromCards = ExpenseCard::query()
            ->whereDate('expense_date', $expenseDay)
            ->where('branch_id', '>', 0)
            ->pluck('branch_id')
            ->map(fn ($id) => (int) $id);

        $ids = $fromRemits->merge($fromCards)->unique()->filter()->values();
        if ($tabIds->isNotEmpty()) {
            $ids = $ids->intersect($tabIds)->values();
        }

        if ($ids->isEmpty()) {
            $default = function_exists('defaultDestinationBranchId') ? defaultDestinationBranchId() : null;
            if ($default) {
                return [(int) $default];
            }
        }

        return $ids->all();
    }

    protected function upsertFuelOnExpenseDay(string $expenseDay, float $total, ?int $userId = null, ?int $branchId = null): void
    {
        $branchId = $branchId && $branchId > 0
            ? $branchId
            : (function_exists('defaultDestinationBranchId') ? defaultDestinationBranchId() : null);

        try {
            DB::transaction(function () use ($expenseDay, $total, $userId, $branchId) {
                $cardQuery = ExpenseCard::query()->whereDate('expense_date', $expenseDay);
                if ($branchId) {
                    $cardQuery->where('branch_id', $branchId);
                } else {
                    $cardQuery->whereNull('branch_id');
                }

                $card = $cardQuery->first();
                if (! $card) {
                    if ($total <= 0) {
                        return;
                    }
                    $card = ExpenseCard::query()->create([
                        'expense_date' => $expenseDay,
                        'branch_id' => $branchId,
                        'total_amount' => 0,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);
                }

                if ($card->isGenerated()) {
                    return;
                }

                $item = $this->findRiderFuelItem($card);
                $subject = $this->subject();

                if ($total <= 0) {
                    if ($item) {
                        $item->delete();
                    }
                    $card->forceFill(['updated_by' => $userId ?? $card->updated_by])->save();
                    $card->recalculateTotal();

                    return;
                }

                if ($item) {
                    $item->fill([
                        'subject' => $subject,
                        'amount' => $total,
                        'source' => ExpenseItem::SOURCE_RIDER_FUEL,
                        'sort_order' => 0,
                    ])->save();
                } else {
                    ExpenseItem::query()
                        ->where('expense_card_id', $card->id)
                        ->increment('sort_order');

                    ExpenseItem::query()->create([
                        'expense_card_id' => $card->id,
                        'subject' => $subject,
                        'amount' => $total,
                        'sort_order' => 0,
                        'source' => ExpenseItem::SOURCE_RIDER_FUEL,
                    ]);
                }

                $card->forceFill(['updated_by' => $userId ?? $card->updated_by])->save();
                $card->recalculateTotal();
            });
        } catch (\Throwable $e) {
            Log::warning('expense rider fuel sync failed', [
                'expense_day' => $expenseDay,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function findRiderFuelItem(ExpenseCard $card): ?ExpenseItem
    {
        $subject = $this->subject();

        return ExpenseItem::query()
            ->where('expense_card_id', $card->id)
            ->where(function ($q) use ($subject) {
                $q->where('source', ExpenseItem::SOURCE_RIDER_FUEL)
                    ->orWhere('subject', $subject)
                    ->orWhere('subject', 'Rider fuel cost')
                    ->orWhere('subject', 'Rider ဆီဖိုး');
            })
            ->orderBy('id')
            ->first();
    }

    public function isRiderFuelSubject(string $subject): bool
    {
        $subject = trim($subject);
        if ($subject === '') {
            return false;
        }

        return in_array($subject, [
            $this->subject(),
            'Rider ဆီဖိုး',
            'Rider fuel cost',
            'Total Rider ဆီဖိုး',
        ], true);
    }
}
