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
     * Income = Daily Check List OS DeliAmount for the card date (settled OS only).
     * AKO's Given = Income − Expense (set in generateFromCard).
     */
    public function incomeForDate(string $day, $user = null): float
    {
        return round((float) $this->osIncomeRows($day, $user)->sum('deli_amount'), 2);
    }

    /**
     * Daily Check OS invoices for View Income Card (Online Shop Name / Deli Amount).
     *
     * @return list<array{subject: string, amount: float, locked: bool}>
     */
    public function incomeCardItemsForDate(string $day, $user = null): array
    {
        return $this->osIncomeRows($day, $user)
            ->map(function ($row) {
                return [
                    'subject' => (string) ($row->name ?? __('message.online_shopping')),
                    'amount' => round((float) ($row->deli_amount ?? 0), 2),
                    'locked' => true,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return \Illuminate\Support\Collection<int, object>
     */
    protected function osIncomeRows(string $day, $user = null)
    {
        $branchId = null;
        if ($user !== null && function_exists('forcedBranchId')) {
            $forced = forcedBranchId($user);
            if ($forced) {
                $branchId = (int) $forced;
            }
        }

        // Prefer stored Daily Check OS invoices (stable for Summary Income Card).
        $fromInvoices = $this->dailyCheckList->osInvoiceRowsForDate($day, $branchId);
        if ($fromInvoices->isNotEmpty()) {
            return $fromInvoices;
        }

        // Fallback: live Daily Check list query (same day window).
        return $this->dailyCheckList->listRows($day, $day, 'os', $branchId, null, null);
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

    /**
     * Refresh stored Summary Income / AKO from live Daily Check OS DeliAmount.
     * Keeps Income Card and Summary table in sync after new settlements.
     *
     * @param  \Illuminate\Support\Collection<int, ExpenseSummary>  $rows
     * @return \Illuminate\Support\Collection<int, ExpenseSummary>
     */
    public function syncIncomeOnRows($rows, $user = null)
    {
        foreach ($rows as $row) {
            $day = $row->summary_date?->toDateString();
            if (! $day) {
                continue;
            }

            $income = $this->incomeForDate($day, $user);
            $expense = round((float) $row->expense, 2);
            $akoGiven = round($income - $expense, 2);

            if (abs((float) $row->income - $income) < 0.001 && abs((float) $row->ako_given - $akoGiven) < 0.001) {
                continue;
            }

            $row->income = $income;
            $row->ako_given = $akoGiven;
            $row->save();
        }

        return $rows;
    }
}
