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

    public function syncRemitDate(string $remitDay, ?int $userId = null): void
    {
        $remitDay = Carbon::parse($remitDay)->toDateString();
        $total = $this->fuelTotalForRemitDay($remitDay);
        $this->upsertFuelOnExpenseDay($remitDay, $total, $userId);
    }

    public function syncExpenseDate(string $expenseDay, ?int $userId = null): void
    {
        $expenseDay = Carbon::parse($expenseDay)->toDateString();
        $total = $this->fuelTotalForRemitDay($expenseDay);
        $this->upsertFuelOnExpenseDay($expenseDay, $total, $userId);
    }

    public function fuelTotalForRemitDay(string $remitDay): float
    {
        return round((float) RiderRemit::query()
            ->whereDate('remit_date', Carbon::parse($remitDay)->toDateString())
            ->sum('fuel_amount'), 2);
    }

    public function fuelTotalForExpenseDay(string $expenseDay): float
    {
        return $this->fuelTotalForRemitDay($this->remitDateForExpenseDay($expenseDay));
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

    protected function upsertFuelOnExpenseDay(string $expenseDay, float $total, ?int $userId = null): void
    {
        try {
            DB::transaction(function () use ($expenseDay, $total, $userId) {
                $card = ExpenseCard::query()->firstOrCreate(
                    ['expense_date' => $expenseDay],
                    [
                        'total_amount' => 0,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]
                );

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
