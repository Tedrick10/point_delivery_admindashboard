<?php

namespace App\Http\Controllers;

use App\Models\ExpenseSummary;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ExpenseSummaryController extends Controller
{
    public function index(Request $request)
    {
        if (! auth()->user()->can('order-list')) {
            return redirect()->route('home')->withErrors(__('message.demo_permission_denied'));
        }

        $monthParam = trim((string) $request->get('month', ''));
        try {
            $month = $monthParam !== ''
                ? Carbon::createFromFormat('Y-m', $monthParam, 'Asia/Yangon')->startOfMonth()
                : now('Asia/Yangon')->startOfMonth();
        } catch (\Throwable $e) {
            $month = now('Asia/Yangon')->startOfMonth();
        }

        $from = $month->copy()->startOfMonth()->toDateString();
        $to = $month->copy()->endOfMonth()->toDateString();

        $fromInput = trim((string) $request->get('from_date', ''));
        $toInput = trim((string) $request->get('to_date', ''));
        if ($fromInput !== '') {
            $parsed = $this->parseDate($fromInput);
            if ($parsed) {
                $from = $parsed;
            }
        }
        if ($toInput !== '') {
            $parsed = $this->parseDate($toInput);
            if ($parsed) {
                $to = $parsed;
            }
        }
        if ($to < $from) {
            $to = $from;
        }

        $rows = ExpenseSummary::query()
            ->with(['expenseCard.items'])
            ->whereBetween('summary_date', [$from, $to])
            ->orderBy('summary_date')
            ->orderBy('id')
            ->get();

        $summaryService = app(\App\Services\ExpenseSummaryService::class);
        $authUser = auth()->user();
        $rows = $summaryService->syncIncomeOnRows($rows, $authUser);

        $incomeItemsByDate = [];
        foreach ($rows as $row) {
            $day = $row->summary_date?->toDateString();
            if (! $day || isset($incomeItemsByDate[$day])) {
                continue;
            }
            $incomeItemsByDate[$day] = $summaryService->incomeCardItemsForDate($day, $authUser);
        }

        $pageTitle = __('message.expense_summary_title');
        $assets = [];
        $prevMonth = $month->copy()->subMonth()->format('Y-m');
        $nextMonth = $month->copy()->addMonth()->format('Y-m');
        $monthLabel = $month->format('M Y');
        $monthValue = $month->format('Y-m');
        $filterFrom = Carbon::parse($from)->format('d-m-Y');
        $filterTo = Carbon::parse($to)->format('d-m-Y');
        $totalIncome = round((float) $rows->sum('income'), 2);
        $totalExpense = round((float) $rows->sum('expense'), 2);
        $totalAko = round((float) $rows->sum('ako_given'), 2);

        return view('order.expense-summary', compact(
            'pageTitle',
            'assets',
            'rows',
            'incomeItemsByDate',
            'prevMonth',
            'nextMonth',
            'monthLabel',
            'monthValue',
            'filterFrom',
            'filterTo',
            'totalIncome',
            'totalExpense',
            'totalAko'
        ));
    }

    private function parseDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        foreach (['d-m-Y', 'Y-m-d', 'd/m/Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value, 'Asia/Yangon')->toDateString();
            } catch (\Throwable $e) {
                // try next
            }
        }
        try {
            return Carbon::parse($value, 'Asia/Yangon')->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
