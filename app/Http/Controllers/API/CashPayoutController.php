<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\OsCashPayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CashPayoutController extends Controller
{
    public function list(Request $request)
    {
        $user = auth()->user();
        if (($user->user_type ?? '') !== 'delivery_man') {
            return json_custom_response(['message' => __('message.demo_permission_denied')], 403);
        }

        $status = trim((string) $request->get('status', 'assigned'));
        $query = OsCashPayout::query()
            ->with(['osUser:id,name,contact_number,address,city_id', 'osUser.city:id,name', 'settlementBatch'])
            ->where('delivery_man_id', $user->id);

        if ($status !== 'all') {
            $query->where('status', $status);
        } else {
            $query->whereIn('status', [
                OsCashPayout::STATUS_ASSIGNED,
                OsCashPayout::STATUS_PENDING,
                OsCashPayout::STATUS_DONE,
            ]);
        }

        $items = $query->orderByDesc('id')->get()->map(fn (OsCashPayout $p) => $this->serialize($p));

        return json_custom_response(['data' => $items]);
    }

    public function updateStatus(Request $request, $id)
    {
        $user = auth()->user();
        if (($user->user_type ?? '') !== 'delivery_man') {
            return json_custom_response(['message' => __('message.demo_permission_denied')], 403);
        }

        $next = (string) $request->input('status');
        if (! in_array($next, ['pending', 'done'], true)) {
            return json_custom_response(['message' => __('message.cash_payout_invalid_transition')], 422);
        }

        $payout = OsCashPayout::query()
            ->where('delivery_man_id', $user->id)
            ->findOrFail((int) $id);

        if ($next === 'pending') {
            $data = $request->validate([
                'status' => 'required|string|in:pending',
                'note' => 'required|string|max:2000',
                'photo' => 'required|file|max:10240',
            ]);
            if ($payout->status !== OsCashPayout::STATUS_ASSIGNED) {
                return json_custom_response(['message' => __('message.cash_payout_invalid_transition')], 422);
            }

            $dir = 'os-cash-payouts/'.$payout->id;
            Storage::disk('public')->makeDirectory($dir);
            $path = $request->file('photo')->store($dir, 'public');

            $payout->update([
                'status' => OsCashPayout::STATUS_PENDING,
                'pending_at' => now(),
                'pending_note' => trim((string) ($data['note'] ?? '')),
                'pending_photo_path' => $path,
            ]);
        } else {
            $data = $request->validate([
                'status' => 'required|string|in:done',
                'note' => 'required|string|max:2000',
                'photo' => 'required|file|max:10240',
            ]);
            if (! in_array($payout->status, [OsCashPayout::STATUS_ASSIGNED, OsCashPayout::STATUS_PENDING], true)) {
                return json_custom_response(['message' => __('message.cash_payout_invalid_transition')], 422);
            }

            $dir = 'os-cash-payouts/'.$payout->id;
            Storage::disk('public')->makeDirectory($dir);
            $path = $request->file('photo')->store($dir, 'public');

            $payout->update([
                'status' => OsCashPayout::STATUS_DONE,
                'done_at' => now(),
                'done_note' => trim((string) $data['note']),
                'done_photo_path' => $path,
            ]);
        }

        return json_custom_response([
            'message' => __('message.updated_successfully'),
            'data' => $this->serialize($payout->fresh([
                'osUser:id,name,contact_number,address,city_id',
                'osUser.city:id,name',
                'settlementBatch',
            ])),
        ]);
    }

    protected function serialize(OsCashPayout $p): array
    {
        $os = $p->osUser;

        return [
            'id' => $p->id,
            'os_user_id' => $p->os_user_id,
            'os_name' => $os?->name ?: ('OS #'.$p->os_user_id),
            'os_phone' => $os?->contact_number ?: '',
            'os_address' => formatOsClientAddress($os),
            'amount' => (float) $p->amount,
            'status' => $p->status,
            'period_from' => optional($p->period_from)->format('d-m-Y'),
            'period_to' => optional($p->period_to)->format('d-m-Y'),
            'slip_photo_url' => $p->slipPhotoUrl(),
            'pending_note' => $p->pending_note,
            'pending_photo_url' => $p->pendingPhotoUrl(),
            'done_note' => $p->done_note,
            'done_photo_url' => $p->donePhotoUrl(),
            'assigned_at' => optional($p->assigned_at)?->toDateTimeString(),
            'pending_at' => optional($p->pending_at)?->toDateTimeString(),
            'done_at' => optional($p->done_at)?->toDateTimeString(),
        ];
    }
}
