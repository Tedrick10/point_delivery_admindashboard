<?php

namespace App\Http\Controllers;

use App\Models\HrOfficeSalaryRow;
use App\Services\HrPayrollService;
use Illuminate\Http\Request;

class HrRiderSalaryController extends Controller
{
    public function index(Request $request, HrPayrollService $service)
    {
        if (! auth()->user()->can('hr-payroll-list') && auth()->user()->user_type !== 'admin') {
            return redirect()->route('home')->withErrors(__('message.demo_permission_denied'));
        }

        $month = $service->parseMonth($request->get('month'));
        $service->syncStaffFromAccounts();
        $service->ensureLateFineRows($month, 'rider');
        $rows = $service->ensureRiderSalaryRows($month);

        $pageTitle = __('message.hr_rider_salary_title');
        $assets = [];
        $canEdit = auth()->user()->can('hr-payroll-edit') || auth()->user()->user_type === 'admin';
        $prevMonth = $month->copy()->subMonth()->format('Y-m');
        $nextMonth = $month->copy()->addMonth()->format('Y-m');
        $monthValue = $month->format('Y-m');
        $monthLabel = $month->format('F Y');
        $sumWayPay = round((float) $rows->sum(fn ($r) => $r->way_pay), 2);
        $sumDeduction = round((float) $rows->sum(fn ($r) => $r->total_deduction), 2);
        $sumNetPay = round((float) $rows->sum(fn ($r) => $r->net_pay), 2);

        return view('hr.rider-salary', compact(
            'pageTitle',
            'assets',
            'canEdit',
            'prevMonth',
            'nextMonth',
            'monthValue',
            'monthLabel',
            'rows',
            'sumWayPay',
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
        if (($row->staff?->staff_group ?? '') !== 'rider') {
            return response()->json(['success' => false, 'message' => __('message.demo_permission_denied')], 403);
        }

        $data = $request->validate([
            'way_count' => 'nullable|integer|min:0',
            'late_minute_amount' => 'nullable|numeric',
            'fine_amount' => 'nullable|numeric|min:0',
            'bag_deduction' => 'nullable|numeric|min:0',
            'personal_expense' => 'nullable|numeric|min:0',
            'deposit' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        // 1 way စာ → Super Admin only (read-only on this sheet)
        foreach ($data as $key => $value) {
            if ($value !== null) {
                $row->{$key} = $value;
            }
        }
        $row->save();

        return response()->json([
            'success' => true,
            'message' => __('message.update_form', ['form' => __('message.hr_rider_salary_title')]),
            'data' => [
                'id' => $row->id,
                'way_pay' => $row->way_pay,
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
        $service->ensureLateFineRows($month, 'rider');
        $service->ensureRiderSalaryRows($month);
        $count = $service->syncSalaryDeductionsFromLateFine($month, 'rider');

        return redirect()->route('hr.rider-salary.index', ['month' => $month->format('Y-m')])
            ->withSuccess(__('message.hr_salary_synced', ['count' => $count]));
    }
}
