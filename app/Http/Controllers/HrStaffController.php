<?php

namespace App\Http\Controllers;

use App\Models\HrStaff;
use App\Services\HrPayrollService;
use Illuminate\Http\Request;

class HrStaffController extends Controller
{
    public function index()
    {
        return redirect()->route('hr.late-fine.index');
    }

    public function store(Request $request, HrPayrollService $service)
    {
        if (! auth()->user()->can('hr-payroll-add') && auth()->user()->user_type !== 'admin') {
            return redirect()->back()->withErrors(__('message.demo_permission_denied'));
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'staff_group' => 'required|in:office,rider',
            'monthly_salary' => 'nullable|numeric|min:0',
            'allowance_minutes' => 'nullable|integer|min:0|max:600',
            'branch_id' => 'nullable|exists:branches,id',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'nullable|in:0,1',
            'redirect_to' => 'nullable|in:late-fine,office-salary',
            'month' => 'nullable|date_format:Y-m',
        ]);

        $service->createStaff($data);

        $month = $data['month'] ?? now('Asia/Yangon')->format('Y-m');
        if (($data['redirect_to'] ?? '') === 'office-salary') {
            return redirect()->route('hr.office-salary.index', ['month' => $month])
                ->withSuccess(__('message.save_form', ['form' => __('message.name')]));
        }

        return redirect()->route('hr.late-fine.index', [
            'month' => $month,
            'staff_group' => $data['staff_group'],
        ])->withSuccess(__('message.save_form', ['form' => __('message.name')]));
    }

    public function update(Request $request, $id)
    {
        if (! auth()->user()->can('hr-payroll-edit') && auth()->user()->user_type !== 'admin') {
            return redirect()->back()->withErrors(__('message.demo_permission_denied'));
        }

        $staff = HrStaff::findOrFail($id);
        $data = $request->validate([
            'code' => 'required|string|max:50|unique:hr_staff,code,'.$staff->id,
            'name' => 'required|string|max:255',
            'staff_group' => 'required|in:office,rider',
            'monthly_salary' => 'nullable|numeric|min:0',
            'allowance_minutes' => 'nullable|integer|min:0|max:600',
            'branch_id' => 'nullable|exists:branches,id',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'nullable|in:0,1',
        ]);

        $staff->update([
            'code' => strtoupper(trim($data['code'])),
            'name' => trim($data['name']),
            'staff_group' => $data['staff_group'],
            'monthly_salary' => (float) ($data['monthly_salary'] ?? 0),
            'allowance_minutes' => (int) ($data['allowance_minutes'] ?? 60),
            'branch_id' => $data['branch_id'] ?: null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'status' => (int) ($data['status'] ?? 1),
        ]);

        return redirect()->route('hr.staff.index', ['staff_group' => $staff->staff_group])
            ->withSuccess(__('message.update_form', ['form' => __('message.hr_staff_title')]));
    }

    public function destroy($id)
    {
        if (! auth()->user()->can('hr-payroll-delete') && auth()->user()->user_type !== 'admin') {
            return redirect()->back()->withErrors(__('message.demo_permission_denied'));
        }

        $staff = HrStaff::findOrFail($id);
        $group = $staff->staff_group;
        $staff->delete();

        return redirect()->route('hr.staff.index', ['staff_group' => $group])
            ->withSuccess(__('message.delete_form', ['form' => __('message.hr_staff_title')]));
    }
}
