<?php

namespace App\Http\Controllers;

use App\Models\OsReceiveSettlement;
use App\Services\OsReceiveSettlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class OsReceiveSettlementController extends Controller
{
    public function index(Request $request)
    {
        if (! auth()->user()->can('order-list')) {
            return redirect()->route('home')->withErrors(__('message.demo_permission_denied'));
        }

        $tab = trim((string) $request->get('tab', 'open'));
        if (! in_array($tab, ['open', 'received'], true)) {
            $tab = 'open';
        }

        [$branchId, $branchFilter, $branches] = resolveDestinationBranchFilter($request);
        $branchTabs = $branches;
        $selectedBranchId = $branchId;

        $openStatuses = [
            OsReceiveSettlement::STATUS_PENDING,
            OsReceiveSettlement::STATUS_WAITING,
            OsReceiveSettlement::STATUS_REJECTED,
        ];

        $query = OsReceiveSettlement::query()
            ->with(['osUser.city', 'settlementBatch', 'finishedByUser', 'reviewedByUser']);

        if ($tab === 'received') {
            $query->where('status', OsReceiveSettlement::STATUS_RECEIVED);
        } else {
            $query->whereIn('status', $openStatuses);
        }

        $items = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        // Tabs follow parcel destination (same as ငွေရှင်းတမ်း), not OS home.
        $itemBranchMap = null;
        $settlementBranchId = function (OsReceiveSettlement $row) use (&$itemBranchMap) {
            if ($itemBranchMap === null) {
                $itemBranchMap = \App\Models\DispatchOrderItem::query()
                    ->select('id', 'from_branch_id', 'to_branch_id')
                    ->get()
                    ->mapWithKeys(function ($item) {
                        $branch = (int) ($item->to_branch_id ?: $item->from_branch_id ?: 0);

                        return [(int) $item->id => $branch];
                    });
            }

            $destCounts = [];
            foreach (collect($row->settlementBatch?->item_ids ?? []) as $itemId) {
                $bid = (int) ($itemBranchMap[(int) $itemId] ?? 0);
                if ($bid > 0) {
                    $destCounts[$bid] = ($destCounts[$bid] ?? 0) + 1;
                }
            }
            if ($destCounts !== []) {
                arsort($destCounts);

                return (int) array_key_first($destCounts);
            }

            return (int) (osHomeBranchId((int) ($row->os_user_id ?? 0)) ?: 0);
        };

        if ($branchId) {
            $items = $items->filter(fn (OsReceiveSettlement $row) => $settlementBranchId($row) === (int) $branchId)->values();
        }

        $allSettlements = OsReceiveSettlement::query()->with('settlementBatch')->get();
        $openCount = $allSettlements
            ->filter(fn (OsReceiveSettlement $row) => in_array($row->status, $openStatuses, true))
            ->when($branchId, fn ($c) => $c->filter(fn (OsReceiveSettlement $row) => $settlementBranchId($row) === (int) $branchId))
            ->count();
        $receivedCount = $allSettlements
            ->filter(fn (OsReceiveSettlement $row) => $row->status === OsReceiveSettlement::STATUS_RECEIVED)
            ->when($branchId, fn ($c) => $c->filter(fn (OsReceiveSettlement $row) => $settlementBranchId($row) === (int) $branchId))
            ->count();

        $branchTabCounts = $branches->mapWithKeys(function ($branch) use ($allSettlements, $settlementBranchId) {
            return [
                $branch->id => $allSettlements
                    ->filter(fn (OsReceiveSettlement $row) => $settlementBranchId($row) === (int) $branch->id)
                    ->count(),
            ];
        });
        $allBranchCount = $allSettlements->count();

        // Group by settlement period day (from_date), not Finish click time (created_at).
        // created_at can be a later calendar day (e.g. Finished on 27th for a 25th sheet).
        $groupedItems = $items
            ->groupBy(function (OsReceiveSettlement $item) {
                $day = normalizeYangonDateOnly($item->from_date ?? null)
                    ?: normalizeYangonDateOnly($item->to_date ?? null);
                if ($day) {
                    return $day;
                }

                return optional($item->created_at)->timezone('Asia/Yangon')->toDateString()
                    ?? 'unknown';
            })
            ->sortKeysDesc()
            ->map(function (Collection $dayItems) use ($tab) {
                if ($tab !== 'received') {
                    // Waiting first (needs action), then pending, then rejected.
                    $rank = [
                        OsReceiveSettlement::STATUS_WAITING => 0,
                        OsReceiveSettlement::STATUS_PENDING => 1,
                        OsReceiveSettlement::STATUS_REJECTED => 2,
                    ];

                    return $dayItems
                        ->sortBy([
                            fn (OsReceiveSettlement $item) => $rank[$item->status] ?? 9,
                            fn (OsReceiveSettlement $item) => (int) $item->id,
                        ])
                        ->values();
                }

                // Received tab: later Approvals sink to the bottom of that date.
                return $dayItems
                    ->sortBy([
                        fn (OsReceiveSettlement $item) => optional($item->approved_at)?->timestamp
                            ?? optional($item->created_at)?->timestamp
                            ?? 0,
                        fn (OsReceiveSettlement $item) => (int) $item->id,
                    ])
                    ->values();
            });

        $pageTitle = $tab === 'received'
            ? __('message.os_receive_done_tab')
            : __('message.os_receive_screen_title');
        $assets = [];
        $canEdit = auth()->user()->can('order-edit');
        $statCount = $tab === 'received' ? $receivedCount : $openCount;

        return view('order.os-receive', compact(
            'pageTitle',
            'assets',
            'items',
            'groupedItems',
            'canEdit',
            'statCount',
            'tab',
            'openCount',
            'receivedCount',
            'branchFilter',
            'branches',
            'branchTabs',
            'branchTabCounts',
            'allBranchCount',
            'selectedBranchId'
        ));
    }

    public function approve(Request $request, $id)
    {
        if (! auth()->user()->can('order-edit')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        try {
            $receive = app(OsReceiveSettlementService::class)->approve(
                OsReceiveSettlement::query()->findOrFail((int) $id),
                (int) auth()->id()
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => __('message.os_receive_approved'),
            'status' => $receive->status,
            'redirect' => route('order.os-receive', ['tab' => 'received']),
        ]);
    }

    public function reject(Request $request, $id)
    {
        if (! auth()->user()->can('order-edit')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        $data = $request->validate([
            'remark' => 'required|string|max:1000',
        ]);

        try {
            $receive = app(OsReceiveSettlementService::class)->reject(
                OsReceiveSettlement::query()->findOrFail((int) $id),
                (int) auth()->id(),
                (string) $data['remark']
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => __('message.os_receive_rejected'),
            'status' => $receive->status,
        ]);
    }
}
