<?php

namespace App\Services;

use App\Http\Controllers\SuperAdmin\BranchAdminController;
use App\Models\Branch;
use App\Models\DailyCheckInvoice;
use App\Models\DispatchOrderItem;
use App\Models\ExpenseCard;
use App\Models\ExpenseItem;
use App\Models\ExpenseSummary;
use App\Models\Order;
use App\Models\OsCashPayout;
use App\Models\OsMoneyTransfer;
use App\Models\OsReceiveSettlement;
use App\Models\OsSettlementBatch;
use App\Models\Payment;
use App\Models\Ratings;
use App\Models\RiderRemit;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WithdrawRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SuperAdminDashboardService
{
    private const TZ = 'Asia/Yangon';

    private const MAX_RANGE_DAYS = 366;

    public function monthContext(): array
    {
        $period = $this->periodFromRequest();

        return [
            'tz' => self::TZ,
            'today' => $period['today'],
            'monthStart' => $period['start'],
            'monthEnd' => $period['end'],
            'monthLabel' => $period['label'],
        ];
    }

    /**
     * Resolve stats period from query: month (default), day, or range.
     */
    public function periodFromRequest(?Request $request = null): array
    {
        $request = $request ?? request();
        $now = Carbon::now(self::TZ);
        $today = $now->toDateString();
        $mode = (string) $request->input('period', 'month');

        if ($mode === 'day') {
            $date = $this->parseDate($request->input('date'), $today);
            $parsed = Carbon::parse($date, self::TZ);

            return [
                'mode' => 'day',
                'start' => $date,
                'end' => $date,
                'label' => $parsed->locale(app()->getLocale())->translatedFormat('d M Y'),
                'today' => $today,
            ];
        }

        if ($mode === 'range') {
            $from = $this->parseDate($request->input('date_from'), $now->copy()->startOfMonth()->toDateString());
            $to = $this->parseDate($request->input('date_to'), $today);
            $startCarbon = Carbon::parse($from, self::TZ);
            $endCarbon = Carbon::parse($to, self::TZ);

            if ($startCarbon->gt($endCarbon)) {
                [$startCarbon, $endCarbon] = [$endCarbon, $startCarbon];
            }

            if ($startCarbon->diffInDays($endCarbon) > self::MAX_RANGE_DAYS) {
                $startCarbon = $endCarbon->copy()->subDays(self::MAX_RANGE_DAYS);
            }

            return [
                'mode' => 'range',
                'start' => $startCarbon->toDateString(),
                'end' => $endCarbon->toDateString(),
                'label' => $this->formatRangeLabel($startCarbon, $endCarbon),
                'today' => $today,
            ];
        }

        return [
            'mode' => 'month',
            'start' => $now->copy()->startOfMonth()->toDateString(),
            'end' => $today,
            'label' => $now->locale(app()->getLocale())->translatedFormat('F Y'),
            'today' => $today,
        ];
    }

    /**
     * Compact stats shared across all Super Admin screens (cached briefly).
     */
    public function sharedBar(?array $period = null): array
    {
        $period = $period ?? $this->periodFromRequest();
        $cacheKey = 'super_admin_shared_bar_'.app()->getLocale().'_'.md5(json_encode($period));

        return cache()->remember($cacheKey, 45, function () use ($period) {
            $build = $this->build($period);
            $s = $build['stats'];

            return [
                'period' => $period,
                'monthLabel' => $period['label'],
                'items_today' => $s['items_today'],
                'items_month' => $s['items_month'],
                'delivered_period' => $s['delivered_period'],
                'cod_pending' => $s['cod_pending'],
                'in_progress' => $s['in_progress'],
                'summary_income_month' => $s['summary_income_month'],
                'summary_ako_month' => $s['summary_ako_month'],
                'remit_combined_month' => $s['remit_combined_month'],
                'mt_combined_month' => $s['mt_combined_month'],
            ];
        });
    }

    public function screenContext(string $screen, ?array $period = null): array
    {
        $period = $period ?? $this->periodFromRequest();
        $build = $this->build($period);
        $s = $build['stats'];
        $byBranch = $build['byBranch'];
        $monthLabel = $build['monthLabel'];
        $periodTag = $this->periodTag($period);

        $metrics = match ($screen) {
            'dispatch' => [
                ['label' => __('message.sa_items_all'), 'value' => $s['items_total'], 'money' => false],
                ['label' => __('message.sa_in_progress'), 'value' => $s['in_progress'], 'money' => false],
                ['label' => __('message.delivered'), 'value' => $s['delivered_ui'], 'money' => false],
                ['label' => __('message.sa_unassigned'), 'value' => $s['unassigned'], 'money' => false],
                ['label' => __('message.sa_cod_pending'), 'value' => $s['cod_pending'], 'money' => true],
                ['label' => __('message.sa_deli')." ({$periodTag})", 'value' => $s['deli_month'], 'money' => true],
            ],
            'daily-check' => [
                ['label' => __('message.sa_pending_remit'), 'value' => $s['daily_check_pending'], 'money' => false],
                ['label' => __('message.sa_items')." ({$periodTag})", 'value' => $s['items_month'], 'money' => false],
                ['label' => __('message.sa_deli')." ({$periodTag})", 'value' => $s['deli_month'], 'money' => true],
                ['label' => __('message.sa_os_to_pay')." ({$periodTag})", 'value' => $s['os_to_pay_month'], 'money' => true],
            ],
            'money-transfer' => [
                ['label' => __('message.sa_money_total'), 'value' => $s['mt_cash_month'], 'money' => true],
                ['label' => __('message.sa_kpay_total'), 'value' => $s['mt_kpay_month'], 'money' => true],
                ['label' => __('message.sa_money_kpay'), 'value' => $s['mt_combined_month'], 'money' => true],
                ['label' => __('message.sa_freight'), 'value' => $s['mt_freight_month'], 'money' => true],
                ['label' => __('message.sa_os_rows'), 'value' => $s['mt_rows_month'], 'money' => false],
            ],
            'rider-remit' => [
                ['label' => __('message.sa_due')." ({$periodTag})", 'value' => $s['remit_month'], 'money' => true],
                ['label' => __('message.sa_money_total'), 'value' => $s['remit_cash_month'], 'money' => true],
                ['label' => __('message.sa_kpay_total'), 'value' => $s['remit_kpay_month'], 'money' => true],
                ['label' => __('message.sa_money_kpay'), 'value' => $s['remit_combined_month'], 'money' => true],
                ['label' => __('message.sa_balanced'), 'value' => $s['remit_balanced'].'/'.$s['remit_records'], 'money' => false, 'raw' => true],
            ],
            'cash-payout' => [
                ['label' => __('message.sa_open'), 'value' => $s['cash_payout_open'], 'money' => false],
                ['label' => __('message.sa_open_amount'), 'value' => $s['cash_payout_open_amount'], 'money' => true],
                ['label' => __('message.sa_done')." ({$periodTag})", 'value' => $s['cash_payout_done_month'], 'money' => true],
            ],
            'os-receive' => [
                ['label' => __('message.sa_status_pending'), 'value' => $s['receive_pending'], 'money' => false],
                ['label' => __('message.sa_pending_amount'), 'value' => $s['receive_pending_amount'], 'money' => true],
                ['label' => __('message.sa_received_period')." ({$periodTag})", 'value' => $s['receive_received_month'], 'money' => true],
                ['label' => __('message.sa_os_finished')." ({$periodTag})", 'value' => $s['settlement_month'], 'money' => true],
            ],
            'expenses' => [
                ['label' => __('message.sa_expenses')." ({$periodTag})", 'value' => $s['expense_month'], 'money' => true],
                ['label' => __('message.sa_cards'), 'value' => $s['expense_cards'], 'money' => false],
                ['label' => __('message.sa_summary_income'), 'value' => $s['summary_income_month'], 'money' => true],
                ['label' => __('message.sa_akos_given'), 'value' => $s['summary_ako_month'], 'money' => true],
            ],
            'expense-summary' => [
                ['label' => __('message.sa_income')." ({$periodTag})", 'value' => $s['summary_income_month'], 'money' => true],
                ['label' => __('message.sa_expenses')." ({$periodTag})", 'value' => $s['summary_expense_month'], 'money' => true],
                ['label' => __('message.sa_akos_given'), 'value' => $s['summary_ako_month'], 'money' => true],
                ['label' => __('message.sa_days_generated'), 'value' => $s['summary_days'], 'money' => false],
            ],
            'late-fine' => [
                ['label' => __('message.hr_allowance_minutes'), 'value' => __('message.sa_late_fine_metric_per_staff'), 'money' => false, 'raw' => true],
                ['label' => __('message.hr_fine_per_minute'), 'value' => app(\App\Services\HrPayrollService::class)->defaultFinePerMinute(), 'money' => true],
                ['label' => __('message.hr_absent_day_rate'), 'value' => app(\App\Services\HrPayrollService::class)->defaultAbsentDayRate(), 'money' => true],
            ],
            'rider-salary' => [
                ['label' => __('message.hr_way_rate'), 'value' => __('message.sa_late_fine_metric_per_staff'), 'money' => false, 'raw' => true],
                ['label' => __('message.hr_group_rider'), 'value' => \App\Models\HrStaff::query()->active()->where('staff_group', 'rider')->count(), 'money' => false],
            ],
            'office-salary' => [
                ['label' => __('message.hr_monthly_salary'), 'value' => __('message.sa_late_fine_metric_per_staff'), 'money' => false, 'raw' => true],
                ['label' => __('message.hr_group_office'), 'value' => \App\Models\HrStaff::query()->active()->where('staff_group', 'office')->count(), 'money' => false],
                ['label' => __('message.hr_day_rate'), 'value' => __('message.sa_office_day_rate_auto'), 'money' => false, 'raw' => true],
            ],
            default => [],
        };

        $colItems = __('message.sa_items');
        $colActive = __('message.active');
        $colDone = __('message.sa_done');
        $colCod = __('message.sa_cod');
        $colDeli = __('message.sa_deli');
        $colRemit = __('message.sa_remit');
        $colRiders = __('message.sa_riders');

        $branchRows = match ($screen) {
            'dispatch' => collect($byBranch)->map(fn ($r) => [
                'name' => $r['name'],
                'cols' => [
                    $colItems => number_format($r['items_total']),
                    $colActive => number_format($r['in_progress']),
                    $colDone => number_format($r['delivered']),
                    $colCod => number_format($r['cod'], 0).' Ks',
                ],
            ])->all(),
            'daily-check', 'money-transfer', 'rider-remit', 'expenses', 'expense-summary' => collect($byBranch)->map(fn ($r) => [
                'name' => $r['name'],
                'cols' => [
                    $colItems => number_format($r['items_month']),
                    $colDeli => number_format($r['deli_month'], 0).' Ks',
                    $colRemit => number_format($r['remit_month'], 0).' Ks',
                ],
            ])->all(),
            'cash-payout', 'os-receive' => collect($byBranch)->map(fn ($r) => [
                'name' => $r['name'],
                'cols' => [
                    $colItems => number_format($r['items_month']),
                    $colRiders => number_format($r['riders']),
                    $colCod => number_format($r['cod'], 0).' Ks',
                ],
            ])->all(),
            default => [],
        };

        return compact('metrics', 'branchRows', 'monthLabel', 'period');
    }

    public function build(?array $period = null): array
    {
        $period = $period ?? $this->periodFromRequest();
        $tz = self::TZ;
        $today = $period['today'];
        $monthStart = $period['start'];
        $monthEnd = $period['end'];
        $monthLabel = $period['label'];

        $branches = BranchAdminController::operationalBranches();
        $branchIds = $branches->pluck('id')->all() ?: [0];
        $branchAdmins = User::query()
            ->where('user_type', 'admin')
            ->whereNull('deleted_at')
            ->whereIn('branch_id', $branchIds)
            ->with('branch:id,name')
            ->get(['id', 'name', 'email', 'branch_id', 'status', 'contact_number']);
        $adminsByBranch = $branchAdmins->keyBy('branch_id');

        $itemBase = DispatchOrderItem::query()->whereNull('deleted_at');
        $monthItemsQuery = fn () => (clone $itemBase)
            ->whereBetween(DB::raw('DATE(created_at)'), [$monthStart, $monthEnd]);

        $statusCounts = (clone $itemBase)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $terminalStatuses = ['completed', 'cancelled', 'return', 'returned'];

        $deliveredUi = (clone $itemBase)
            ->where('status', 'completed')
            ->whereNull('admin_completed_at')
            ->count();

        $adminCompleted = (clone $itemBase)
            ->where('status', 'completed')
            ->whereNotNull('admin_completed_at')
            ->count();

        $cancelledItems = (clone $itemBase)
            ->whereIn('status', ['cancelled', 'return', 'returned'])
            ->count();

        $inProgress = (clone $itemBase)
            ->whereNotIn('status', $terminalStatuses)
            ->count();

        $unassigned = (clone $itemBase)
            ->whereNull('delivery_man_id')
            ->whereNotIn('status', $terminalStatuses)
            ->count();

        $todayItems = (clone $itemBase)->whereDate('created_at', $today)->count();
        $todayDelivered = (clone $itemBase)
            ->where('status', 'completed')
            ->whereNull('admin_completed_at')
            ->whereDate('updated_at', $today)
            ->count();

        $deliveredPeriod = (clone $itemBase)
            ->where('status', 'completed')
            ->whereNull('admin_completed_at')
            ->whereBetween(DB::raw('DATE(updated_at)'), [$monthStart, $monthEnd])
            ->count();

        $periodDayItems = $period['mode'] === 'day'
            ? (clone $itemBase)->whereDate('created_at', $monthStart)->count()
            : $todayItems;

        $monthItems = $monthItemsQuery()->count();

        $codPending = (float) (clone $itemBase)
            ->whereNull('admin_completed_at')
            ->sum(DB::raw('COALESCE(cust_get, 0)'));

        $deliMonth = (float) $monthItemsQuery()
            ->sum(DB::raw('COALESCE(deli_amount, 0)'));

        $itemValueMonth = (float) $monthItemsQuery()
            ->sum(DB::raw('COALESCE(item_value, 0)'));

        $osToPayMonth = (float) $monthItemsQuery()
            ->sum(DB::raw('COALESCE(os_to_pay, 0)'));

        $gateAmountMonth = (float) $monthItemsQuery()
            ->sum(DB::raw('COALESCE(gate_amount, 0)'));

        $advancePaidMonth = (float) $monthItemsQuery()
            ->sum(DB::raw('COALESCE(advance_paid, 0)'));

        $expenseMonth = 0.0;
        $expenseCards = 0;
        if (class_exists(ExpenseItem::class)) {
            $expenseMonth = (float) ExpenseItem::query()
                ->whereHas('card', function ($q) use ($monthStart, $monthEnd) {
                    $q->whereBetween('expense_date', [$monthStart, $monthEnd]);
                })
                ->sum('amount');
        }
        if (class_exists(ExpenseCard::class)) {
            $expenseCards = ExpenseCard::query()
                ->whereBetween('expense_date', [$monthStart, $monthEnd])
                ->count();
        }

        $summaryIncomeMonth = 0.0;
        $summaryExpenseMonth = 0.0;
        $summaryAkoMonth = 0.0;
        $summaryDays = 0;
        if (class_exists(ExpenseSummary::class)) {
            $summaryIncomeMonth = (float) ExpenseSummary::query()
                ->whereBetween('summary_date', [$monthStart, $monthEnd])
                ->sum('income');
            $summaryExpenseMonth = (float) ExpenseSummary::query()
                ->whereBetween('summary_date', [$monthStart, $monthEnd])
                ->sum('expense');
            $summaryAkoMonth = (float) ExpenseSummary::query()
                ->whereBetween('summary_date', [$monthStart, $monthEnd])
                ->sum('ako_given');
            $summaryDays = ExpenseSummary::query()
                ->whereBetween('summary_date', [$monthStart, $monthEnd])
                ->count();
        }

        $riders = User::query()->where('user_type', 'delivery_man')->whereNull('deleted_at');
        $clients = User::query()->where('user_type', 'client')->whereNull('deleted_at');

        $avgRating = Ratings::query()->avg('rating');
        $ratingCount = Ratings::query()->count();

        $remitQuery = RiderRemit::query()->whereBetween('remit_date', [$monthStart, $monthEnd]);
        $remitMonth = (float) (clone $remitQuery)->sum(DB::raw('COALESCE(due_amount, 0)'));
        $remitPrepaidMonth = (float) (clone $remitQuery)->sum(DB::raw('COALESCE(prepaid_amount, 0)'));
        $remitFuelMonth = (float) (clone $remitQuery)->sum(DB::raw('COALESCE(fuel_amount, 0)'));
        $remitFeeMonth = (float) (clone $remitQuery)->sum(DB::raw('COALESCE(fee_amount, 0)'));
        $remitKpayMonth = (float) (clone $remitQuery)->sum(DB::raw('COALESCE(kpay_amount, 0)'));
        $remitCashMonth = 0.0;
        $remitBalanced = 0;
        $remitRecords = (clone $remitQuery)->get();
        foreach ($remitRecords as $remit) {
            $remitCashMonth += $remit->cashTotal();
            if ($remit->isBalanced()) {
                $remitBalanced++;
            }
        }
        $remitCombinedMonth = round($remitCashMonth + $remitKpayMonth, 2);

        $mtQuery = OsMoneyTransfer::query()
            ->whereBetween(DB::raw('DATE(updated_at)'), [$monthStart, $monthEnd]);
        $mtCashMonth = (float) (clone $mtQuery)->sum(DB::raw('COALESCE(cash_amount, 0)'));
        $mtKpayMonth = (float) (clone $mtQuery)->sum(DB::raw('COALESCE(kpay_amount, 0)'));
        $mtFreightMonth = (float) (clone $mtQuery)->sum(DB::raw('COALESCE(freight_amount, 0)'));
        $mtCombinedMonth = round($mtCashMonth + $mtKpayMonth, 2);
        $mtRowsMonth = (clone $mtQuery)->count();

        $paymentMonth = (float) Payment::query()
            ->whereBetween(DB::raw('DATE(created_at)'), [$monthStart, $monthEnd])
            ->sum('total_amount');
        $commissionMonth = (float) Payment::query()
            ->whereBetween(DB::raw('DATE(created_at)'), [$monthStart, $monthEnd])
            ->sum('admin_commission');
        $paymentAll = (float) Payment::sum('total_amount');
        $commissionAll = (float) Payment::sum('admin_commission');
        $paymentsUnsettled = Payment::query()->where('is_settled', 0)->count();

        $settlementMonth = 0.0;
        $settlementCountMonth = 0;
        if (Schema::hasTable('os_settlement_batches')) {
            $settlementMonth = (float) OsSettlementBatch::query()
                ->whereBetween(DB::raw('DATE(finished_at)'), [$monthStart, $monthEnd])
                ->whereNotNull('finished_at')
                ->sum('amount');
            $settlementCountMonth = OsSettlementBatch::query()
                ->whereBetween(DB::raw('DATE(finished_at)'), [$monthStart, $monthEnd])
                ->whereNotNull('finished_at')
                ->count();
        }

        $receivePending = 0;
        $receivePendingAmount = 0.0;
        $receiveReceivedMonth = 0.0;
        if (Schema::hasTable('os_receive_settlements')) {
            $receivePending = OsReceiveSettlement::query()
                ->whereIn('status', [OsReceiveSettlement::STATUS_PENDING, OsReceiveSettlement::STATUS_WAITING])
                ->count();
            $receivePendingAmount = (float) OsReceiveSettlement::query()
                ->whereIn('status', [OsReceiveSettlement::STATUS_PENDING, OsReceiveSettlement::STATUS_WAITING])
                ->sum(DB::raw('ABS(COALESCE(amount, 0))'));
            $receiveReceivedMonth = (float) OsReceiveSettlement::query()
                ->where('status', OsReceiveSettlement::STATUS_RECEIVED)
                ->whereBetween(DB::raw('DATE(approved_at)'), [$monthStart, $monthEnd])
                ->sum(DB::raw('ABS(COALESCE(amount, 0))'));
        }

        $cashPayoutOpen = 0;
        $cashPayoutOpenAmount = 0.0;
        $cashPayoutDoneMonth = 0.0;
        if (Schema::hasTable('os_cash_payouts')) {
            $cashPayoutOpen = OsCashPayout::query()
                ->whereIn('status', [
                    OsCashPayout::STATUS_UNASSIGNED,
                    OsCashPayout::STATUS_ASSIGNED,
                    OsCashPayout::STATUS_PENDING,
                ])
                ->count();
            $cashPayoutOpenAmount = (float) OsCashPayout::query()
                ->whereIn('status', [
                    OsCashPayout::STATUS_UNASSIGNED,
                    OsCashPayout::STATUS_ASSIGNED,
                    OsCashPayout::STATUS_PENDING,
                ])
                ->sum('amount');
            $cashPayoutDoneMonth = (float) OsCashPayout::query()
                ->where('status', OsCashPayout::STATUS_DONE)
                ->whereBetween(DB::raw('DATE(done_at)'), [$monthStart, $monthEnd])
                ->sum('amount');
        }

        $dailyCheckPending = 0;
        if (Schema::hasTable('daily_check_invoices')) {
            $dailyCheckPending = DailyCheckInvoice::query()
                ->whereNull('remitted_date')
                ->count();
        }

        $walletTotal = 0.0;
        if (Schema::hasTable('wallets')) {
            $walletTotal = (float) Wallet::query()->whereNull('deleted_at')->sum('total_amount');
        }

        $withdrawPending = 0;
        $withdrawPendingAmount = 0.0;
        if (Schema::hasTable('withdraw_requests')) {
            $withdrawPending = WithdrawRequest::query()
                ->whereNull('deleted_at')
                ->where('status', 'pending')
                ->count();
            $withdrawPendingAmount = (float) WithdrawRequest::query()
                ->whereNull('deleted_at')
                ->where('status', 'pending')
                ->sum('amount');
        }

        $orderStatusCounts = Order::query()
            ->whereNull('deleted_at')
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $byBranch = $this->branchRows($branches, $adminsByBranch, $today, $monthStart, $monthEnd);

        $last7 = $this->dailySeries($period);

        $stats = [
            'branches' => $branches->count(),
            'active_branches' => $branches->where('status', 1)->count(),
            'branch_admins' => $branchAdmins->count(),
            'branches_without_admin' => $branches->filter(fn ($b) => ! $adminsByBranch->has($b->id))->count(),
            'orders' => Order::query()->whereNull('deleted_at')->count(),
            'orders_month' => Order::query()
                ->whereNull('deleted_at')
                ->whereBetween(DB::raw('DATE(created_at)'), [$monthStart, $monthEnd])
                ->count(),
            'items_total' => (clone $itemBase)->count(),
            'items_today' => $periodDayItems,
            'items_month' => $monthItems,
            'delivered_period' => $deliveredPeriod,
            'today_delivered' => $todayDelivered,
            'delivered_ui' => $deliveredUi,
            'admin_completed' => $adminCompleted,
            'cancelled_items' => $cancelledItems,
            'in_progress' => $inProgress,
            'unassigned' => $unassigned,
            'status_counts' => $statusCounts,
            'order_status_counts' => $orderStatusCounts,
            'riders' => (clone $riders)->count(),
            'riders_active' => (clone $riders)->where('status', 1)->count(),
            'clients' => (clone $clients)->count(),
            'clients_vip' => (clone $clients)->where('is_vip', 1)->count(),
            'cod_pending' => $codPending,
            'deli_month' => $deliMonth,
            'item_value_month' => $itemValueMonth,
            'os_to_pay_month' => $osToPayMonth,
            'gate_amount_month' => $gateAmountMonth,
            'advance_paid_month' => $advancePaidMonth,
            'expense_month' => $expenseMonth,
            'expense_cards' => $expenseCards,
            'summary_income_month' => $summaryIncomeMonth,
            'summary_expense_month' => $summaryExpenseMonth,
            'summary_ako_month' => $summaryAkoMonth,
            'summary_days' => $summaryDays,
            'payment_total' => $paymentAll,
            'payment_month' => $paymentMonth,
            'admin_commission' => $commissionAll,
            'admin_commission_month' => $commissionMonth,
            'payments_unsettled' => $paymentsUnsettled,
            'avg_rating' => $avgRating ? round((float) $avgRating, 2) : null,
            'rating_count' => $ratingCount,
            'remit_month' => $remitMonth,
            'remit_prepaid_month' => $remitPrepaidMonth,
            'remit_fuel_month' => $remitFuelMonth,
            'remit_fee_month' => $remitFeeMonth,
            'remit_cash_month' => round($remitCashMonth, 2),
            'remit_kpay_month' => $remitKpayMonth,
            'remit_combined_month' => $remitCombinedMonth,
            'remit_balanced' => $remitBalanced,
            'remit_records' => $remitRecords->count(),
            'mt_cash_month' => $mtCashMonth,
            'mt_kpay_month' => $mtKpayMonth,
            'mt_freight_month' => $mtFreightMonth,
            'mt_combined_month' => $mtCombinedMonth,
            'mt_rows_month' => $mtRowsMonth,
            'settlement_month' => $settlementMonth,
            'settlement_count_month' => $settlementCountMonth,
            'receive_pending' => $receivePending,
            'receive_pending_amount' => $receivePendingAmount,
            'receive_received_month' => $receiveReceivedMonth,
            'cash_payout_open' => $cashPayoutOpen,
            'cash_payout_open_amount' => $cashPayoutOpenAmount,
            'cash_payout_done_month' => $cashPayoutDoneMonth,
            'daily_check_pending' => $dailyCheckPending,
            'wallet_total' => $walletTotal,
            'withdraw_pending' => $withdrawPending,
            'withdraw_pending_amount' => $withdrawPendingAmount,
        ];

        return compact('stats', 'byBranch', 'last7', 'today', 'monthLabel', 'period');
    }

    private function parseDate(mixed $value, string $fallback): string
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $fallback;
        }

        try {
            return Carbon::parse($value, self::TZ)->toDateString();
        } catch (\Throwable) {
            return $fallback;
        }
    }

    private function formatRangeLabel(Carbon $start, Carbon $end): string
    {
        $locale = app()->getLocale();
        $start = $start->locale($locale);
        $end = $end->locale($locale);

        if ($start->toDateString() === $end->toDateString()) {
            return $start->translatedFormat('d M Y');
        }

        if ($start->year === $end->year && $start->month === $end->month) {
            return $start->translatedFormat('j').' – '.$end->translatedFormat('j M Y');
        }

        if ($start->year === $end->year) {
            return $start->translatedFormat('j M').' – '.$end->translatedFormat('j M Y');
        }

        return $start->translatedFormat('j M Y').' – '.$end->translatedFormat('j M Y');
    }

    private function periodTag(array $period): string
    {
        return match ($period['mode']) {
            'day' => __('message.sa_period_day'),
            'range' => __('message.sa_period_range'),
            default => __('message.sa_period_month'),
        };
    }

    private function branchRows(Collection $branches, Collection $adminsByBranch, string $today, string $monthStart, string $monthEnd): array
    {
        $rows = [];
        foreach ($branches as $branch) {
            $bid = (int) $branch->id;
            $branchItems = DispatchOrderItem::query()
                ->whereNull('deleted_at')
                ->where(function ($q) use ($bid) {
                    $q->where('from_branch_id', $bid)->orWhere('to_branch_id', $bid);
                });

            $monthBranchItems = (clone $branchItems)
                ->whereBetween(DB::raw('DATE(created_at)'), [$monthStart, $monthEnd]);

            $admin = $adminsByBranch->get($bid);
            $rows[] = [
                'id' => $bid,
                'name' => $branch->name,
                'status' => (int) $branch->status,
                'admin' => $admin ? [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'status' => (int) $admin->status,
                ] : null,
                'items_total' => (clone $branchItems)->count(),
                'items_today' => (clone $branchItems)->whereDate('created_at', $today)->count(),
                'items_month' => (clone $monthBranchItems)->count(),
                'delivered' => (clone $branchItems)
                    ->where('status', 'completed')
                    ->whereNull('admin_completed_at')
                    ->count(),
                'in_progress' => (clone $branchItems)
                    ->whereNotIn('status', ['completed', 'cancelled', 'return', 'returned'])
                    ->count(),
                'cancelled' => (clone $branchItems)
                    ->whereIn('status', ['cancelled', 'return', 'returned'])
                    ->count(),
                'riders' => User::query()
                    ->where('user_type', 'delivery_man')
                    ->where('branch_id', $bid)
                    ->whereNull('deleted_at')
                    ->count(),
                'cod' => (float) (clone $branchItems)
                    ->whereNull('admin_completed_at')
                    ->sum(DB::raw('COALESCE(cust_get, 0)')),
                'deli_month' => (float) (clone $monthBranchItems)
                    ->sum(DB::raw('COALESCE(deli_amount, 0)')),
                'remit_month' => (float) RiderRemit::query()
                    ->where('branch_id', $bid)
                    ->whereBetween('remit_date', [$monthStart, $monthEnd])
                    ->sum(DB::raw('COALESCE(due_amount, 0)')),
            ];
        }

        return $rows;
    }

    private function dailySeries(array $period): array
    {
        $tz = self::TZ;
        $end = Carbon::parse($period['end'], $tz);
        $start = Carbon::parse($period['start'], $tz);

        if ($period['mode'] === 'month') {
            return $this->lastSevenDays($tz);
        }

        $seriesStart = $start->copy();
        if ($start->diffInDays($end) > 31) {
            $seriesStart = $end->copy()->subDays(30);
            if ($seriesStart->lt($start)) {
                $seriesStart = $start->copy();
            }
        }

        $days = [];
        $cursor = $seriesStart->copy();
        while ($cursor->lte($end)) {
            $day = $cursor->toDateString();
            $days[] = $this->dayActivity($day);
            $cursor->addDay();
        }

        return $days;
    }

    private function lastSevenDays(string $tz): array
    {
        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::now($tz)->subDays($i)->toDateString();
            $days[] = $this->dayActivity($day);
        }

        return $days;
    }

    private function dayActivity(string $day): array
    {
        return [
            'date' => $day,
            'label' => Carbon::parse($day, self::TZ)->locale(app()->getLocale())->translatedFormat('d M'),
            'created' => DispatchOrderItem::query()->whereNull('deleted_at')->whereDate('created_at', $day)->count(),
            'delivered' => DispatchOrderItem::query()
                ->whereNull('deleted_at')
                ->where('status', 'completed')
                ->whereNull('admin_completed_at')
                ->whereDate('updated_at', $day)
                ->count(),
        ];
    }
}
