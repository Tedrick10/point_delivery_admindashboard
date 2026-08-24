<?php

namespace App\Services;

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
     * Sum all riders' ဆီဖိုး for the day and upsert Expense "Rider ဆီဖိုး".
     */
    public function syncDate(string $day, ?int $userId = null): void
    {
        $day = Carbon::parse($day)->toDateString();
        $total = round((float) RiderRemit::query()
            ->whereDate('remit_date', $day)
            ->sum('fuel_amount'), 2);

        try {
            DB::transaction(function () use ($day, $total, $userId) {
                $card = ExpenseCard::query()->firstOrCreate(
                    ['expense_date' => $day],
                    [
                        'total_amount' => 0,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]
                );

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
                'day' => $day,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Sync every date in [from, to] that has rider remits (or already has a rider-fuel item).
     */
    public function syncDateRange(string $from, string $to, ?int $userId = null): void
    {
        $from = Carbon::parse($from)->toDateString();
        $to = Carbon::parse($to)->toDateString();
        $subject = $this->subject();

        $remitDays = RiderRemit::query()
            ->whereBetween('remit_date', [$from, $to])
            ->selectRaw('DATE(remit_date) as d')
            ->groupBy('d')
            ->pluck('d');

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

        $days = $remitDays->merge($cardDays)->unique()->filter()->values();
        foreach ($days as $day) {
            $this->syncDate((string) $day, $userId);
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
