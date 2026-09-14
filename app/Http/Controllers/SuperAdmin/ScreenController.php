<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\HrStaff;
use App\Services\HrPayrollService;
use App\Services\KyoShinService;
use App\Services\NetworkControlService;
use App\Services\RiderRemitService;
use App\Services\SuperAdminDashboardService;
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
        $riderSalaryStaff = collect();
        $officeSalaryStaff = collect();
        $riderFuelStaff = collect();
        $riderFuelGroups = [];
        $deliveryRoute = null;
        $networkControl = null;
        $kyoShinControl = null;
        if ($screen === 'late-fine') {
            $payroll = app(HrPayrollService::class);
            $lateFineDefaults = [
                'fine_per_minute' => $payroll->defaultFinePerMinute(),
                'absent_day_rate' => $payroll->defaultAbsentDayRate(),
            ];
            $lateFineStaff = HrStaff::query()
                ->active()
                ->orderBy('staff_group')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'staff_group', 'allowance_minutes', 'sort_order']);
        } elseif ($screen === 'rider-salary') {
            $payroll = app(HrPayrollService::class);
            $payroll->syncStaffFromAccounts();
            $riderSalaryStaff = HrStaff::query()
                ->active()
                ->where('staff_group', 'rider')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'staff_group', 'way_rate', 'sort_order']);
        } elseif ($screen === 'office-salary') {
            $payroll = app(HrPayrollService::class);
            $payroll->syncStaffFromAccounts();
            $officeSalaryStaff = HrStaff::query()
                ->active()
                ->where('staff_group', 'office')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'staff_group', 'monthly_salary', 'sort_order']);
        } elseif ($screen === 'rider-remit') {
            $riderFuelGroups = app(RiderRemitService::class)->fuelControlRiderGroups();
            $riderFuelStaff = collect($riderFuelGroups)->flatMap(fn ($g) => $g['rows'])->unique('id')->values();
        } elseif ($screen === 'delivery-route') {
            $deliveryRoute = DeliveryRouteController::screenPayload($request);
        } elseif ($screen === 'network') {
            $networkControl = app(NetworkControlService::class)->payload();
        } elseif ($screen === 'kyo-shin') {
            $kyoShinControl = app(KyoShinService::class)->summaries();
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
            'defaultOfficeSalary' => in_array($screen, ['office-salary', 'network'], true)
                ? app(HrPayrollService::class)->defaultOfficeMonthlySalary()
                : null,
            'lateFineDefaults' => $lateFineDefaults,
            'lateFineStaff' => $lateFineStaff,
            'riderSalaryStaff' => $riderSalaryStaff,
            'officeSalaryStaff' => $officeSalaryStaff,
            'riderFuelStaff' => $riderFuelStaff,
            'riderFuelGroups' => $riderFuelGroups,
            'deliveryRoute' => $deliveryRoute,
            'networkControl' => $networkControl,
            'kyoShinControl' => $kyoShinControl,
        ]);
    }
}
