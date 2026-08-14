<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use App\Services\MoneyTransferService;
use Illuminate\Http\Request;

class MoneyTransferController extends Controller
{
    public function index(Request $request, MoneyTransferService $service)
    {
        if (! auth()->user()->can('order-list')) {
            return redirect()->route('home')->withErrors(__('message.demo_permission_denied'));
        }

        $yangonToday = now('Asia/Yangon')->format('d-m-Y');
        $fromDateRaw = trim((string) $request->get('from_date', $yangonToday));
        $toDateRaw = trim((string) $request->get('to_date', $fromDateRaw));
        $fromDay = $service->parseDate($fromDateRaw)->toDateString();
        $toDay = $service->parseDate($toDateRaw)->toDateString();

        $branchFilter = $request->get('branch_id', 'all');
        $branchId = $branchFilter === 'all' || $branchFilter === '' || $branchFilter === null
            ? null
            : (int) $branchFilter;

        $loginUser = auth()->user();
        $branches = Branch::query()->where('status', 1)->orderBy('name')->get(['id', 'name']);
        if (! in_array((string) ($loginUser->user_type ?? ''), ['admin', 'demo_admin'], true)
            && (int) ($loginUser->branch_id ?? 0) > 0
        ) {
            $branches = $branches->where('id', (int) $loginUser->branch_id)->values();
            $branchId = (int) $loginUser->branch_id;
            $branchFilter = (string) $branchId;
        }

        $osFilter = $request->get('os_id', 'all');
        $osId = null;
        if ($osFilter !== 'all' && $osFilter !== '') {
            $osId = (int) $osFilter;
        }

        $method = trim((string) $request->get('method', 'all'));
        if (! in_array($method, ['all', 'kpay', 'cash'], true)) {
            $method = 'all';
        }

        $sheet = $service->listSheet($fromDay, $toDay, $branchId, $osId, $method);
        $rows = $sheet['rows'];
        $summary = $sheet['summary'];

        $osOptions = User::query()
            ->where('user_type', 'client')
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        $pageTitle = __('message.money_transfer_list');
        $assets = [];
        $filterFromDate = $fromDateRaw;
        $filterToDate = $toDateRaw;
        $canEdit = auth()->user()->can('order-edit');
        $paymentMethod = $method;

        return view('order.money-transfer', compact(
            'pageTitle',
            'assets',
            'rows',
            'summary',
            'filterFromDate',
            'filterToDate',
            'branchFilter',
            'branches',
            'osFilter',
            'osOptions',
            'canEdit',
            'fromDay',
            'toDay',
            'branchId',
            'paymentMethod'
        ));
    }

    public function upsert(Request $request, MoneyTransferService $service)
    {
        if (! auth()->user()->can('order-edit')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        $data = $request->validate([
            'from_date' => 'required|string',
            'to_date' => 'required|string',
            'branch_id' => 'nullable',
            'os_user_id' => 'required|integer|min:0',
            'cash_amount' => 'nullable|numeric|min:0',
            'kpay_amount' => 'nullable|numeric|min:0',
            'freight_amount' => 'nullable|numeric|min:0',
            'remark' => 'nullable|string|max:255',
            'payment_method' => 'nullable|string|in:kpay,cash',
        ]);

        $fromDay = $service->parseDate($data['from_date'])->toDateString();
        $toDay = $service->parseDate($data['to_date'])->toDateString();

        $branchFilter = $data['branch_id'] ?? 'all';
        $branchId = $branchFilter === 'all' || $branchFilter === '' || $branchFilter === null
            ? null
            : (int) $branchFilter;

        $loginUser = auth()->user();
        if (! in_array((string) ($loginUser->user_type ?? ''), ['admin', 'demo_admin'], true)
            && (int) ($loginUser->branch_id ?? 0) > 0
        ) {
            $branchId = (int) $loginUser->branch_id;
        }

        $method = in_array(($data['payment_method'] ?? ''), ['kpay', 'cash'], true)
            ? $data['payment_method']
            : 'kpay';

        $row = $service->upsertRow($fromDay, $toDay, $branchId, (int) $data['os_user_id'], $data);
        $sheet = $service->listSheet($fromDay, $toDay, $branchId, (int) $data['os_user_id'], $method);
        $viewRow = $sheet['rows']->first();
        $full = $service->listSheet($fromDay, $toDay, $branchId, null, $method);

        return response()->json([
            'message' => __('message.updated_successfully'),
            'transfer_id' => $row->id,
            'row' => $viewRow,
            'summary' => $full['summary'],
        ]);
    }

    public function fillKpay(Request $request, MoneyTransferService $service)
    {
        if (! auth()->user()->can('order-edit')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        $data = $request->validate([
            'from_date' => 'required|string',
            'to_date' => 'required|string',
            'branch_id' => 'nullable',
        ]);

        $fromDay = $service->parseDate($data['from_date'])->toDateString();
        $toDay = $service->parseDate($data['to_date'])->toDateString();
        $branchFilter = $data['branch_id'] ?? 'all';
        $branchId = $branchFilter === 'all' || $branchFilter === '' || $branchFilter === null
            ? null
            : (int) $branchFilter;

        $loginUser = auth()->user();
        if (! in_array((string) ($loginUser->user_type ?? ''), ['admin', 'demo_admin'], true)
            && (int) ($loginUser->branch_id ?? 0) > 0
        ) {
            $branchId = (int) $loginUser->branch_id;
        }

        $count = $service->fillKpayFromDue($fromDay, $toDay, $branchId);

        return response()->json([
            'message' => __('message.money_transfer_fill_kpay_done', ['count' => $count]),
            'count' => $count,
        ]);
    }

    public function switchMethod(Request $request, MoneyTransferService $service)
    {
        if (! auth()->user()->can('order-edit')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        $data = $request->validate([
            'transfer_id' => 'required|integer',
            'payment_method' => 'required|string|in:kpay',
            'kpay_slip' => 'required|image|max:10240',
        ]);

        $transfer = \App\Models\OsMoneyTransfer::query()->findOrFail((int) $data['transfer_id']);
        if (($transfer->payment_method ?? '') !== 'cash') {
            return response()->json(['message' => __('message.cash_payout_invalid_transition')], 422);
        }

        try {
            $row = $service->switchPaymentMethod(
                $transfer,
                'kpay',
                $request->file('kpay_slip')
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => __('message.money_transfer_switched_to_kpay'),
            'payment_method' => $row->payment_method,
            'transfer_id' => $row->id,
        ]);
    }
}
