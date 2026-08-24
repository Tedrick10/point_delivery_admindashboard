<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\OsReceiveSettlement;
use App\Services\OsReceiveSettlementService;
use Illuminate\Http\Request;

class OsReceiveSettlementController extends Controller
{
    public function unsettledSummary(Request $request)
    {
        $user = auth()->user();
        if ((string) $user->user_type !== 'client') {
            return json_custom_response([
                'data' => [
                    'has_unsettled' => false,
                    'count' => 0,
                    'total_amount' => 0,
                ],
            ]);
        }

        $rows = OsReceiveSettlement::query()
            ->where('os_user_id', (int) $user->id)
            ->whereIn('status', [
                OsReceiveSettlement::STATUS_PENDING,
                OsReceiveSettlement::STATUS_WAITING,
                OsReceiveSettlement::STATUS_REJECTED,
            ])
            ->orderByDesc('id')
            ->get(['id', 'amount', 'status']);

        $count = $rows->count();
        $total = round((float) $rows->sum(static fn ($r) => abs((float) $r->amount)), 2);

        // Prefer actionable (pending/rejected) for upload screen; else latest waiting.
        $latest = $rows->first(static fn ($r) => in_array($r->status, [
            OsReceiveSettlement::STATUS_PENDING,
            OsReceiveSettlement::STATUS_REJECTED,
        ], true)) ?? $rows->first();

        return json_custom_response([
            'data' => [
                'has_unsettled' => $count > 0,
                'count' => $count,
                'total_amount' => $total,
                'latest_receive_id' => $latest?->id,
            ],
        ]);
    }

    public function show(Request $request, int $id)
    {
        $user = auth()->user();
        $receive = OsReceiveSettlement::query()->findOrFail($id);

        if ((string) $user->user_type === 'client' && (int) $receive->os_user_id !== (int) $user->id) {
            return json_message_response(__('message.demo_permission_denied'), 403);
        }

        return json_custom_response([
            'data' => app(OsReceiveSettlementService::class)->toApiArray($receive),
        ]);
    }

    public function uploadPayslip(Request $request, int $id)
    {
        $user = auth()->user();
        $receive = OsReceiveSettlement::query()->findOrFail($id);

        if ((string) $user->user_type === 'client' && (int) $receive->os_user_id !== (int) $user->id) {
            return json_message_response(__('message.demo_permission_denied'), 403);
        }

        $request->validate([
            'payslip' => 'nullable|image|max:10240',
            'payslips' => 'nullable|array|min:1|max:10',
            'payslips.*' => 'image|max:10240',
        ]);

        $files = [];
        if ($request->hasFile('payslips')) {
            $files = $request->file('payslips');
            if (! is_array($files)) {
                $files = [$files];
            }
        } elseif ($request->hasFile('payslip')) {
            $files = [$request->file('payslip')];
        }

        if ($files === []) {
            return json_message_response(__('message.please_select_kbz_screenshot'), 422);
        }

        try {
            $updated = app(OsReceiveSettlementService::class)->submitOsPayslips(
                $receive,
                $files
            );
        } catch (\RuntimeException $e) {
            return json_message_response($e->getMessage(), 422);
        }

        return json_custom_response([
            'message' => __('message.os_receive_payslip_uploaded'),
            'data' => app(OsReceiveSettlementService::class)->toApiArray($updated),
        ]);
    }
}
