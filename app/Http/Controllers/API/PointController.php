<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PointHistory;
use App\Models\PointOrderPayment;
use App\Models\PointSalaryRequest;
use App\Services\PointOrderPaymentService;
use App\Services\PointSalaryRequestService;
use App\Services\PointWalletService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class PointController extends Controller
{
    public function balance(PointWalletService $points)
    {
        $user = auth()->user();
        if (! $user) {
            return json_message_response(__('message.demo_permission_denied'), 403);
        }

        return json_custom_response([
            'data' => [
                'user_id' => (int) $user->id,
                'balance' => $points->balance((int) $user->id),
                'conversion_rate' => app(PointSalaryRequestService::class)->conversionRate(),
            ],
        ]);
    }

    public function history(Request $request)
    {
        $user = auth()->user();
        if (! $user) {
            return json_message_response(__('message.demo_permission_denied'), 403);
        }

        $rows = PointHistory::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->paginate(max(1, min(50, (int) $request->get('per_page', 20))));

        return json_custom_response([
            'data' => $rows->items(),
            'pagination' => json_pagination_response($rows),
        ]);
    }

    public function payOrder(Request $request, PointOrderPaymentService $service)
    {
        $user = auth()->user();
        if (! $user) {
            return json_message_response(__('message.demo_permission_denied'), 403);
        }

        $data = $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'points' => 'nullable|numeric|min:0.01',
            'remark' => 'nullable|string|max:500',
        ]);

        try {
            $payment = $service->create(
                $user,
                (int) $data['order_id'],
                isset($data['points']) ? (float) $data['points'] : null,
                $data['remark'] ?? null
            );
        } catch (InvalidArgumentException $e) {
            return json_message_response($e->getMessage(), 400);
        }

        return json_custom_response([
            'message' => __('message.point_payment_submitted'),
            'data' => $payment,
        ]);
    }

    public function myOrderPayments(Request $request)
    {
        $user = auth()->user();
        if (! $user) {
            return json_message_response(__('message.demo_permission_denied'), 403);
        }

        $rows = PointOrderPayment::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->paginate(max(1, min(50, (int) $request->get('per_page', 20))));

        return json_custom_response([
            'data' => $rows->items(),
            'pagination' => json_pagination_response($rows),
        ]);
    }

    public function requestSalary(Request $request, PointSalaryRequestService $service)
    {
        $user = auth()->user();
        if (! $user) {
            return json_message_response(__('message.demo_permission_denied'), 403);
        }

        if (($user->user_type ?? '') === 'client') {
            return json_message_response(__('message.demo_permission_denied'), 403);
        }

        $data = $request->validate([
            'points' => 'required|numeric|min:0.01',
            'month' => 'nullable|string',
            'remark' => 'nullable|string|max:500',
        ]);

        try {
            $row = $service->create(
                $user,
                (float) $data['points'],
                $data['month'] ?? null,
                $data['remark'] ?? null
            );
        } catch (InvalidArgumentException $e) {
            return json_message_response($e->getMessage(), 400);
        }

        return json_custom_response([
            'message' => __('message.point_salary_submitted'),
            'data' => $row,
        ]);
    }

    public function mySalaryRequests(Request $request)
    {
        $user = auth()->user();
        if (! $user) {
            return json_message_response(__('message.demo_permission_denied'), 403);
        }

        $rows = PointSalaryRequest::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->paginate(max(1, min(50, (int) $request->get('per_page', 20))));

        return json_custom_response([
            'data' => $rows->items(),
            'pagination' => json_pagination_response($rows),
        ]);
    }
}
