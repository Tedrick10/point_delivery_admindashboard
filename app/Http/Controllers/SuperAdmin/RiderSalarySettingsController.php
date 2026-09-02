<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\HrStaff;
use App\Services\HrPayrollService;
use Illuminate\Http\Request;

class RiderSalarySettingsController extends Controller
{
    public function updateWayRate(Request $request, $id, HrPayrollService $service)
    {
        $staff = HrStaff::query()->where('staff_group', 'rider')->findOrFail($id);
        $data = $request->validate([
            'way_rate' => 'required|numeric|min:0|max:1000000',
        ]);

        $service->updateStaffWayRate($staff, (float) $data['way_rate']);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => __('message.sa_rider_way_rate_saved'),
                'staff_id' => $staff->id,
                'way_rate' => (float) $staff->fresh()->way_rate,
            ]);
        }

        return redirect()
            ->route('super-admin.screens.show', 'rider-salary')
            ->withSuccess(__('message.sa_rider_way_rate_saved'));
    }
}
