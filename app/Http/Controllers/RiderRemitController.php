<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\RiderRemit;
use App\Models\User;
use App\Services\RiderRemitAuditService;
use App\Services\RiderRemitService;
use Illuminate\Http\Request;

class RiderRemitController extends Controller
{
    public function index(Request $request, RiderRemitService $service)
    {
        if (! auth()->user()->can('order-list')) {
            return redirect()->route('home')->withErrors(__('message.demo_permission_denied'));
        }

        $defaultDay = yangonSettlementDefaultDate();
        $dateRaw = trim((string) $request->get('date', $defaultDay));
        if ($dateRaw === '') {
            $dateRaw = $defaultDay;
        }
        $day = $service->parseDate($dateRaw)->toDateString();

        $branchFilter = $request->get('branch_id', 'all');
        $branchId = $branchFilter === 'all' || $branchFilter === '' || $branchFilter === null
            ? null
            : (int) $branchFilter;

        $loginUser = auth()->user();
        [$branchId, $branchFilter, $branches] = resolveDestinationBranchFilter($request, $loginUser);
        $branchTabs = $branches;

        $service->ensureOpenRemitDefaults($day, $branchId, (int) auth()->id());
        $sheet = $service->sheet($day, $branchId);
        $pageTitle = __('message.rider_remit_title');
        $assets = [];
        $canEdit = auth()->user()->can('order-edit');
        $filterDate = $dateRaw;
        $denoms = RiderRemit::DENOMS;
        $riders = $sheet['riders'];
        $summary = $sheet['summary'];
        $isOtherBranchRemit = (bool) ($sheet['is_other_branch'] ?? false);
        $storeBranchId = $branchId && $branchId > 0 ? $branchId : 0;
        $defaultFuel = $service->defaultFuelAmount();
        $selectedBranchId = $branchId;

        $visibleRiderIds = User::query()
            ->where('user_type', 'delivery_man')
            ->visibleOnAdminRiderList($loginUser)
            ->pluck('id');

        $branchTabCounts = RiderRemit::query()
            ->whereDate('remit_date', $day)
            ->whereNotNull('branch_id')
            ->where('branch_id', '>', 0)
            ->whereIn('delivery_man_id', $visibleRiderIds)
            ->selectRaw('branch_id, COUNT(DISTINCT delivery_man_id) as total')
            ->groupBy('branch_id')
            ->pluck('total', 'branch_id');
        $allBranchCount = (int) RiderRemit::query()
            ->whereDate('remit_date', $day)
            ->whereIn('delivery_man_id', $visibleRiderIds)
            ->distinct()
            ->count('delivery_man_id');

        return view('order.rider-remit', compact(
            'pageTitle',
            'assets',
            'riders',
            'summary',
            'filterDate',
            'branchFilter',
            'branches',
            'branchTabs',
            'branchTabCounts',
            'allBranchCount',
            'selectedBranchId',
            'canEdit',
            'denoms',
            'day',
            'storeBranchId',
            'defaultFuel',
            'isOtherBranchRemit'
        ));
    }

