<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\HrPayrollService;
use Illuminate\Http\Request;

class HrSalaryController extends Controller
{
    /**
     * Self-service payroll salary for Rider App / Office account API.
     * GET /api/my-payroll-salary?month=Y-m
     */
    public function mySalary(Request $request, HrPayrollService $service)
    {
        $user = auth()->user();
        if (! $user) {
            return json_custom_response(['message' => __('message.demo_permission_denied')], 403);
        }

        $userType = (string) ($user->user_type ?? '');
        if ($userType === 'client') {
            return json_custom_response(['message' => __('message.demo_permission_denied')], 403);
        }

        $payload = $service->salaryForUser($user, $request->get('month'));
        if (! $payload) {
            return json_custom_response([
                'message' => __('message.hr_my_salary_not_found'),
                'data' => null,
            ], 404);
        }

        // Riders only see rider salary; office staff only see office salary
        if ($userType === 'delivery_man' && ($payload['staff_group'] ?? '') !== 'rider') {
            return json_custom_response(['message' => __('message.demo_permission_denied')], 403);
        }
        if (
            $userType !== 'delivery_man'
            && $userType !== 'admin'
            && ($payload['staff_group'] ?? '') !== 'office'
        ) {
            return json_custom_response(['message' => __('message.demo_permission_denied')], 403);
        }

        return json_custom_response([
            'data' => $payload,
        ]);
    }
}
