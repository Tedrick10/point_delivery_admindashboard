<?php

namespace App\Http\Controllers;

use App\Services\ExpenseSummaryTripleCheckService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseSummaryController extends Controller
{
    public function index(Request $request)
    {
        if (! auth()->user()?->can('order-list')) {
            return redirect()->route('home')->withErrors(__('message.demo_permission_denied'));
        }

        $month = $this->resolvedMonth($request);
        $from = $month->copy()->startOfMonth()->toDateString();
        $to = $month->copy()->endOfMonth()->toDateString();
        $request->merge([
            'from_date' => $request->filled('from_date') ? $request->get('from_date') : Carbon::parse($from)->format('d-m-Y'),
            'to_date' => $request->filled('to_date') ? $request->get('to_date') : Carbon::parse($to)->format('d-m-Y'),
        ]);

        $payload = self::screenPayload($request);
        $pageTitle = __('message.expense_summary_title');
        $assets = [];
        $prevMonth = $month->copy()->subMonth()->format('Y-m');
        $nextMonth = $month->copy()->addMonth()->format('Y-m');
        $monthLabel = $month->format('M Y');
        $monthValue = $month->format('Y-m');

        return view('order.expense-summary', $payload + compact(
            'pageTitle',
            'assets',
            'prevMonth',
            'nextMonth',
            'monthLabel',
            'monthValue'
        ));
    }

    /**
     * Super Admin Summary table payload (generated rows only).
     *
     * @return array<string, mixed>
     */
    public static function screenPayload(Request $request): array
    {
        $period = app(\App\Services\SuperAdminDashboardService::class)->periodFromRequest($request);
        $from = (string) ($period['start'] ?? now('Asia/Yangon')->startOfMonth()->toDateString());
        $to = (string) ($period['end'] ?? now('Asia/Yangon')->toDateString());

        $fromInput = trim((string) $request->get('from_date', ''));
        $toInput = trim((string) $request->get('to_date', ''));
        if ($fromInput !== '') {
            $parsed = self::parseDate($fromInput);
            if ($parsed) {
                $from = $parsed;
            }
        }
        if ($toInput !== '') {
            $parsed = self::parseDate($toInput);
            if ($parsed) {
                $to = $parsed;
            }
        }
        if ($to < $from) {
            $to = $from;
        }

        [$branchId, $branchFilter, $branches] = resolveDestinationBranchFilter($request);
        $summaryService = app(\App\Services\ExpenseSummaryService::class);
        $authUser = auth()->user();
        $rows = $summaryService->rowsForPeriod($from, $to, $branchId, $authUser);

        $incomeItemsByDate = [];
        foreach ($rows as $row) {
            $day = $row->summary_date?->toDateString();
            if (! $day || isset($incomeItemsByDate[$day])) {
                continue;
            }
            $incomeItemsByDate[$day] = $summaryService->incomeCardItemsForDate($day, $authUser, $branchId);
        }

        $branchTabCounts = $summaryService->branchRowCounts($from, $to);
        $allBranchCount = array_sum($branchTabCounts);

        $month = self::resolvedMonthFromRequest($request, $from);
        $triple = app(ExpenseSummaryTripleCheckService::class);
        $monthStart = $month->copy()->startOfMonth()->toDateString();
        $monthEnd = $month->copy()->endOfMonth()->toDateString();
        $tripleChecks = $triple->checksBetween($monthStart, $monthEnd);
        $checkerKey = $triple->checkerKeyForUser($authUser);
        $calendarDays = $triple->calendarGrid($month, $from, $to, $tripleChecks);

        return [
            'rows' => $rows,
            'incomeItemsByDate' => $incomeItemsByDate,
            'totalIncome' => round((float) $rows->sum('income'), 2),
            'totalExpense' => round((float) $rows->sum('expense'), 2),
            'totalAko' => round((float) $rows->sum('ako_given'), 2),
            'branchFilter' => $branchFilter,
            'branches' => $branches,
            'branchTabs' => $branches,
            'branchTabCounts' => $branchTabCounts,
            'allBranchCount' => $allBranchCount,
            'selectedBranchId' => $branchId,
            'filterFrom' => Carbon::parse($from, 'Asia/Yangon')->format('d-m-Y'),
            'filterTo' => Carbon::parse($to, 'Asia/Yangon')->format('d-m-Y'),
            'selectedFromYmd' => $from,
            'selectedToYmd' => $to,
            'calendarMonth' => $month->format('Y-m'),
            'calendarMonthLabel' => $month->format('M Y'),
            'calendarPrevMonth' => $month->copy()->subMonth()->format('Y-m'),
            'calendarNextMonth' => $month->copy()->addMonth()->format('Y-m'),
            'calendarNextDisabled' => $month->copy()->addMonth()->startOfMonth()->gt(now('Asia/Yangon')->startOfDay()),
            'calendarToday' => now('Asia/Yangon')->toDateString(),
            'calendarDays' => $calendarDays,
            'calendarWeekdays' => $triple->weekdayLabels(),
            'tripleCheckers' => $triple->checkerMeta(),
            'tripleCanConfirm' => $checkerKey !== null,
            'tripleCheckerKey' => $checkerKey,
            'tripleAlreadyConfirmed' => $checkerKey
                ? $triple->selectedAlreadyConfirmed($from, $to, $checkerKey, $tripleChecks)
                : false,
            'tripleConfirmUrl' => request()->routeIs('super-admin.*')
                ? route('super-admin.expense-summary.confirm')
                : route('order.expense-summary.confirm'),
        ];
    }

    public function confirm(Request $request): JsonResponse
    {
        $user = auth()->user();
        if (! $user || (! $user->can('order-list') && ! (function_exists('isSuperAdmin') && isSuperAdmin($user)))) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        $from = self::parseDate((string) $request->input('from_date', ''))
            ?? now('Asia/Yangon')->toDateString();
        $to = self::parseDate((string) $request->input('to_date', '')) ?: $from;
        $result = app(ExpenseSummaryTripleCheckService::class)->confirmRange($user, $from, $to);

        return response()->json([
            'success' => true,
            'message' => __('message.expense_summary_confirmed'),
            'checker_key' => $result['key'],
            'dates' => $result['dates'],
            'checks' => $result['checks'],
        ]);
    }

    protected function resolvedMonth(Request $request): Carbon
    {
        return self::resolvedMonthFromRequest($request);
    }

    private static function resolvedMonthFromRequest(Request $request, ?string $fallbackDate = null): Carbon
    {
        $raw = trim((string) $request->get('month', ''));
        if ($raw !== '') {
            try {
                return Carbon::createFromFormat('Y-m', $raw, 'Asia/Yangon')->startOfMonth();
            } catch (\Throwable $e) {
                // fall through
            }
        }

        if ($fallbackDate) {
            try {
                return Carbon::parse($fallbackDate, 'Asia/Yangon')->startOfMonth();
            } catch (\Throwable $e) {
                // fall through
            }
        }

        return now('Asia/Yangon')->startOfMonth();
    }

    private static function parseDate(string $value): ?string
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
