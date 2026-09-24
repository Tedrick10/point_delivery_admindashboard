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
    public function incomeForDate(string $day, $user = null, ?int $branchId = null): float
    {
        return round((float) $this->osIncomeRows($day, $user, $branchId)->sum('deli_amount'), 2);
    }

    /**
     * Daily Check OS invoices for View Income Card (Online Shop Name / Deli Amount).
     *
     * @return list<array{subject: string, amount: float, locked: bool}>
     */
    public function incomeCardItemsForDate(string $day, $user = null, ?int $branchId = null): array
    {
        return $this->osIncomeRows($day, $user, $branchId)
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
    protected function osIncomeRows(string $day, $user = null, ?int $branchId = null)
    {
        if ($user !== null && function_exists('forcedBranchId')) {
            $forced = forcedBranchId($user);
            if ($forced) {
                $branchId = (int) $forced;
            }
        }

        // Prefer stored Daily Check OS invoices (stable for Summary Income Card).
        $fromInvoices = $this->dailyCheckList->osInvoiceRowsForDate($day, $branchId);
        if ($branchId && $branchId > 0) {
            $fromInvoices = $fromInvoices->filter(fn ($row) => (int) ($row->branch_id ?? 0) === $branchId)->values();
        }
        if ($fromInvoices->isNotEmpty()) {
            return $fromInvoices;
        }

        // Fallback: live Daily Check list query (same day window).
        $rows = $this->dailyCheckList->listRows($day, $day, 'os', $branchId, null, null);
        if ($branchId && $branchId > 0) {
            $rows = $rows->filter(fn ($row) => (int) ($row->branch_id ?? 0) === $branchId)->values();
        }

        return $rows;
    }

    public function generateFromCard(ExpenseCard $card, ?int $userId = null): ExpenseSummary
    {
        $card->loadMissing('items');
        $card->recalculateTotal();
        $card->refresh();

        $day = $card->expense_date->toDateString();
        $cardBranchId = (int) ($card->branch_id ?? 0) ?: null;
        $user = $userId ? \App\Models\User::query()->find($userId) : auth()->user();
        $income = $this->incomeForDate($day, $user, $cardBranchId);
        $expense = round((float) $card->total_amount, 2);
        $akoGiven = round($income - $expense, 2);

        return DB::transaction(function () use ($card, $day, $cardBranchId, $income, $expense, $akoGiven, $userId) {
            ExpenseSummary::query()
                ->where('expense_card_id', $card->id)
                ->whereDate('summary_date', '!=', $day)
                ->delete();

            $lookup = ['summary_date' => $day];
            if ($cardBranchId) {
                $lookup['branch_id'] = $cardBranchId;
            }

            $summary = ExpenseSummary::query()->updateOrCreate(
                $lookup,
                [
                    'branch_id' => $cardBranchId,
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

            $income = $this->incomeForDate($day, $user, (int) ($row->branch_id ?? $row->expenseCard?->branch_id ?? 0) ?: null);
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

    /**
     * Generated Summary rows only (Expense Generate နှိပ်ပြီးသား).
     *
     * @return \Illuminate\Support\Collection<int, ExpenseSummary>
     */
    public function rowsForPeriod(string $from, string $to, ?int $branchId, $user = null)
    {
        $summaries = ExpenseSummary::query()
            ->with(['expenseCard.items'])
            ->whereBetween('summary_date', [$from, $to])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('summary_date')
            ->orderBy('id')
            ->get();

        $this->syncIncomeOnRows($summaries, $user);

        return $summaries->values();
    }

    /**
     * @return array<int, int>
     */
    public function branchRowCounts(string $from, string $to): array
    {
        return ExpenseSummary::query()
            ->whereBetween('summary_date', [$from, $to])
            ->whereNotNull('branch_id')
            ->where('branch_id', '>', 0)
            ->selectRaw('branch_id, COUNT(*) as total')
            ->groupBy('branch_id')
            ->pluck('total', 'branch_id')
            ->mapWithKeys(fn ($total, $branchId) => [(int) $branchId => (int) $total])
            ->all();
    }
}
