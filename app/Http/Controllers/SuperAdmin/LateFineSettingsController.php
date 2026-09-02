<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\HrStaff;
use App\Services\HrPayrollService;
use Illuminate\Http\Request;

class LateFineSettingsController extends Controller
{
    public function saveDefaults(Request $request, HrPayrollService $service)
    {
        $data = $request->validate([
            'fine_per_minute' => 'required|integer|min:0|max:100000',
            'absent_day_rate' => 'required|integer|min:0|max:1000000',
        ]);

        $service->setDefaultFinePerMinute((int) $data['fine_per_minute']);
        $service->setDefaultAbsentDayRate((int) $data['absent_day_rate']);
        $service->applyGlobalLateFineRates(null);

        $month = $service->parseMonth(null);
        $service->syncSalaryDeductionsFromLateFine($month);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => __('message.sa_late_fine_defaults_saved'),
                'fine_per_minute' => $service->defaultFinePerMinute(),
                'absent_day_rate' => $service->defaultAbsentDayRate(),
            ]);
        }

        return redirect()
            ->route('super-admin.screens.show', 'late-fine')
            ->withSuccess(__('message.sa_late_fine_defaults_saved'));
    }

    public function updateAllowance(Request $request, $id, HrPayrollService $service)
    {
        $staff = HrStaff::query()->findOrFail($id);
        $data = $request->validate([
            'allowance_minutes' => 'required|integer|min:0|max:600',
        ]);

        $service->updateStaffAllowanceMinutes($staff, (int) $data['allowance_minutes']);
        $month = $service->parseMonth(null);
        $service->syncSalaryDeductionsFromLateFine($month);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => __('message.sa_late_fine_allowance_saved'),
                'staff_id' => $staff->id,
                'allowance_minutes' => (int) $staff->fresh()->allowance_minutes,
            ]);
        }

        return redirect()
            ->route('super-admin.screens.show', 'late-fine')
            ->withSuccess(__('message.sa_late_fine_allowance_saved'));
    }
}
