<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\HrStaff;
use App\Services\HrPayrollService;
use Illuminate\Http\Request;

class OfficeSalarySettingsController extends Controller
{
    public function saveDefault(Request $request, HrPayrollService $service)
    {
        $data = $request->validate([
            'monthly_salary' => 'required|numeric|min:0|max:100000000',
        ]);

        $amount = $service->setDefaultOfficeMonthlySalary((float) $data['monthly_salary']);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => __('message.sa_office_salary_default_saved'),
                'monthly_salary' => $amount,
            ]);
        }

        return redirect()
            ->route('super-admin.screens.show', 'office-salary')
            ->withSuccess(__('message.sa_office_salary_default_saved'));
    }

    public function updateMonthlySalary(Request $request, $id, HrPayrollService $service)
    {
        $staff = HrStaff::query()->where('staff_group', 'office')->findOrFail($id);
        $data = $request->validate([
            'monthly_salary' => 'required|numeric|min:0|max:100000000',
        ]);

        $service->updateStaffMonthlySalary($staff, (float) $data['monthly_salary']);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => __('message.sa_office_salary_saved'),
                'staff_id' => $staff->id,
                'monthly_salary' => (float) $staff->fresh()->monthly_salary,
            ]);
        }

        return redirect()
            ->route('super-admin.screens.show', 'office-salary')
            ->withSuccess(__('message.sa_office_salary_saved'));
    }
}
