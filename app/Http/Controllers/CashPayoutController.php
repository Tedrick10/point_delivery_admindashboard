<?php

namespace App\Http\Controllers;

use App\Models\OsCashPayout;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CashPayoutController extends Controller
{
    public function index(Request $request)
    {
        if (! auth()->user()->can('order-list')) {
            return redirect()->route('home')->withErrors(__('message.demo_permission_denied'));
        }

        $status = trim((string) $request->get('status', 'unassigned'));
        if (! in_array($status, ['unassigned', 'assigned', 'pending', 'done'], true)) {
            $status = 'unassigned';
        }

        $items = OsCashPayout::query()
            ->with(['osUser.city', 'deliveryMan', 'settlementBatch'])
            ->when($status === 'unassigned', fn ($q) => $q->where('status', OsCashPayout::STATUS_UNASSIGNED))
            ->when($status === 'assigned', fn ($q) => $q->where('status', OsCashPayout::STATUS_ASSIGNED))
            ->when($status === 'pending', fn ($q) => $q->where('status', OsCashPayout::STATUS_PENDING))
            ->when($status === 'done', fn ($q) => $q->where('status', OsCashPayout::STATUS_DONE))
            ->orderByDesc('id')
            ->get();

        $riders = User::query()
            ->where('user_type', 'delivery_man')
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'contact_number']);

        $counts = [
            'unassigned' => OsCashPayout::query()->where('status', OsCashPayout::STATUS_UNASSIGNED)->count(),
            'assigned' => OsCashPayout::query()->where('status', OsCashPayout::STATUS_ASSIGNED)->count(),
            'pending' => OsCashPayout::query()->where('status', OsCashPayout::STATUS_PENDING)->count(),
            'done' => OsCashPayout::query()->where('status', OsCashPayout::STATUS_DONE)->count(),
        ];

        $pageTitle = __('message.cash_payout_title');
        $assets = [];
        $canEdit = auth()->user()->can('order-edit');

        return view('order.cash-payout', compact(
            'pageTitle',
            'assets',
            'items',
            'riders',
            'status',
            'counts',
            'canEdit'
        ));
    }

    public function assign(Request $request, $id)
    {
        if (! auth()->user()->can('order-edit')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        $data = $request->validate([
            'delivery_man_id' => 'required|integer|exists:users,id',
        ]);

        $payout = OsCashPayout::query()->findOrFail($id);
        if (! in_array($payout->status, [OsCashPayout::STATUS_UNASSIGNED, OsCashPayout::STATUS_ASSIGNED], true)) {
            return response()->json(['message' => __('message.cash_payout_cannot_assign')], 422);
        }

        $rider = User::query()->where('user_type', 'delivery_man')->findOrFail((int) $data['delivery_man_id']);

        if (! $rider->isRiderWorkOn()) {
            return response()->json(['message' => __('message.rider_work_off_assign_blocked')], 422);
        }

        $payout->update([
            'delivery_man_id' => $rider->id,
            'status' => OsCashPayout::STATUS_ASSIGNED,
            'assigned_at' => now(),
        ]);

        return response()->json([
            'message' => __('message.updated_successfully'),
            'status' => $payout->status,
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        if (! auth()->user()->can('order-edit')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        $next = (string) $request->input('status');
        if (! in_array($next, ['pending', 'done'], true)) {
            return response()->json(['message' => __('message.cash_payout_invalid_transition')], 422);
        }

        $payout = OsCashPayout::query()->findOrFail((int) $id);

        if ($next === 'pending') {
            $data = $request->validate([
                'status' => 'required|string|in:pending',
                'note' => 'required|string|max:2000',
                'photo' => 'required|file|max:10240',
            ]);
            if ($payout->status !== OsCashPayout::STATUS_ASSIGNED) {
                return response()->json(['message' => __('message.cash_payout_invalid_transition')], 422);
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
            $request->validate([
                'status' => 'required|string|in:done',
                'note' => 'nullable|string|max:2000',
                'photo' => 'required|file|max:10240',
            ]);
            if (! in_array($payout->status, [OsCashPayout::STATUS_ASSIGNED, OsCashPayout::STATUS_PENDING], true)) {
                return response()->json(['message' => __('message.cash_payout_invalid_transition')], 422);
            }

            $dir = 'os-cash-payouts/'.$payout->id;
            Storage::disk('public')->makeDirectory($dir);
            $path = $request->file('photo')->store($dir, 'public');

            $payout->update([
                'status' => OsCashPayout::STATUS_DONE,
                'done_at' => now(),
                'done_note' => null,
                'done_photo_path' => $path,
            ]);
        }

        return response()->json([
            'message' => __('message.updated_successfully'),
            'status' => $payout->fresh()->status,
        ]);
    }
}
