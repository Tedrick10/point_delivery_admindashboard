<?php

namespace App\Http\Controllers;

use App\Models\HrOfficeSalaryRow;
use App\Services\HrPayrollService;
use Illuminate\Http\Request;

class HrOfficeSalaryController extends Controller
{
    public function index(Request $request, HrPayrollService $service)
    {
        if (! auth()->user()->can('hr-payroll-list') && auth()->user()->user_type !== 'admin') {
            return redirect()->route('home')->withErrors(__('message.demo_permission_denied'));
        }

        $month = $service->parseMonth($request->get('month'));
        $service->syncStaffFromAccounts();
        $service->ensureLateFineRows($month, 'office');
        $rows = $service->ensureOfficeSalaryRows($month);

        $pageTitle = __('message.hr_office_salary_title');
        $assets = [];
        $canEdit = auth()->user()->can('hr-payroll-edit') || auth()->user()->user_type === 'admin';
        $prevMonth = $month->copy()->subMonth()->format('Y-m');
        $nextMonth = $month->copy()->addMonth()->format('Y-m');
        $monthValue = $month->format('Y-m');
        $monthLabel = $month->format('F Y');
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
        $sumTotalSalary = $colTotals['total_salary'];
        $sumDeduction = $colTotals['total_deduction'];
        $sumNetPay = $colTotals['net_pay'];

        return view('hr.office-salary', compact(
            'pageTitle',
            'assets',
            'canEdit',
            'prevMonth',
            'nextMonth',
            'monthValue',
            'monthLabel',
            'daysInMonth',
            'dayBase',
            'rows',
            'colTotals',
            'sumTotalSalary',
            'sumDeduction',
            'sumNetPay'
        ));
    }

    public function updateRow(Request $request, $id)
    {
        if (! auth()->user()->can('hr-payroll-edit') && auth()->user()->user_type !== 'admin') {
            return response()->json(['success' => false, 'message' => __('message.demo_permission_denied')], 403);
        }

        $row = HrOfficeSalaryRow::with('staff')->findOrFail($id);
        if (($row->staff?->staff_group ?? 'office') !== 'office') {
            return response()->json(['success' => false, 'message' => __('message.demo_permission_denied')], 403);
        }

        $data = $request->validate([
            'deposit' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        foreach ($data as $key => $value) {
            if ($value !== null) {
                $row->{$key} = $value;
            }
        }
        // monthly_salary / day rate → Super Admin
        // rest_days → Employee List Off/On; late_minute / fine / bag → Late Fine sync
        $row->way_count = 0;
        $row->way_rate = 0;
        $row->personal_expense = 0;
        $row->save();

        return response()->json([
            'success' => true,
            'message' => __('message.update_form', ['form' => __('message.hr_office_salary_title')]),
            'data' => [
                'id' => $row->id,
                'day_rate' => round($row->day_rate, 0),
                'worked_days' => $row->worked_days,
                'total_salary' => $row->total_salary,
                'total_deduction' => $row->total_deduction,
                'net_pay' => $row->net_pay,
            ],
        ]);
    }

    public function syncFromLateFine(Request $request, HrPayrollService $service)
    {
        if (! auth()->user()->can('hr-payroll-edit') && auth()->user()->user_type !== 'admin') {
            return redirect()->back()->withErrors(__('message.demo_permission_denied'));
        }

        $month = $service->parseMonth($request->get('month'));
        $service->syncStaffFromAccounts();
        $service->ensureLateFineRows($month, 'office');
        $service->ensureOfficeSalaryRows($month);
        $count = $service->syncSalaryDeductionsFromLateFine($month, 'office');

        return redirect()->route('hr.office-salary.index', ['month' => $month->format('Y-m')])
            ->withSuccess(__('message.hr_salary_synced', ['count' => $count]));
    }
}