    public function saveDefaultFuel(Request $request, RiderRemitService $service)
    {
        if (! auth()->user()->can('order-edit')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        $data = $request->validate([
            'fuel_amount' => 'required|numeric|min:0',
            'remit_date' => 'nullable|date',
            'branch_id' => 'nullable|integer|min:0',
        ]);

        $oldDefault = $service->defaultFuelAmount();
        $newDefault = $service->setDefaultFuelAmount((float) $data['fuel_amount']);

        $applied = 0;
        $dayRaw = trim((string) ($data['remit_date'] ?? ''));
        if ($dayRaw !== '') {
            $day = $service->parseDate($dayRaw)->toDateString();
            $branchId = isset($data['branch_id']) ? (int) $data['branch_id'] : null;
            $forcedBranchId = forcedBranchId(auth()->user());
            if ($forcedBranchId) {
                $branchId = $forcedBranchId;
            }
            $applied = $service->applyDefaultFuelToOpenRemits(
                $day,
                $branchId && $branchId > 0 ? $branchId : null,
                $oldDefault,
                $newDefault
            );
        }

        return response()->json([
            'message' => __('message.updated_successfully'),
            'default_fuel' => $newDefault,
            'applied' => $applied,
        ]);
    }

    public function save(Request $request, RiderRemitService $service)
    {
        if (! auth()->user()->can('order-edit')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        $data = $request->validate([
            'remit_date' => 'required|date',
            'branch_id' => 'nullable|integer|min:0',
            'delivery_man_id' => 'required|integer|exists:users,id',
            'prepaid_amount' => 'nullable|numeric|min:0',
            'fuel_amount' => 'nullable|numeric|min:0',
            'fee_amount' => 'nullable|numeric|min:0',
            'kpay_amount' => 'nullable|numeric|min:0',
            'kyo_shin_incharge_amount' => 'nullable|numeric|min:0',
            'denominations' => 'nullable|array',
        ]);

        $isRider = \App\Models\User::query()
            ->where('id', (int) $data['delivery_man_id'])
            ->where('user_type', 'delivery_man')
            ->exists();
        if (! $isRider) {
            return response()->json(['message' => __('message.something_went_wrong')], 422);
        }

        $forcedBranchId = forcedBranchId(auth()->user());
        if ($forcedBranchId) {
            $data['branch_id'] = $forcedBranchId;
        }

        $row = $service->save($data, (int) auth()->id());
        $branchId = (int) ($data['branch_id'] ?? 0);
        $sheet = $service->sheet($row->remit_date->toDateString(), $branchId > 0 ? $branchId : null);
        $rider = collect($sheet['riders'])->firstWhere('delivery_man_id', (int) $row->delivery_man_id);

        return response()->json([
            'message' => __('message.updated_successfully'),
            'rider' => $rider,
            'summary' => $sheet['summary'],
        ]);
    }

    public function submit(Request $request, RiderRemitService $service)
    {
        if (! auth()->user()->can('order-edit')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        if (env('APP_DEMO')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        $data = $request->validate([
            'remit_date' => 'required|date',
            'branch_id' => 'nullable|integer|min:0',
            'riders' => 'required|array|min:1',
            'riders.*.delivery_man_id' => 'required|integer|exists:users,id',
            'riders.*.prepaid_amount' => 'nullable|numeric|min:0',
            'riders.*.fuel_amount' => 'nullable|numeric|min:0',
            'riders.*.fee_amount' => 'nullable|numeric|min:0',
            'riders.*.kpay_amount' => 'nullable|numeric|min:0',
            'riders.*.kyo_shin_incharge_amount' => 'nullable|numeric|min:0',
            'riders.*.denominations' => 'nullable|array',
        ]);

        $day = $service->parseDate((string) $data['remit_date'])->toDateString();
        $branchId = (int) ($data['branch_id'] ?? 0);
        $forcedBranchId = forcedBranchId(auth()->user());
        if ($forcedBranchId) {
            $branchId = $forcedBranchId;
        }

        $sheet = $service->submitAll(
            $day,
            $branchId > 0 ? $branchId : null,
            $data['riders'],
            (int) auth()->id()
        );

        return response()->json([
            'message' => __('message.rider_remit_submit_success'),
            'summary' => $sheet['summary'],
        ]);
    }

    public function logs(Request $request, RiderRemitService $service, RiderRemitAuditService $audit)
    {
        if (! auth()->user()->can('order-list')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        $day = $service->parseDate(trim((string) $request->get('date', yangonSettlementDefaultDate())))->toDateString();
        $branchFilter = $request->get('branch_id', 'all');
        $branchId = $branchFilter === 'all' || $branchFilter === '' || $branchFilter === null
            ? 0
            : (int) $branchFilter;

        $forcedBranchId = forcedBranchId(auth()->user());
        if ($forcedBranchId) {
            $branchId = $forcedBranchId;
        }

        return response()->json([
            'logs' => $audit->timeline($day, $branchId),
        ]);
    }
}
