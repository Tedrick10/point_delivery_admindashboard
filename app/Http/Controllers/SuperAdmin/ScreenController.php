<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\HrStaff;
use App\Services\HrPayrollService;
use App\Services\KyoShinService;
use App\Services\NetworkControlService;
use App\Services\AppTextService;
use App\Services\RiderRemitService;
use App\Services\SuperAdminDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScreenController extends Controller
{
    public function hub(Request $request, SuperAdminDashboardService $dashboard): View
    {
        $period = $dashboard->periodFromRequest($request);
        $screens = config('super_admin_screens', []);

        return view('super-admin.screens.hub', [
            'screens' => $screens,
            'monthLabel' => $period['label'],
            'saPeriod' => $period,
        ]);
    }

    public function show(Request $request, string $screen, SuperAdminDashboardService $dashboard): View
    {
        $screens = config('super_admin_screens', []);
        if (! isset($screens[$screen])) {
            abort(404);
        }

        $period = $dashboard->periodFromRequest($request);
        $ctx = $dashboard->screenContext($screen, $period);

        $lateFineDefaults = null;
        $lateFineStaff = collect();
        $lateFineSheet = null;
        $riderSalaryStaff = collect();
        $riderSalarySheet = null;
        $officeSalaryStaff = collect();
        $officeSalarySheet = null;
        $riderFuelStaff = collect();
        $riderFuelGroups = [];
        $deliveryRoute = null;
        $networkControl = null;
        $kyoShinControl = null;
        $expenseSummary = null;
        $welcomePromotion = null;
        $accountCreation = null;
        $rolesPermissions = null;
        $generalSetting = null;
        $apiServerSetting = null;
        $companyContact = null;
        $appStoreUpdate = null;
        $uiTheme = null;
        $appCopy = null;
        if ($screen === 'late-fine') {
            $payroll = app(HrPayrollService::class);
            $payroll->syncStaffFromAccounts();
            $lateFineDefaults = [
                'fine_per_minute' => $payroll->defaultFinePerMinute(),
                'absent_day_rate' => $payroll->defaultAbsentDayRate(),
            ];
            // Super Admin Late Fine: Mandalay riders only (not Office / other branches).
            $lateFineStaff = HrStaff::query()
                ->active()
                ->where('staff_group', 'rider')
                ->mandalayRiders()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'staff_group', 'allowance_minutes', 'sort_order']);

            $month = $payroll->parseMonth($request->get('month'));
            $rows = $payroll->ensureLateFineRows($month, 'rider');
            $mdyStaffIds = $lateFineStaff->pluck('id');
            $items = $payroll->lateFineItems($month)->filter(
                fn ($item) => $mdyStaffIds->contains($item->staff_id)
            )->values();
            $bagItems = $payroll->bagDeductionItems($month)->filter(
                fn ($item) => $mdyStaffIds->contains($item->staff_id)
            )->values();
            $totals = $payroll->lateFineTotals($rows, $items);
            $staffOptions = HrStaff::query()
                ->active()
                ->where('staff_group', 'rider')
                ->mandalayRiders()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'staff_group', 'user_id']);

            $lateFineSheet = array_merge($totals, [
                'items' => $items,
                'bagItems' => $bagItems,
                'sum_bag_deductions' => round((float) $bagItems->sum('amount'), 2),
                'staffOptions' => $staffOptions,
                'canEdit' => true,
                'prevMonth' => $month->copy()->subMonth()->format('Y-m'),
                'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
                'monthValue' => $month->format('Y-m'),
                'monthLabel' => $month->format('F Y'),
                'finePerMinuteDefault' => $payroll->defaultFinePerMinute(),
                'prevMonthUrl' => route('super-admin.screens.show', ['screen' => 'late-fine', 'month' => $month->copy()->subMonth()->format('Y-m')]),
                'nextMonthUrl' => route('super-admin.screens.show', ['screen' => 'late-fine', 'month' => $month->copy()->addMonth()->format('Y-m')]),
                'embedReturn' => 'super-admin',
            ]);
        } elseif ($screen === 'rider-salary') {
            $payroll = app(HrPayrollService::class);
            $payroll->syncStaffFromAccounts();
            $riderSalaryStaff = HrStaff::query()
                ->active()
                ->where('staff_group', 'rider')
                ->mandalayRiders()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'staff_group', 'way_rate', 'sort_order']);

            $month = $payroll->parseMonth($request->get('month'));
            $payroll->ensureLateFineRows($month, 'rider');
            $rows = $payroll->ensureRiderSalaryRows($month);
            $colTotals = [
                'way_count' => (int) $rows->sum('way_count'),
                'total_salary' => round((float) $rows->sum(fn ($r) => $r->total_salary), 2),
                'late_minute_amount' => round((float) $rows->sum('late_minute_amount'), 2),
                'fine_amount' => round((float) $rows->sum('fine_amount'), 2),
                'bag_deduction' => round((float) $rows->sum('bag_deduction'), 2),
                'deposit' => round((float) $rows->sum('deposit'), 2),
                'total_deduction' => round((float) $rows->sum(fn ($r) => $r->total_deduction), 2),
                'net_pay' => round((float) $rows->sum(fn ($r) => $r->net_pay), 2),
            ];
            $riderSalarySheet = [
                'rows' => $rows,
                'colTotals' => $colTotals,
                'sumWayPay' => $colTotals['total_salary'],
                'sumDeduction' => $colTotals['total_deduction'],
                'sumNetPay' => $colTotals['net_pay'],
                'canEdit' => true,
                'canEditDeposit' => true,
                'prevMonth' => $month->copy()->subMonth()->format('Y-m'),
                'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
                'monthValue' => $month->format('Y-m'),
                'monthLabel' => $month->format('F Y'),
                'prevMonthUrl' => route('super-admin.screens.show', ['screen' => 'rider-salary', 'month' => $month->copy()->subMonth()->format('Y-m')]),
                'nextMonthUrl' => route('super-admin.screens.show', ['screen' => 'rider-salary', 'month' => $month->copy()->addMonth()->format('Y-m')]),
            ];
        } elseif ($screen === 'office-salary') {
            $payroll = app(HrPayrollService::class);
            $payroll->syncStaffFromAccounts();
            $officeSalaryStaff = HrStaff::query()
                ->active()
                ->where('staff_group', 'office')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'staff_group', 'monthly_salary', 'sort_order']);

            $month = $payroll->parseMonth($request->get('month'));
            $payroll->ensureLateFineRows($month, 'office');
            $rows = $payroll->ensureOfficeSalaryRows($month);
            $daysInMonth = $month->daysInMonth;
            $dayBase = max(1, $daysInMonth - 3);
            $colTotals = [
                'monthly_salary' => round((float) $rows->sum('monthly_salary'), 2),
                'day_rate' => round((float) $rows->sum(fn ($r) => round($r->day_rate)), 2),
                'rest_days' => (int) $rows->sum('rest_days'),
                'worked_days' => (int) $rows->sum(fn ($r) => $r->worked_days),
                'total_salary' => round((float) $rows->sum(fn ($r) => $r->total_salary), 2),
                'late_minute_amount' => round((float) $rows->sum('late_minute_amount'), 2),
                'fine_amount' => round((float) $rows->sum('fine_amount'), 2),
                'bag_deduction' => round((float) $rows->sum('bag_deduction'), 2),
                'deposit' => round((float) $rows->sum('deposit'), 2),
                'total_deduction' => round((float) $rows->sum(fn ($r) => $r->total_deduction), 2),
                'net_pay' => round((float) $rows->sum(fn ($r) => $r->net_pay), 2),
            ];
            $officeSalarySheet = [
                'rows' => $rows,
                'colTotals' => $colTotals,
                'sumTotalSalary' => $colTotals['total_salary'],
                'sumDeduction' => $colTotals['total_deduction'],
                'sumNetPay' => $colTotals['net_pay'],
                'daysInMonth' => $daysInMonth,
                'dayBase' => $dayBase,
                'canEdit' => true,
                'canEditDeposit' => true,
                'prevMonth' => $month->copy()->subMonth()->format('Y-m'),
                'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
                'monthValue' => $month->format('Y-m'),
                'monthLabel' => $month->format('F Y'),
                'prevMonthUrl' => route('super-admin.screens.show', ['screen' => 'office-salary', 'month' => $month->copy()->subMonth()->format('Y-m')]),
                'nextMonthUrl' => route('super-admin.screens.show', ['screen' => 'office-salary', 'month' => $month->copy()->addMonth()->format('Y-m')]),
            ];
        } elseif ($screen === 'rider-remit') {
            $riderFuelGroups = app(RiderRemitService::class)->fuelControlRiderGroups();
            $riderFuelStaff = collect($riderFuelGroups)->flatMap(fn ($g) => $g['rows'])->unique('id')->values();
        } elseif ($screen === 'delivery-route') {
            $deliveryRoute = DeliveryRouteController::screenPayload($request);
        } elseif ($screen === 'network') {
            $networkControl = app(NetworkControlService::class)->payload();
        } elseif ($screen === 'kyo-shin') {
            $kyoShinControl = app(KyoShinService::class)->summaries();
        } elseif ($screen === 'expense-summary') {
            $expenseSummary = \App\Http\Controllers\ExpenseSummaryController::screenPayload($request);
        } elseif ($screen === 'welcome-promotion') {
            $welcomePromotion = WelcomePromotionController::screenPayload($request);
        } elseif ($screen === 'account-creation') {
            $accountCreation = AccountCreationController::screenPayload($request);
        } elseif ($screen === 'roles-permissions') {
            $rolesPermissions = RolesPermissionsController::screenPayload();
        } elseif ($screen === 'general-setting') {
            $generalSetting = SystemSettingsController::generalPayload();
        } elseif ($screen === 'company-contact') {
            $companyContact = SystemSettingsController::companyContactPayload();
        } elseif ($screen === 'api-server-setting') {
            $apiServerSetting = SystemSettingsController::apiServerPayload();
        } elseif ($screen === 'app-store-update') {
            $appStoreUpdate = SystemSettingsController::appStorePayload();
        } elseif ($screen === 'ui-theme') {
            $uiTheme = UiThemeController::screenPayload();
        } elseif ($screen === 'app-copy') {
            $appCopy = AppTextService::screenPayload(
                (string) $request->get('app', 'admin'),
                $request->get('q'),
                $request->get('screen_id'),
                max(1, (int) $request->get('page', 1)),
                40
            );
        }

        return view('super-admin.screens.show', [
            'screenKey' => $screen,
            'screen' => $screens[$screen],
            'metrics' => $ctx['metrics'],
            'branchRows' => $ctx['branchRows'],
            'monthLabel' => $ctx['monthLabel'],
            'saPeriod' => $period,
            'defaultFuel' => $screen === 'rider-remit'
                ? app(RiderRemitService::class)->defaultFuelAmount()
                : null,
            'fuelMinWays' => $screen === 'rider-remit'
                ? app(RiderRemitService::class)->fuelMinWays()
                : null,
            'defaultOfficeSalary' => in_array($screen, ['office-salary', 'network'], true)
                ? app(HrPayrollService::class)->defaultOfficeMonthlySalary()
                : null,
            'lateFineDefaults' => $lateFineDefaults,
            'lateFineStaff' => $lateFineStaff,
            'lateFineSheet' => $lateFineSheet,
            'riderSalaryStaff' => $riderSalaryStaff,
            'riderSalarySheet' => $riderSalarySheet,
            'officeSalaryStaff' => $officeSalaryStaff,
            'officeSalarySheet' => $officeSalarySheet,
            'riderFuelStaff' => $riderFuelStaff,
            'riderFuelGroups' => $riderFuelGroups,
            'deliveryRoute' => $deliveryRoute,
            'networkControl' => $networkControl,
            'kyoShinControl' => $kyoShinControl,
            'expenseSummary' => $expenseSummary,
            'welcomePromotion' => $welcomePromotion,
            'accountCreation' => $accountCreation,
            'rolesPermissions' => $rolesPermissions,
            'generalSetting' => $generalSetting,
            'apiServerSetting' => $apiServerSetting,
            'companyContact' => $companyContact,
            'appStoreUpdate' => $appStoreUpdate,
            'uiTheme' => $uiTheme,
            'appCopy' => $appCopy,
        ]);
    }
}
