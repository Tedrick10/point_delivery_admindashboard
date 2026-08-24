<?php

namespace App\Services;

use App\Models\ExpenseCard;
use App\Models\ExpenseSummary;
use Illuminate\Support\Facades\DB;

class ExpenseSummaryService
{
    public function __construct(
        private readonly DailyCheckListService $dailyCheckList
    ) {
    }

    /**
     * Income = Daily Check List DeliAmount total for the card date (OS mode, branch-scoped when forced).
     */
    public function incomeForDate(string $day, $user = null): float
    {
        $branchId = null;
        if ($user !== null && function_exists('forcedBranchId')) {
            $forced = forcedBranchId($user);
            if ($forced) {
                $branchId = (int) $forced;
            }
        }

        $rows = $this->dailyCheckList->listRows($day, $day, 'os', $branchId, null, null);

        return round((float) $rows->sum('deli_amount'), 2);
    }

    public function generateFromCard(ExpenseCard $card, ?int $userId = null): ExpenseSummary
    {
        $card->loadMissing('items');
        $card->recalculateTotal();
        $card->refresh();

        $day = $card->expense_date->toDateString();
        $user = $userId ? \App\Models\User::query()->find($userId) : auth()->user();
        $income = $this->incomeForDate($day, $user);
        $expense = round((float) $card->total_amount, 2);
        $akoGiven = round($income - $expense, 2);

        return DB::transaction(function () use ($card, $day, $income, $expense, $akoGiven, $userId) {
            ExpenseSummary::query()
                ->where('expense_card_id', $card->id)
                ->whereDate('summary_date', '!=', $day)
                ->delete();

            $summary = ExpenseSummary::query()->updateOrCreate(
                ['summary_date' => $day],
                [
                    'expense_card_id' => $card->id,
                    'income' => $income,
                    'expense' => $expense,
                    'ako_given' => $akoGiven,
                    'generated_by' => $userId,
                ]
            );

            return $summary->fresh();
        });
    }
}
