<?php

namespace App\Services;

use App\Models\DailyCheckInvoice;
use App\Models\DispatchOrderItem;
use App\Models\ExpenseCard;
use App\Models\ExpenseSummary;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ExpenseSummaryService
{
    public function __construct(
        private readonly DailyCheckListService $dailyCheckList
    ) {
    }

    /**
     * Income = settled OS DeliAmount for the card date, by parcel destination.
     * Summary / Income Card follow the branch that received the parcel — not OS home —
     * so Yangon / POL / Sagaing income leaves the Mandalay tab.
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

        // Load network-wide settled OS invoices for the day, then attribute
        // Income to parcel destination (not OS home branch).
        $fromInvoices = $this->dailyCheckList->osInvoiceRowsForDate($day, null);
        $rows = $fromInvoices->isNotEmpty()
            ? $fromInvoices
            : $this->dailyCheckList->listRows($day, $day, 'os', null, null, null);

        if ($branchId && $branchId > 0) {
            $rows = $this->scopeIncomeDeliToDestinationBranch($rows, $branchId);
        }

        return $rows;
    }

    /**
     * Recalculate each OS row's deli_amount using only parcels delivered to $branchId.
     *
     * @param  Collection<int, object>  $rows
     * @return Collection<int, object>
     */
    protected function scopeIncomeDeliToDestinationBranch(Collection $rows, int $branchId): Collection
    {
        $invoiceIds = $rows->pluck('id')->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($invoiceIds->isEmpty()) {
            return $rows;
        }

        $invoices = DailyCheckInvoice::query()
            ->whereIn('id', $invoiceIds->all())
            ->get(['id', 'item_ids'])
            ->keyBy('id');

        $allItemIds = $invoices
            ->flatMap(fn (DailyCheckInvoice $inv) => collect($inv->item_ids ?? [])->map(fn ($id) => (int) $id))
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $itemsById = DispatchOrderItem::query()
            ->whereIn('id', $allItemIds->all() ?: [0])
            ->get(['id', 'to_branch_id', 'from_branch_id', 'deli_amount'])
            ->keyBy('id');

        return $rows
            ->map(function ($row) use ($invoices, $itemsById, $branchId) {
                $inv = $invoices->get((int) ($row->id ?? 0));
                $deli = 0.0;
                foreach ((array) ($inv?->item_ids ?? []) as $rawId) {
                    $item = $itemsById->get((int) $rawId);
                    if (! $item) {
                        continue;
                    }
                    $dest = (int) ($item->to_branch_id ?: $item->from_branch_id ?: 0);
                    if ($dest !== $branchId) {
                        continue;
                    }
                    $deli += (float) ($item->deli_amount ?? 0);
                }
                $row->deli_amount = round($deli, 2);

                return $row;
            })
            ->filter(fn ($row) => (float) ($row->deli_amount ?? 0) > 0.0001)
            ->values();
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
     * Refresh stored Summary Income / AKO from live Daily Check OS DeliAmount
     * attributed by parcel destination (not OS home).
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
     * Repair rows that still look like the old OS-home Generate bug:
     * non-MDY branch with Expense > 0 but Income stuck at 0.
     * Cheap enough for Summary page load (only touches stale rows).
     *
     * @param  \Illuminate\Support\Collection<int, ExpenseSummary>  $rows
     * @return \Illuminate\Support\Collection<int, ExpenseSummary>
     */
    public function syncStaleZeroIncomeRows($rows, $user = null)
    {
        $stale = $rows->filter(function (ExpenseSummary $row) {
            return (float) ($row->income ?? 0) <= 0.0001
                && (float) ($row->expense ?? 0) > 0.0001
                && (int) ($row->branch_id ?? 0) > 0;
        })->values();

        if ($stale->isEmpty()) {
            return $rows;
        }

        $this->syncIncomeOnRows($stale, $user);

        return $rows;
    }

    /**
     * Generated Summary rows only (Expense Generate နှိပ်ပြီးသား).
     * Auto-repairs stale Income=0 rows (old OS-home Generate) by destination.
     * Full-period live sync stays opt-in — it was 10s+ and blocked SPA entry.
     *
     * @return \Illuminate\Support\Collection<int, ExpenseSummary>
     */
    public function rowsForPeriod(string $from, string $to, ?int $branchId, $user = null, bool $syncIncome = false)
    {
        $summaries = ExpenseSummary::query()
            ->with(['expenseCard.items'])
            ->whereBetween('summary_date', [$from, $to])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('summary_date')
            ->orderBy('id')
            ->get();

        if ($syncIncome) {
            $this->syncIncomeOnRows($summaries, $user);
        } else {
            $this->syncStaleZeroIncomeRows($summaries, $user);
        }

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
