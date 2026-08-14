<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\DispatchOrderItemResource;
use App\Models\DispatchItemMessage;
use App\Models\DispatchOrderItem;
use App\Models\Order;
use App\Services\DispatchOrderAuditService;
use App\Services\DispatchOrderWorkflowService;
use App\Services\PickupParcelDispatchService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DispatchOrderItemController extends Controller
{
    /**
     * Resolve order for rider pickup item APIs without raw ModelNotFoundException.
     *
     * @return \App\Models\Order|\Illuminate\Http\JsonResponse
     */
    protected function resolveRiderPickupOrder($orderId)
    {
        $order = Order::query()->find($orderId);
        if (! $order) {
            return json_custom_response([
                'status' => false,
                'message' => __('message.pickup_order_not_found'),
            ], 404);
        }

        $user = auth()->user();
        if ($user && $user->user_type === 'delivery_man' && (int) $order->delivery_man_id !== (int) $user->id) {
            return json_custom_response([
                'status' => false,
                'message' => __('message.pickup_order_no_longer_assigned'),
            ], 403);
        }

        return $order;
    }

    public function updatePickupItem(Request $request, $orderId, $itemId)
    {
        $request->validate([
            'weight' => 'nullable|integer|min:1|max:10',
            'deli_amount' => 'nullable|numeric|min:0',
            'item_value' => 'nullable|numeric|min:0',
            'pay_mode' => 'nullable|in:os_pay,customer_pay,pay_done',
        ]);

        if (! $request->filled('weight')
            && ! $request->filled('pay_mode')
            && ! $request->exists('deli_amount')
            && ! $request->exists('item_value')) {
            return json_custom_response([
                'status' => false,
                'message' => __('message.order_not_editable'),
            ], 422);
        }

        $order = $this->resolveRiderPickupOrder($orderId);
        if (! $order instanceof Order) {
            return $order;
        }

        $pickupService = app(PickupParcelDispatchService::class);

        if (! $pickupService->isDispatchPickupOrder($order)) {
            return json_custom_response([
                'status' => false,
                'message' => __('message.not_found_entry', ['name' => __('message.order')]),
            ], 422);
        }

        if (! in_array($order->status, [
            'courier_assigned',
            'courier_arrived',
            'courier_picked_up',
            'pickup_error',
            'active',
            'create',
        ], true)) {
            return json_custom_response([
                'status' => false,
                'message' => __('message.order_not_editable'),
            ], 422);
        }

        // Do not call ensureDispatchItems here — sync can create/delete items
        // while the rider is selecting sizes and make panels "auto-increase".
        $item = DispatchOrderItem::query()
            ->where('order_id', $order->id)
            ->where('id', $itemId)
            ->whereIn('status', ['collected', 'assigned'])
            ->firstOrFail();

        $audit = app(DispatchOrderAuditService::class);
        $before = $audit->itemSnapshot($item);

        $fill = [];

        if ($request->filled('weight')) {
            $fill['weight'] = normalizeDispatchItemSize($request->input('weight'));
        }

        if ($request->filled('pay_mode') || $request->exists('deli_amount') || $request->exists('item_value')) {
            $payMode = (string) ($request->input('pay_mode') ?: $this->inferPickupPayMode($item));
            $deliAmount = $request->exists('deli_amount')
                ? (float) $request->input('deli_amount')
                : (float) ($item->deli_amount ?? 0);
            $itemValue = $request->exists('item_value')
                ? (float) $request->input('item_value')
                : (float) ($item->item_value ?? 0);

            if ($payMode === 'pay_done') {
                $creditTo = 'os';
                $osPaid = $deliAmount;
            } elseif ($payMode === 'os_pay') {
                $creditTo = 'os';
                $osPaid = 0;
            } else {
                $creditTo = 'customer';
                $osPaid = 0;
            }

            $amounts = DispatchOrderItem::computeAmounts(
                $itemValue,
                $deliAmount,
                (float) ($item->advance_paid ?? 0),
                $osPaid,
                $creditTo
            );

            $fill['item_value'] = $itemValue;
            $fill['deli_amount'] = $deliAmount;
            $fill['credit_to'] = $creditTo;
            $fill['os_paid'] = $osPaid;
            $fill['pickup_pay_mode'] = $payMode;
            $fill['cust_get'] = $amounts['cust_get'];
            $fill['os_to_pay'] = $amounts['os_to_pay'];
        }

        if (! empty($fill)) {
            $item->forceFill($fill)->save();
            $audit->logRiderItemInfo($order, $item->fresh(), null, $before);
        }

        $item->load(['fromBranch', 'toBranch', 'photoMedia']);

        return json_custom_response([
            'status' => true,
            'message' => __('message.update_form', ['form' => __('message.size')]),
            'data' => (new DispatchOrderItemResource($item))->resolve(),
        ]);
    }

    private function inferPickupPayMode(DispatchOrderItem $item): string
    {
        $stored = (string) ($item->pickup_pay_mode ?? '');
        if (in_array($stored, ['os_pay', 'customer_pay', 'pay_done'], true)) {
            return $stored;
        }

        if ((float) ($item->os_paid ?? 0) > 0) {
            return 'pay_done';
        }

        return (($item->credit_to ?? 'customer') === 'os') ? 'os_pay' : 'customer_pay';
    }

    /**
     * Rider adds an extra parcel during pickup (photo + size only).
     */
    public function addPickupItem(Request $request, $orderId)
    {
        $order = $this->resolveRiderPickupOrder($orderId);
        if (! $order instanceof Order) {
            return $order;
        }

        $pickupService = app(PickupParcelDispatchService::class);

        if (! $pickupService->isDispatchPickupOrder($order)) {
            return json_custom_response([
                'status' => false,
                'message' => __('message.not_found_entry', ['name' => __('message.order')]),
            ], 422);
        }

        if (! in_array($order->status, ['courier_assigned', 'courier_arrived', 'active', 'create'], true)) {
            return json_custom_response([
                'status' => false,
                'message' => __('message.order_not_editable'),
            ], 422);
        }

        // Seed base parcels first so gate/shop bulk orders don't lose original count.
        $pickupService->ensureDispatchItems($order->fresh());
        $order = $order->fresh();

        $existing = DispatchOrderItem::query()
            ->where('order_id', $order->id)
            ->where('status', 'collected')
            ->orderBy('id')
            ->get();

        if ($existing->isEmpty()) {
            $baseCount = max(1, (int) $order->total_parcel);
            $delivery = is_array($order->delivery_point) ? $order->delivery_point : [];
            $itemPrefix = (int) ($order->is_gate_order ?? 0) === 1
                ? 'Gate Parcel'
                : ((int) ($order->is_shop_order ?? 0) === 1 ? 'Shop Parcel' : 'Parcel');

            for ($i = 1; $i <= $baseCount; $i++) {
                DispatchOrderItem::create([
                    'order_id' => $order->id,
                    'photo_id' => 0,
                    'received_date' => $order->pickup_datetime ?? $order->created_at ?? now(),
                    'status' => 'collected',
                    'code' => DispatchOrderItem::generateCode(),
                    'item_name' => $itemPrefix.' '.$i,
                    'customer_name' => trim((string) ($delivery['name'] ?? '')),
                    'customer_phone' => normalizeContactNumber(trim((string) ($delivery['contact_number'] ?? ''))),
                    'customer_address' => trim((string) ($delivery['address'] ?? '')),
                    'weight' => 0,
                    'item_value' => 0,
                    'deli_amount' => 0,
                    'advance_paid' => 0,
                    'os_paid' => 0,
                    'credit_to' => 'customer',
                ]);
            }

            $existing = DispatchOrderItem::query()
                ->where('order_id', $order->id)
                ->where('status', 'collected')
                ->orderBy('id')
                ->get();
        }

        $extraNumber = $existing->filter(fn ($row) => ($row->remark ?? '') === 'rider_added')->count() + 1;
        $template = $existing->first();
        $previousTotal = max(1, (int) $order->total_parcel);

        $item = DispatchOrderItem::create([
            'order_id' => $order->id,
            'photo_id' => 0,
            'received_date' => $order->pickup_datetime ?? $order->created_at ?? now(),
            'status' => 'collected',
            'code' => DispatchOrderItem::generateCode(),
            'item_name' => 'ထပ်တိုး Parcel '.$extraNumber,
            'remark' => 'rider_added',
            'from_branch_id' => $template?->from_branch_id,
            'to_branch_id' => $template?->to_branch_id,
            'delivery_city' => $template?->delivery_city,
            'township' => $template?->township,
            'weight' => 0,
            'item_value' => 0,
            'deli_amount' => 0,
            'advance_paid' => 0,
            'os_paid' => 0,
            'credit_to' => 'customer',
        ]);

        $collectedCount = DispatchOrderItem::query()
            ->where('order_id', $order->id)
            ->where('status', 'collected')
            ->count();

        $order->forceFill([
            'total_parcel' => max($previousTotal + 1, $collectedCount),
        ])->save();

        $item->load(['fromBranch', 'toBranch', 'photoMedia']);

        try {
            app(DispatchOrderAuditService::class)->logItemCreated($order, $item, 'rider');
        } catch (\Throwable $e) {
            \Log::warning('dispatch audit failed after rider item add', [
                'order_id' => $order->id,
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
        }

        return json_custom_response([
            'status' => true,
            'message' => __('message.save_form', ['form' => __('message.item')]),
            'data' => (new DispatchOrderItemResource($item))->resolve(),
        ]);
    }

    /**
     * Client (User App) lists collected items for a text order.
     */
    public function listClientItems(Request $request, $orderId)
    {
        $order = Order::query()
            ->with('client')
            ->where('client_id', auth()->id())
            ->findOrFail($orderId);

        if ((int) ($order->is_text_order ?? 0) !== 1) {
            return json_custom_response([
                'status' => false,
                'message' => __('message.not_found_entry', ['name' => __('message.order')]),
            ], 422);
        }

        $workflow = app(DispatchOrderWorkflowService::class);

        $items = DispatchOrderItem::query()
            ->where('order_id', $order->id)
            ->whereIn('status', $workflow->clientVisibleItemStatuses())
            ->where(function ($query) {
                $query->whereNull('remark')->orWhere('remark', '!=', 'rider_added');
            })
            ->orderBy('id')
            ->get();

        $items->load(['fromBranch', 'toBranch', 'photoMedia']);

        return json_custom_response([
            'status' => true,
            'order_id' => (int) $order->id,
            'os_name' => optional($order->client)->name
                ?? (is_array($order->pickup_point) ? ($order->pickup_point['name'] ?? '') : ''),
            'item_count' => $items->count(),
            'total_parcel' => $items->count(),
            'is_full' => false,
            'can_edit' => $workflow->clientItemsEditable($order),
            'os_pay_total' => (float) $items->sum('os_to_pay'),
            'customer_pay_total' => (float) $items->sum('cust_get'),
            'data' => DispatchOrderItemResource::collection($items)->resolve(),
        ]);
    }

    /**
     * Client (User App) adds a collected item (customer + Os/Customer pay).
     */
    public function addClientItem(Request $request, $orderId)
    {
        $order = Order::query()
            ->where('client_id', auth()->id())
            ->findOrFail($orderId);

        if ((int) ($order->is_text_order ?? 0) !== 1) {
            return json_custom_response([
                'status' => false,
                'message' => __('message.not_found_entry', ['name' => __('message.order')]),
            ], 422);
        }

        if (! app(DispatchOrderWorkflowService::class)->clientItemsEditable($order)) {
            return json_custom_response([
                'status' => false,
                'message' => __('message.order_not_editable'),
            ], 422);
        }

        $data = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:50',
            'customer_address' => 'required|string|max:500',
            'item_value' => 'nullable|numeric|min:0',
            'deli_amount' => 'nullable|numeric|min:0',
            'credit_to' => 'nullable|in:os,customer',
            'os_paid' => 'nullable|numeric|min:0',
            // Legacy fields from older User App builds.
            'os_pay' => 'nullable|numeric|min:0',
            'customer_pay' => 'nullable|numeric|min:0',
            'remark' => 'nullable|string|max:2000',
        ]);

        $creditToRaw = $data['credit_to'] ?? 'customer';
        $creditTo = in_array($creditToRaw, ['os', 'customer'], true) ? $creditToRaw : 'customer';

        // Prefer Admin-style fields; fall back to legacy os_pay / customer_pay.
        $itemValue = array_key_exists('item_value', $data)
            ? (float) ($data['item_value'] ?? 0)
            : (float) ($data['customer_pay'] ?? 0);
        $deliAmount = (float) ($data['deli_amount'] ?? 0);
        $osPaid = $creditTo === 'os'
            ? (float) ($data['os_paid'] ?? $data['os_pay'] ?? 0)
            : 0;
        $advancePaid = 0;

        $amounts = DispatchOrderItem::computeAmounts(
            $itemValue,
            $deliAmount,
            $advancePaid,
            $osPaid,
            $creditTo
        );

        $branchId = \App\Models\Branch::query()
            ->where('status', 1)
            ->where('name', config('dispatch_item_cities.default_from_branch', 'MDY'))
            ->value('id');

        $existingCount = DispatchOrderItem::query()
            ->where('order_id', $order->id)
            ->where('status', 'collected')
            ->where(function ($query) {
                $query->whereNull('remark')->orWhere('remark', '!=', 'rider_added');
            })
            ->count();

        $item = DispatchOrderItem::create([
            'order_id' => $order->id,
            'photo_id' => 0,
            'received_date' => $order->pickup_datetime ?? $order->created_at ?? now(),
            'status' => 'collected',
            'code' => DispatchOrderItem::generateCode(),
            'item_name' => 'Parcel ' . ($existingCount + 1),
            'remark' => stripAutoOrderRemarkTip($data['remark'] ?? null),
            'from_branch_id' => $branchId,
            'to_branch_id' => $branchId,
            'delivery_city' => config('dispatch_item_cities.default_delivery_city', 'Mandalay'),
            'township' => config('dispatch_item_cities.default_township', 'ချမ်းမြသာစည်'),
            'customer_name' => trim($data['customer_name']),
            'customer_phone' => normalizeContactNumber(trim($data['customer_phone'])),
            'customer_address' => trim($data['customer_address']),
            'item_value' => $itemValue,
            'deli_amount' => $deliAmount,
            'advance_paid' => $advancePaid,
            'os_paid' => $osPaid,
            'credit_to' => $creditTo,
            'weight' => 0,
            'cust_get' => $amounts['cust_get'],
            'os_to_pay' => $amounts['os_to_pay'],
        ]);

        try {
            app(DispatchOrderAuditService::class)->logItemCreated($order, $item, 'client');
        } catch (\Throwable $e) {
            \Log::warning('dispatch audit failed after client item add', [
                'order_id' => $order->id,
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
        }

        $clientItems = DispatchOrderItem::query()
            ->where('order_id', $order->id)
            ->where('status', 'collected')
            ->where(function ($query) {
                $query->whereNull('remark')->orWhere('remark', '!=', 'rider_added');
            })
            ->get();

        $order->forceFill([
            'total_parcel' => $clientItems->count(),
            'total_amount' => (float) $order->dispatchItems()->sum('deli_amount'),
        ])->save();
        $order->touch();

        app(DispatchOrderWorkflowService::class)->syncOrderWorkflow($order->fresh());

        $item->load(['fromBranch', 'toBranch', 'photoMedia']);

        return json_custom_response([
            'status' => true,
            'message' => __('message.save_form', ['form' => __('message.item_name')]),
            'item_count' => $clientItems->count(),
            'total_parcel' => $clientItems->count(),
            'is_full' => false,
            'os_pay_total' => (float) $clientItems->sum('os_to_pay'),
            'customer_pay_total' => (float) $clientItems->sum('cust_get'),
            'data' => (new DispatchOrderItemResource($item))->resolve(),
        ]);
    }

    /**
     * Client (User App) updates a collected item.
     */
    public function updateClientItem(Request $request, $orderId, $itemId)
    {
        $order = Order::query()
            ->where('client_id', auth()->id())
            ->findOrFail($orderId);

        if ((int) ($order->is_text_order ?? 0) !== 1) {
            return json_custom_response([
                'status' => false,
                'message' => __('message.not_found_entry', ['name' => __('message.order')]),
            ], 422);
        }

        if (! app(DispatchOrderWorkflowService::class)->clientItemsEditable($order)) {
            return json_custom_response([
                'status' => false,
                'message' => __('message.order_not_editable'),
            ], 422);
        }

        $item = DispatchOrderItem::query()
            ->where('order_id', $order->id)
            ->where('id', $itemId)
            ->where('status', 'collected')
            ->where(function ($query) {
                $query->whereNull('remark')->orWhere('remark', '!=', 'rider_added');
            })
            ->firstOrFail();

        $audit = app(DispatchOrderAuditService::class);
        $before = $audit->itemSnapshot($item);

        $data = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:50',
            'customer_address' => 'required|string|max:500',
            'item_value' => 'nullable|numeric|min:0',
            'deli_amount' => 'nullable|numeric|min:0',
            'credit_to' => 'nullable|in:os,customer',
            'os_paid' => 'nullable|numeric|min:0',
            'os_pay' => 'nullable|numeric|min:0',
            'customer_pay' => 'nullable|numeric|min:0',
            'remark' => 'nullable|string|max:2000',
        ]);

        $creditToRaw = $data['credit_to'] ?? 'customer';
        $creditTo = in_array($creditToRaw, ['os', 'customer'], true) ? $creditToRaw : 'customer';

        $itemValue = array_key_exists('item_value', $data)
            ? (float) ($data['item_value'] ?? 0)
            : (float) ($data['customer_pay'] ?? 0);
        $deliAmount = (float) ($data['deli_amount'] ?? 0);
        $osPaid = $creditTo === 'os'
            ? (float) ($data['os_paid'] ?? $data['os_pay'] ?? 0)
            : 0;

        $amounts = DispatchOrderItem::computeAmounts(
            $itemValue,
            $deliAmount,
            0,
            $osPaid,
            $creditTo
        );

        $item->forceFill([
            'remark' => stripAutoOrderRemarkTip($data['remark'] ?? null),
            'customer_name' => trim($data['customer_name']),
            'customer_phone' => normalizeContactNumber(trim($data['customer_phone'])),
            'customer_address' => trim($data['customer_address']),
            'item_value' => $itemValue,
            'deli_amount' => $deliAmount,
            'os_paid' => $osPaid,
            'credit_to' => $creditTo,
            'cust_get' => $amounts['cust_get'],
            'os_to_pay' => $amounts['os_to_pay'],
        ])->save();

        try {
            $audit->logOsItemInfo($order, $item->fresh(), null, $before);
        } catch (\Throwable $e) {
            \Log::warning('dispatch audit failed after client item update', [
                'order_id' => $order->id,
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
        }

        $clientCount = DispatchOrderItem::query()
            ->where('order_id', $order->id)
            ->where('status', 'collected')
            ->where(function ($query) {
                $query->whereNull('remark')->orWhere('remark', '!=', 'rider_added');
            })
            ->count();

        $order->forceFill([
            'total_parcel' => $clientCount,
            'total_amount' => (float) $order->dispatchItems()->sum('deli_amount'),
        ])->save();
        $order->touch();

        app(DispatchOrderWorkflowService::class)->syncOrderWorkflow($order->fresh());

        $item->load(['fromBranch', 'toBranch', 'photoMedia']);

        return json_custom_response([
            'status' => true,
            'message' => __('message.update_form', ['form' => __('message.item_name')]),
            'data' => (new DispatchOrderItemResource($item))->resolve(),
        ]);
    }

    /**
     * Client (User App) deletes a collected item.
     */
    public function deleteClientItem(Request $request, $orderId, $itemId)
    {
        $order = Order::query()
            ->where('client_id', auth()->id())
            ->findOrFail($orderId);

        if ((int) ($order->is_text_order ?? 0) !== 1) {
            return json_custom_response([
                'status' => false,
                'message' => __('message.not_found_entry', ['name' => __('message.order')]),
            ], 422);
        }

        if (! app(DispatchOrderWorkflowService::class)->clientItemsEditable($order)) {
            return json_custom_response([
                'status' => false,
                'message' => __('message.order_not_editable'),
            ], 422);
        }

        $item = DispatchOrderItem::query()
            ->where('order_id', $order->id)
            ->where('id', $itemId)
            ->where('status', 'collected')
            ->where(function ($query) {
                $query->whereNull('remark')->orWhere('remark', '!=', 'rider_added');
            })
            ->firstOrFail();

        $audit = app(DispatchOrderAuditService::class);
        $itemMeta = [
            'id' => $item->id,
            'code' => $item->code,
            'snapshot' => $audit->itemSnapshot($item),
        ];
        $item->delete();

        try {
            $audit->logItemDeleted($order, $itemMeta, 'client');
        } catch (\Throwable $e) {
            \Log::warning('dispatch audit failed after client item delete', [
                'order_id' => $order->id,
                'item_id' => $itemMeta['id'],
                'error' => $e->getMessage(),
            ]);
        }

        $clientCount = DispatchOrderItem::query()
            ->where('order_id', $order->id)
            ->where('status', 'collected')
            ->where(function ($query) {
                $query->whereNull('remark')->orWhere('remark', '!=', 'rider_added');
            })
            ->count();

        $order->forceFill([
            'total_parcel' => $clientCount,
            'total_amount' => (float) $order->dispatchItems()->sum('deli_amount'),
        ])->save();
        $order->touch();

        app(DispatchOrderWorkflowService::class)->syncOrderWorkflow($order->fresh());

        return json_custom_response([
            'status' => true,
            'message' => __('message.delete_form', ['form' => __('message.item_name')]),
            'item_count' => $clientCount,
            'total_parcel' => $clientCount,
            'is_full' => false,
        ]);
    }

    /**
     * Rider Delivery list — items assigned via Assign 100 (item.delivery_man_id).
     * Day-by-day by default (Asia/Yangon today) via from_date / to_date.
     */
    public function listDeliveryItems(Request $request)
    {
        $user = auth()->user();
        if (! $user || $user->user_type !== 'delivery_man') {
            return json_custom_response([
                'status' => false,
                'message' => __('message.demo_permission_denied'),
            ], 403);
        }

        $workflow = app(DispatchOrderWorkflowService::class);
        $statuses = $workflow->deliveryItemStatuses();
        $statusFilter = trim((string) $request->get('status', 'all'));

        // Day-by-day: default to Yangon today when dates are omitted.
        $yangonToday = Carbon::now('Asia/Yangon')->toDateString();
        $fromDay = $this->parseDeliveryListDay($request->get('from_date')) ?? $yangonToday;
        $toDay = $this->parseDeliveryListDay($request->get('to_date')) ?? $fromDay;
        if ($toDay < $fromDay) {
            $toDay = $fromDay;
        }

        $baseQuery = DispatchOrderItem::query()
            ->where('delivery_man_id', $user->id)
            ->whereIn('status', $statuses);
        $this->applyDeliveryListDayFilter($baseQuery, $fromDay, $toDay);

        $counts = [
            'all' => (clone $baseQuery)->count(),
            'courier_assigned' => (clone $baseQuery)->where('status', 'courier_assigned')->count(),
            'courier_departed' => (clone $baseQuery)->where('status', 'courier_departed')->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
        ];

        $query = DispatchOrderItem::query()
            ->where('delivery_man_id', $user->id)
            ->whereIn('status', $statuses)
            ->with(['fromBranch', 'toBranch', 'photoMedia', 'pendingPhotoMedia', 'deliveryMan', 'order.city'])
            ->orderByDesc('assigned_at')
            ->orderByDesc('id');
        $this->applyDeliveryListDayFilter($query, $fromDay, $toDay);

        if ($statusFilter !== '' && $statusFilter !== 'all') {
            if (! in_array($statusFilter, $statuses, true)) {
                return json_custom_response([
                    'status' => false,
                    'message' => __('message.delivery_item_status_not_allowed'),
                ], 422);
            }
            $query->where('status', $statusFilter);
        }

        $perPage = config('constant.PER_PAGE_LIMIT', 20);
        if ($request->filled('per_page') && is_numeric($request->per_page)) {
            $perPage = (int) $request->per_page;
        }
        if ((int) $request->get('per_page') === -1) {
            $perPage = max(1, $query->count());
        }

        $page = $query->paginate($perPage);
        $items = DispatchOrderItemResource::collection($page);

        return json_custom_response([
            'pagination' => json_pagination_response($items),
            'data' => $items,
            'counts' => $counts,
            'from_date' => $fromDay,
            'to_date' => $toDay,
        ]);
    }

    /**
     * Client (User App) delivery items — Assigned / On Way / Pending / Delivered.
     * Scoped to orders owned by the authenticated client; day-by-day filter.
     */
    public function listClientDeliveryItems(Request $request)
    {
        $user = auth()->user();
        if (! $user || $user->user_type !== 'client') {
            return json_custom_response([
                'status' => false,
                'message' => __('message.demo_permission_denied'),
            ], 403);
        }

        $workflow = app(DispatchOrderWorkflowService::class);
        $statuses = $workflow->clientDeliveryItemStatuses();
        $statusFilter = trim((string) $request->get('status', 'all'));

        $yangonToday = Carbon::now('Asia/Yangon')->toDateString();
        $fromDay = $this->parseDeliveryListDay($request->get('from_date')) ?? $yangonToday;
        $toDay = $this->parseDeliveryListDay($request->get('to_date')) ?? $fromDay;
        if ($toDay < $fromDay) {
            $toDay = $fromDay;
        }

        $baseQuery = DispatchOrderItem::query()
            ->whereIn('status', $statuses)
            ->whereHas('order', function ($q) use ($user) {
                $q->where('client_id', $user->id);
            });
        $this->applyDeliveryListDayFilter($baseQuery, $fromDay, $toDay);

        $counts = [
            'all' => (clone $baseQuery)->count(),
            'assigned' => (clone $baseQuery)->where('status', 'assigned')->count(),
            'assigned_active' => (clone $baseQuery)->whereIn('status', ['assigned', 'courier_assigned'])->count(),
            'courier_assigned' => (clone $baseQuery)->where('status', 'courier_assigned')->count(),
            'courier_departed' => (clone $baseQuery)->where('status', 'courier_departed')->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'completed' => (clone $baseQuery)->where('status', 'completed')->whereNull('admin_completed_at')->count(),
            'delivered' => (clone $baseQuery)->where('status', 'completed')->whereNull('admin_completed_at')->count(),
            'admin_completed' => (clone $baseQuery)->where('status', 'completed')->whereNotNull('admin_completed_at')->whereNull('admin_finished_at')->count(),
            'finished' => (clone $baseQuery)->where('status', 'completed')->whereNotNull('admin_finished_at')->count(),
            'undelivered' => (clone $baseQuery)->whereIn('status', ['assigned', 'courier_assigned', 'courier_departed', 'pending'])->count(),
        ];

        $query = DispatchOrderItem::query()
            ->whereIn('status', $statuses)
            ->whereHas('order', function ($q) use ($user) {
                $q->where('client_id', $user->id);
            })
            ->with(['fromBranch', 'toBranch', 'photoMedia', 'pendingPhotoMedia', 'deliveryMan', 'order.city'])
            ->orderByDesc('assigned_at')
            ->orderByDesc('id');
        $this->applyDeliveryListDayFilter($query, $fromDay, $toDay);

        if ($statusFilter === 'undelivered') {
            $query->whereIn('status', ['assigned', 'courier_assigned', 'courier_departed', 'pending']);
        } elseif ($statusFilter === 'assigned_active') {
            // Assign 100 pool + delivery Assigned (OS undelivered "Assigned" tab).
            $query->whereIn('status', ['assigned', 'courier_assigned']);
        } elseif (in_array($statusFilter, ['delivered', 'completed'], true)) {
            // Rider-delivered, not yet admin Completed/Finished.
            $query->where('status', 'completed')->whereNull('admin_completed_at');
        } elseif ($statusFilter === 'admin_completed') {
            $query->where('status', 'completed')
                ->whereNotNull('admin_completed_at')
                ->whereNull('admin_finished_at');
        } elseif ($statusFilter === 'finished') {
            $query->where('status', 'completed')->whereNotNull('admin_finished_at');
        } elseif ($statusFilter !== '' && $statusFilter !== 'all') {
            if (! in_array($statusFilter, $statuses, true)) {
                return json_custom_response([
                    'status' => false,
                    'message' => __('message.delivery_item_status_not_allowed'),
                ], 422);
            }
            $query->where('status', $statusFilter);
        }

        $perPage = config('constant.PER_PAGE_LIMIT', 20);
        if ($request->filled('per_page') && is_numeric($request->per_page)) {
            $perPage = (int) $request->per_page;
        }
        if ((int) $request->get('per_page') === -1) {
            $perPage = max(1, $query->count());
        }

        $page = $query->paginate($perPage);
        DispatchItemMessage::attachClientChatMeta($page->getCollection(), (int) $user->id);
        $items = DispatchOrderItemResource::collection($page);

        return json_custom_response([
            'pagination' => json_pagination_response($items),
            'data' => $items,
            'counts' => $counts,
            'from_date' => $fromDay,
            'to_date' => $toDay,
        ]);
    }

    /**
     * Client — Assign 100 items for a pickup notification.
     * Pass order_id to load that pickup's items (history kept after Finished).
     * Without order_id: live pool still in Assign 100 today.
     */
    public function listClientAssign100Today(Request $request)
    {
        $user = auth()->user();
        if (! $user || $user->user_type !== 'client') {
            return json_custom_response([
                'status' => false,
                'message' => __('message.demo_permission_denied'),
            ], 403);
        }

        $yangonToday = Carbon::now('Asia/Yangon')->toDateString();
        $push = app(\App\Services\AppPushService::class);
        $orderId = (int) $request->get('order_id', 0);

        if ($orderId > 0) {
            $ownsOrder = Order::query()
                ->where('id', $orderId)
                ->where('client_id', $user->id)
                ->exists();
            if (! $ownsOrder) {
                return json_custom_response([
                    'status' => false,
                    'message' => __('message.not_found_entry', ['name' => __('message.order')]),
                ], 404);
            }
            $query = $push->clientAssign100OrderItemsQuery($user->id, $orderId);
        } else {
            $query = $push->clientAssign100TodayItemsQuery($user->id);
        }

        $items = $query
            ->with(['fromBranch', 'toBranch', 'photoMedia', 'pendingPhotoMedia', 'deliveryMan', 'order.city'])
            ->orderByDesc('assigned_at')
            ->orderByDesc('id')
            ->get();

        $itemCount = $items->count();
        $orderCount = $items->pluck('order_id')->unique()->filter()->count();

        return json_custom_response([
            'status' => true,
            'date' => $yangonToday,
            'order_id' => $orderId > 0 ? $orderId : null,
            'order_count' => $orderCount,
            'item_count' => $itemCount,
            'disclaimer' => trans('message.assign100_today_disclaimer', [], 'my'),
            'title' => trans('message.assign100_today_title', [], 'my'),
            'message' => trans('message.push_pickup_ready_title', ['count' => max(0, $itemCount)], 'my'),
            'data' => DispatchOrderItemResource::collection($items),
        ]);
    }

    /**
     * Client — single delivery item (notification deep link).
     */
    public function showClientDeliveryItem($itemId)
    {
        $user = auth()->user();
        if (! $user || $user->user_type !== 'client') {
            return json_custom_response([
                'status' => false,
                'message' => __('message.demo_permission_denied'),
            ], 403);
        }

        $workflow = app(DispatchOrderWorkflowService::class);
        // Include Assign 100 pool (`assigned`) plus later delivery stages.
        $visibleStatuses = array_values(array_unique(array_merge(
            ['assigned'],
            $workflow->deliveryItemStatuses()
        )));
        $item = DispatchOrderItem::query()
            ->where('id', $itemId)
            ->whereIn('status', $visibleStatuses)
            ->whereHas('order', function ($q) use ($user) {
                $q->where('client_id', $user->id);
            })
            ->with(['fromBranch', 'toBranch', 'photoMedia', 'pendingPhotoMedia', 'deliveryMan', 'order.city'])
            ->first();

        if (! $item) {
            return json_custom_response([
                'status' => false,
                'message' => __('message.not_found_entry', ['name' => __('message.item_name')]),
            ], 404);
        }

        return json_custom_response([
            'status' => true,
            'data' => new DispatchOrderItemResource($item),
        ]);
    }

    /**
     * Rider — single delivery item (notification deep link).
     */
    public function showDeliveryItem($itemId)
    {
        $user = auth()->user();
        if (! $user || $user->user_type !== 'delivery_man') {
            return json_custom_response([
                'status' => false,
                'message' => __('message.demo_permission_denied'),
            ], 403);
        }

        $workflow = app(DispatchOrderWorkflowService::class);
        $item = DispatchOrderItem::query()
            ->where('id', $itemId)
            ->where('delivery_man_id', $user->id)
            ->whereIn('status', $workflow->deliveryItemStatuses())
            ->with(['fromBranch', 'toBranch', 'photoMedia', 'pendingPhotoMedia', 'deliveryMan', 'order.city'])
            ->first();

        if (! $item) {
            return json_custom_response([
                'status' => false,
                'message' => __('message.not_found_entry', ['name' => __('message.item_name')]),
            ], 404);
        }

        return json_custom_response([
            'status' => true,
            'data' => new DispatchOrderItemResource($item),
        ]);
    }

    /**
     * Parse Y-m-d or d-m-Y into Y-m-d (Asia/Yangon calendar day).
     */
    protected function parseDeliveryListDay($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        foreach (['Y-m-d', 'd-m-Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value, 'Asia/Yangon')->toDateString();
            } catch (\Throwable $e) {
                // try next
            }
        }

        try {
            return Carbon::parse($value, 'Asia/Yangon')->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Filter by received_date; fall back to assigned_at / created_at when received_date is null.
     */
    protected function applyDeliveryListDayFilter(Builder $query, string $fromDay, string $toDay): void
    {
        $query->where(function ($dateQuery) use ($fromDay, $toDay) {
            $dateQuery->whereBetween('received_date', [$fromDay, $toDay])
                ->orWhere(function ($fallback) use ($fromDay, $toDay) {
                    $fallback->whereNull('received_date')
                        ->where(function ($assigned) use ($fromDay, $toDay) {
                            $assigned->where(function ($q) use ($fromDay, $toDay) {
                                $q->whereNotNull('assigned_at')
                                    ->whereDate('assigned_at', '>=', $fromDay)
                                    ->whereDate('assigned_at', '<=', $toDay);
                            })->orWhere(function ($q) use ($fromDay, $toDay) {
                                $q->whereNull('assigned_at')
                                    ->whereDate('created_at', '>=', $fromDay)
                                    ->whereDate('created_at', '<=', $toDay);
                            });
                        });
                });
        });
    }

    /**
     * Rider updates a single Assign-100 delivery item status.
     * On Way → Pending requires remark + pending_photo (multipart).
     */
    public function updateDeliveryItemStatus(Request $request, $itemId)
    {
        $user = auth()->user();
        if (! $user || $user->user_type !== 'delivery_man') {
            return json_custom_response([
                'status' => false,
                'message' => __('message.demo_permission_denied'),
            ], 403);
        }

        $toStatus = (string) $request->input('status', '');
        $rules = [
            'status' => 'required|string|in:courier_departed,pending,completed',
            'remark' => 'nullable|string|max:1000',
            'pending_photo' => 'nullable|image|max:10240',
        ];
        if ($toStatus === 'pending') {
            $rules['remark'] = 'required|string|max:1000';
            $rules['pending_photo'] = 'required|image|max:10240';
        }
        $request->validate($rules);

        $item = DispatchOrderItem::query()
            ->where('id', $itemId)
            ->where('delivery_man_id', $user->id)
            ->first();

        if (! $item) {
            return json_custom_response([
                'status' => false,
                'message' => __('message.not_found_entry', ['name' => __('message.item_name')]),
            ], 404);
        }

        $workflow = app(DispatchOrderWorkflowService::class);
        $remark = $request->filled('remark') ? trim((string) $request->remark) : null;

        try {
            $workflow->assertDeliveryStatusTransition($item, $toStatus, $remark);
        } catch (\InvalidArgumentException $e) {
            return json_custom_response([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $fromStatus = (string) $item->status;
        $fill = ['status' => $toStatus];
        if ($toStatus === 'pending' && $remark !== null && $remark !== '') {
            $fill['remark'] = $remark;
        }

        // Any transition to Delivered is final — lock so status cannot change again.
        if ($toStatus === 'completed') {
            $fill['delivery_locked'] = true;
        }

        if ($toStatus === 'pending') {
            if (! $request->hasFile('pending_photo')) {
                return json_custom_response([
                    'status' => false,
                    'message' => 'Pending photo is required.',
                ], 422);
            }
            $pendingPhotoId = $this->storePendingRemarkPhoto($item, $request->file('pending_photo'));
            if ($pendingPhotoId <= 0) {
                return json_custom_response([
                    'status' => false,
                    'message' => 'Unable to save pending photo.',
                ], 422);
            }
            $fill['pending_photo_id'] = $pendingPhotoId;
        }

        $item->forceFill($fill)->save();
        $item = $item->fresh(['fromBranch', 'toBranch', 'photoMedia', 'pendingPhotoMedia', 'deliveryMan', 'order.city']);

        $order = $item->order;
        if ($order) {
            try {
                app(DispatchOrderAuditService::class)->logDeliveryItemStatus(
                    $order,
                    $item,
                    $fromStatus,
                    $toStatus,
                    $user,
                    $remark
                );
            } catch (\Throwable $e) {
                \Log::warning('dispatch audit failed after delivery item status', [
                    'order_id' => $order->id,
                    'item_id' => $item->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Keep order history for User App / Admin timeline when status moves.
            // Item-level Pending/Delivered push is handled by AppPushService (skip_push).
            if (in_array($toStatus, ['courier_departed', 'completed'], true)) {
                try {
                    saveOrderHistory([
                        'history_type' => $toStatus,
                        'order_id' => $order->id,
                        'order' => $order,
                        'skip_push' => $toStatus === 'completed',
                    ]);
                } catch (\Throwable $e) {
                    \Log::warning('order history failed after delivery item status', [
                        'order_id' => $order->id,
                        'item_id' => $item->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            try {
                $push = app(\App\Services\AppPushService::class);
                if ($toStatus === 'pending') {
                    $push->notifyClientItemPending($item);
                } elseif ($toStatus === 'completed') {
                    $push->notifyClientItemDelivered($item);
                }
            } catch (\Throwable $e) {
                \Log::warning('push failed after delivery item status', [
                    'order_id' => $order->id,
                    'item_id' => $item->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return json_custom_response([
            'status' => true,
            'message' => __('message.delivery_item_status_updated'),
            'data' => new DispatchOrderItemResource($item),
        ]);
    }

    /**
     * Store pending-remark image on Profofpictures and return Spatie media id.
     */
    protected function storePendingRemarkPhoto(DispatchOrderItem $item, $file): int
    {
        if (! $file) {
            return 0;
        }

        $profpicture = \App\Models\Profofpictures::create([
            'order_id' => $item->order_id,
            'type' => 'pending_remark',
        ]);
        $profpicture->addMedia($file)->toMediaCollection('prof_file');
        $media = $profpicture->getMedia('prof_file')->first();

        return $media ? (int) $media->id : 0;
    }

    /**
     * Rider search — Assign 100 pool (status=assigned, no delivery rider yet).
     * Match Customer Name / Phone / Voucher Code (item.code).
     */
    public function searchAssignableDeliveryItems(Request $request)
    {
        $user = auth()->user();
        if (! $user || $user->user_type !== 'delivery_man') {
            return json_custom_response([
                'status' => false,
                'message' => __('message.demo_permission_denied'),
            ], 403);
        }

        $q = trim((string) $request->get('q', ''));
        if (mb_strlen($q) < 2) {
            return json_custom_response([
                'status' => false,
                'message' => 'Please enter at least 2 characters to search.',
            ], 422);
        }

        app(DispatchOrderWorkflowService::class)->reclaimPrematureAssign100Items();

        $like = '%'.$q.'%';
        $query = DispatchOrderItem::query()
            ->where('status', 'assigned')
            ->where(function ($builder) use ($like, $q) {
                $builder->where('customer_name', 'like', $like)
                    ->orWhere('customer_phone', 'like', $like)
                    ->orWhere('code', 'like', $like);

                // Digits-only phone search (strip spaces/dashes from stored value).
                $digits = preg_replace('/\D+/', '', $q);
                if ($digits !== null && $digits !== '' && strlen($digits) >= 2) {
                    $builder->orWhereRaw(
                        "REPLACE(REPLACE(REPLACE(COALESCE(customer_phone,''), ' ', ''), '-', ''), '+', '') LIKE ?",
                        ['%'.$digits.'%']
                    );
                }
            })
            ->with(['fromBranch', 'toBranch', 'photoMedia', 'deliveryMan', 'order.city'])
            ->orderByDesc('assigned_at')
            ->orderByDesc('id');

        $perPage = config('constant.PER_PAGE_LIMIT', 20);
        if ($request->filled('per_page') && is_numeric($request->per_page)) {
            $perPage = max(1, min(50, (int) $request->per_page));
        }

        $page = $query->paginate($perPage);
        $items = DispatchOrderItemResource::collection($page);

        return json_custom_response([
            'pagination' => json_pagination_response($items),
            'data' => $items,
        ]);
    }

    /**
     * Rider self-assign — take one Assign 100 item onto current rider (→ Assigned).
     * Removes it from Admin Assign 100 automatically (status leaves `assigned`).
     */
    public function assignDeliveryItemToMe(Request $request, $itemId)
    {
        $user = auth()->user();
        if (! $user || $user->user_type !== 'delivery_man') {
            return json_custom_response([
                'status' => false,
                'message' => __('message.demo_permission_denied'),
            ], 403);
        }

        if ((int) $user->status !== 1) {
            return json_custom_response([
                'status' => false,
                'message' => __('message.demo_permission_denied'),
            ], 403);
        }

        app(DispatchOrderWorkflowService::class)->reclaimPrematureAssign100Items();

        $item = DispatchOrderItem::query()
            ->where('id', $itemId)
            ->where('status', 'assigned')
            ->first();

        if (! $item) {
            return json_custom_response([
                'status' => false,
                'message' => __('message.not_found_entry', ['name' => __('message.item_name')]),
            ], 404);
        }

        $updated = DispatchOrderItem::query()
            ->where('id', $item->id)
            ->where('status', 'assigned')
            ->update([
                'status' => 'courier_assigned',
                'delivery_man_id' => $user->id,
                'assigned_at' => now(),
                // Show on rider Assigned tab for today (day-by-day list).
                'received_date' => Carbon::now('Asia/Yangon')->toDateString(),
            ]);

        if ($updated === 0) {
            return json_custom_response([
                'status' => false,
                'message' => __('message.no_record_found'),
            ], 422);
        }

        $item = $item->fresh(['fromBranch', 'toBranch', 'photoMedia', 'deliveryMan', 'order.city']);
        $order = $item?->order;

        if ($order) {
            try {
                app(DispatchOrderAuditService::class)->logDeliveryRiderAssigned($order, $user, 1, $user);
            } catch (\Throwable $e) {
                \Log::warning('dispatch audit failed after rider self-assign', [
                    'order_id' => $order->id,
                    'item_id' => $item->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        try {
            app(\App\Services\AppPushService::class)->notifyRiderDeliveryAssigned($user, $item);
        } catch (\Throwable $e) {
            \Log::warning('push failed after rider self-assign', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
        }

        return json_custom_response([
            'status' => true,
            'message' => __('message.dispatch_delivery_man_assigned_success', [
                'count' => 1,
                'rider' => $user->name,
            ]),
            'data' => new DispatchOrderItemResource($item),
        ]);
    }
}
