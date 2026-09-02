<?php

namespace App\Http\Controllers;

use App\Models\HrLateFineItem;
use App\Models\HrLateFineRow;
use App\Models\HrStaff;
use App\Services\HrPayrollService;
use Illuminate\Http\Request;

class HrLateFineController extends Controller
{
    public function index(Request $request, HrPayrollService $service)
    {
        if (! auth()->user()->can('hr-payroll-list') && auth()->user()->user_type !== 'admin') {
            return redirect()->route('home')->withErrors(__('message.demo_permission_denied'));
        }

        $month = $service->parseMonth($request->get('month'));
        $service->syncStaffFromAccounts();
        $rows = $service->ensureLateFineRows($month); // All: office + rider
        $items = $service->lateFineItems($month);
        $totals = $service->lateFineTotals($rows, $items);

        $staffOptions = HrStaff::query()
            ->with('user')
            ->active()
            ->orderBy('staff_group')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'staff_group', 'user_id']);
        $pageTitle = __('message.hr_late_fine_title');
        $assets = [];
        $canEdit = auth()->user()->can('hr-payroll-edit') || auth()->user()->user_type === 'admin';
        $prevMonth = $month->copy()->subMonth()->format('Y-m');
        $nextMonth = $month->copy()->addMonth()->format('Y-m');
        $monthValue = $month->format('Y-m');
        $monthLabel = $month->format('F Y');
        $finePerMinuteDefault = $service->defaultFinePerMinute();

        return view('hr.late-fine', array_merge(compact(
            'pageTitle',
            'assets',
            'canEdit',
            'prevMonth',
            'nextMonth',
            'monthValue',
            'monthLabel',
            'items',
            'staffOptions',
            'finePerMinuteDefault'
        ), $totals));
    }

    public function updateRow(Request $request, $id, HrPayrollService $service)
    {
        if (! auth()->user()->can('hr-payroll-edit') && auth()->user()->user_type !== 'admin') {
            return response()->json(['success' => false, 'message' => __('message.demo_permission_denied')], 403);
        }

        $row = HrLateFineRow::findOrFail($id);
        $data = $request->validate([
            'late_minutes' => 'nullable|integer|min:0|max:100000',
            'absent_dates' => 'nullable|string|max:500',
            'absent_days' => 'nullable|integer|min:0|max:31',
            'way_amount' => 'nullable|numeric|min:0',
            'ako_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        // အခွင့်အရေး / 1မိနစ် rate / Finger Print day rate → Super Admin only (read-only here)
        $row->fill([
            'late_minutes' => (int) ($data['late_minutes'] ?? $row->late_minutes),
            'way_amount' => (float) ($data['way_amount'] ?? $row->way_amount),
            'ako_amount' => (float) ($data['ako_amount'] ?? $row->ako_amount),
            'notes' => $data['notes'] ?? $row->notes,
        ]);

        if (array_key_exists('absent_dates', $data)) {
            $row->absent_dates = $service->normalizeAbsentDates($data['absent_dates']);
            $row->absent_days = $row->absent_dates !== ''
                ? $service->countAbsentDates($row->absent_dates)
                : (int) ($data['absent_days'] ?? 0);
        } elseif (array_key_exists('absent_days', $data)) {
            $row->absent_days = (int) $data['absent_days'];
        }
        $row->save();
        $row->load('staff');

        $month = $service->parseMonth($row->period_month->format('Y-m'));
        $group = $row->staff?->staff_group;
        if (in_array($group, ['office', 'rider'], true)) {
            $service->ensureSalaryRows($month, $group);
        } else {
            $service->syncSalaryDeductionsFromLateFine($month);
        }

        return response()->json([
            'success' => true,
            'message' => __('message.update_form', ['form' => __('message.hr_late_fine_title')]),
            'data' => [
                'id' => $row->id,
                'fine_minutes' => $row->fine_minutes,
                'late_fine_amount' => $row->late_fine_amount,
                'absent_dates' => $row->absent_dates,
                'absent_days' => $row->absent_days,
                'absent_fine_amount' => $row->absent_fine_amount,
                'total_fine' => $row->total_fine,
                'grand_total' => $row->total_fine,
            ],
        ]);
    }

    public function storeItem(Request $request, HrPayrollService $service)
    {
        if (! auth()->user()->can('hr-payroll-add') && auth()->user()->user_type !== 'admin') {
            return redirect()->back()->withErrors(__('message.demo_permission_denied'));
        }

        $data = $request->validate([
            'month' => 'required|date_format:Y-m',
            'staff_id' => 'nullable|exists:hr_staff,id',
            'staff_code' => 'nullable|string|max:50',
            'description' => 'required|string|max:500',
            'amount' => 'required|numeric|min:0',
        ]);

        $month = $service->parseMonth($data['month']);
        $staff = ! empty($data['staff_id']) ? HrStaff::find($data['staff_id']) : null;
        if (! $staff && ! empty($data['staff_code'])) {
            $staff = HrStaff::where('code', strtoupper(trim($data['staff_code'])))->first();
        }

        HrLateFineItem::create([
            'period_month' => $month->toDateString(),
            'staff_id' => $staff?->id,
            'staff_code' => $staff?->code ?? strtoupper(trim((string) ($data['staff_code'] ?? ''))),
            'description' => trim($data['description']),
            'amount' => (float) $data['amount'],
            'sort_order' => (int) HrLateFineItem::whereDate('period_month', $month->toDateString())->max('sort_order') + 1,
        ]);

        $group = $staff?->staff_group;
        if (in_array($group, ['office', 'rider'], true)) {
            $service->ensureSalaryRows($month, $group);
        } else {
            $service->syncSalaryDeductionsFromLateFine($month);
        }

        return redirect()->route('hr.late-fine.index', ['month' => $month->format('Y-m')])
            ->withSuccess(__('message.save_form', ['form' => __('message.hr_late_fine_item')]));
    }

    public function destroyItem($id, HrPayrollService $service)
    {
        if (! auth()->user()->can('hr-payroll-delete') && auth()->user()->user_type !== 'admin') {
            return redirect()->back()->withErrors(__('message.demo_permission_denied'));
        }

        $item = HrLateFineItem::with('staff')->findOrFail($id);
        $month = $service->parseMonth($item->period_month->format('Y-m'));
        $group = $item->staff?->staff_group;
        $item->delete();

        if (in_array($group, ['office', 'rider'], true)) {
            $service->ensureSalaryRows($month, $group);
        } else {
            $service->syncSalaryDeductionsFromLateFine($month);
        }

        return redirect()->route('hr.late-fine.index', ['month' => $month->format('Y-m')])
            ->withSuccess(__('message.delete_form', ['form' => __('message.hr_late_fine_item')]));
    }
}
