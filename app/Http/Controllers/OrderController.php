<?php

namespace App\Http\Controllers;

use App\DataTables\ClientOrderDataTable;
use App\DataTables\DispatchOrderDataTable;
use App\DataTables\DispatchOrderItemDataTable;
use App\DataTables\OrderDataTable;
use App\DataTables\OrderPrintDataTable;
use App\DataTables\ShippedOrderDataTable;
use App\Http\Requests\OrderRequest;
use App\Http\Requests\VehicleHistoryRequest;
use Illuminate\Http\Request;
use App\Models\DispatchOrderItem;
use App\Models\OsSettlementBatch;
use App\Models\OsSettlementDraft;
use App\Models\Order;
use App\Services\OsSettlementService;
use App\Models\AppSetting;
use App\Models\Vehicle;
use App\Http\Resources\DeliverymanVehicleHistoryResource;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\StaticData;
use App\Models\User;
use App\Models\City;
use App\Models\Country;
use App\Models\OrderHistory;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Traits\OrderTrait;
use App\Traits\PaymentTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Picqer\Barcode\BarcodeGeneratorPNG;
use App\Imports\ImportOrderdata;
use App\Mail\sendmail;
use App\Models\CourierCompanies;
use App\Models\CustomerSupport;
use App\Models\DeliverymanVehicleHistory;
use App\Models\OrderBid;
use App\Models\OrderMail;
use App\Models\OrderVehicleHistory;
use App\Models\Profofpictures;
use App\Models\Ratings;
use App\Models\Reschedule;
use App\Models\Wallet;
use App\Models\WalletHistory;
use App\Services\PhotoOrderDispatchService;
use App\Services\TextOrderDispatchService;
use App\Services\DispatchOrderAuditService;
use App\Services\DispatchOrderWorkflowService;
use App\Notifications\CustomerSupportNotification;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;

class OrderController extends Controller
{
    use OrderTrait, PaymentTrait;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(OrderDataTable $dataTable, DispatchOrderDataTable $dispatchDataTable)
    {
        if (!auth()->user()->can('order-list')) {
            $message = __('message.demo_permission_denied');
            return redirect()->back()->withErrors($message);
        }
        $pageTitle = __('message.list_form_title', ['form' => __('message.order')]);
        $dispatchStatus = request('dispatch_status');
        if ($dispatchStatus === 'rider_pick_up_error') {
            $pageTitle = __('message.pickup_error_order_list');
        } elseif ($dispatchStatus === 'rider_pick_up_cancelled') {
            $pageTitle = __('message.pickup_cancelled_order_list');
        } elseif ($dispatchStatus === 'pre_order') {
            $pageTitle = __('message.pre_order_list');
        }
        $auth_user = authSession();
        $assets = ['datatable'];
        $params = [
            'status' => request('status') ?? null,
            'from_date' => request('from_date') ?? null,
            'to_date' => request('to_date') ?? null,
            'created_at' => request('created_at') ?? null,
            'city_id' => request('city_id') ?? null,
            'country_id' => request('country_id') ?? null,
            'dispatch_status' => $dispatchStatus,
        ];

        $multi_checkbox_delete = $auth_user->can('order-delete') ? '<button id="deleteSelectedBtn" checked-title = "order-checked " class="float-left btn btn-sm ">' . __('message.delete_selected') . '</button>' : '';

        if (auth()->user()->user_type !== 'client') {
            return $dispatchDataTable->render('order.dispatch-list', compact('pageTitle', 'auth_user', 'multi_checkbox_delete', 'params', 'assets'));
        }

        $filter_file_button = '<a href="' . route('filter.order.data', $params) . '" class=" mr-1 mt-1 btn btn-sm btn-success  text-dark loadRemoteModel"><i class="fas fa-filter"></i> ' . __('message.filter') . '</a>';
        $reset_file_button = '<a href="' . route('order.index') . '" class="float-right mr-1 mt-0 mb-1 btn btn-sm btn-info text-dark mt-1 pt-1 pb-1"><i class="ri-repeat-line" style="font-size:12px"></i> ' . __('message.reset_filter') . '</a>';

        return $dataTable->render('global.order-filter', compact('pageTitle', 'auth_user', 'multi_checkbox_delete', 'params', 'reset_file_button', 'filter_file_button'));
    }

    /**
     * Lightweight fingerprint for admin Order List live refresh (no page reload).
     */
    public function liveVersion()
    {
        if (! auth()->user()->can('order-list')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        // Release session lock immediately so navigations (e.g. Pre Pick Up)
        // are not blocked by continuous live-version polling on artisan serve.
        if (session()->isStarted()) {
            session()->save();
        }

        return response()->json([
            'version' => adminOrderListLiveVersion(),
        ]);
    }

    /**
     * Fingerprint for a single Order Detail List live refresh.
     */
    public function liveItemsVersion($id)
    {
        if (! auth()->user()->can('order-list')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        if (session()->isStarted()) {
            session()->save();
        }

        $order = Order::findOrFail($id);

        return response()->json([
            'version' => adminOrderItemsLiveVersion((int) $order->id),
        ]);
    }

    public function dispatchItemsDeliAudit($id, DispatchOrderAuditService $audit)
    {
        if (! auth()->user()->can('order-list')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        $order = Order::findOrFail($id);
        $entries = $audit->deliAmountTimelineForOrder($order);

        return response()->json([
            'order_id' => (int) $order->id,
            'entries' => $entries->values(),
        ]);
    }

    public function dispatchDeliAudit(Request $request, DispatchOrderAuditService $audit)
    {
        if (! auth()->user()->can('order-list')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        $yangonToday = now('Asia/Yangon')->format('d-m-Y');
        $fromRaw = trim((string) $request->get('from_date', $yangonToday));
        $toRaw = trim((string) $request->get('to_date', $fromRaw !== '' ? $fromRaw : $yangonToday));
        if ($fromRaw === '') {
            $fromRaw = $yangonToday;
        }
        if ($toRaw === '') {
            $toRaw = $fromRaw;
        }

        $fromDay = $this->parseDispatchDateInput($fromRaw)->toDateString();
        $toDay = $this->parseDispatchDateInput($toRaw)->toDateString();
        if ($toDay < $fromDay) {
            $toDay = $fromDay;
            $toRaw = $fromRaw;
        }

        $entries = $audit->deliAmountTimelineForRange($fromDay, $toDay);

        return response()->json([
            'from_date' => $fromRaw,
            'to_date' => $toRaw,
            'entries' => $entries->values(),
        ]);
    }

    public function orderprintindex(OrderPrintDataTable $dataTable)
    {
        $pageTitle = __('message.list_form_title', ['form' => __('message.print_order')]);
        $auth_user = authSession();
        $assets = ['datatable'];


        $multi_checkbox_print = $auth_user->can('order-list')
            ? '<div class="btn-group">
            <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                ' . __('message.print') . ' <i class="fa-solid fa-angle-down"></i>
            </button>
            <div class="dropdown-menu">
                <a class="dropdown-item" id="printsectionBtn">' . __('message.print_multiple') . '</a>
                <a class="dropdown-item" id="printLabelBtn">' . __('message.print_label') . '</a>
            </div>
        </div>'
            : '';

        return $dataTable->render('global.order-filter', compact('pageTitle', 'auth_user', 'multi_checkbox_print'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        if (!auth()->user()->can('order-add')) {
            $message = __('message.demo_permission_denied');
            return redirect()->back()->withErrors($message);
        }
        $pageTitle = __('message.add_form_title', ['form' => __('message.order')]);
        $assets = ['datatable'];
        $id = null;
        $data = null;

        if ($request->filled('order_id')) {
            $data = Order::with('delivery_man')->find($request->order_id);
            if ($data) {
                $id = $data->id;
            }
        }

        $pickupRiders = $this->getDispatchPickupRiders();
        $gatePassImages = $data ? collectGatePassImages($data) : [];

        return view('order.dispatch-form', compact('pageTitle', 'assets', 'data', 'id', 'pickupRiders', 'gatePassImages'));
    }

    public function dispatchStore(Request $request)
    {
        if (!auth()->user()->can('order-add')) {
            $message = __('message.demo_permission_denied');
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json(['status' => false, 'message' => $message], 403);
            }

            return redirect()->back()->withErrors($message);
        }

        $request->validate([
            'received_date' => 'required',
            'client_id' => 'required|exists:users,id',
            'os_name' => 'required|string|max:255',
            'os_phone' => 'required|string|max:50',
            'os_address' => 'required|string|max:500',
            'order_count' => 'required|integer|min:1',
            'delivery_man_id' => 'required|exists:users,id',
            'remark' => 'nullable|string|max:1000',
        ]);

        $client = User::findOrFail($request->client_id);
        $receivedDate = $this->parseDispatchDateInput($request->received_date);
        [$pickupDatetime, $deliveryDatetime] = $this->resolveDispatchSchedule($request, $receivedDate);
        $orderCount = max(1, (int) $request->order_count);

        $pickupPoint = [
            'name' => $request->os_name,
            'contact_number' => normalizeContactNumber($request->os_phone),
            'address' => $request->os_address,
        ];

        // Delivery/customer details are filled per item — never copy OS pickup into delivery.
        $deliveryPoint = [
            'name' => '',
            'contact_number' => '',
            'address' => '',
        ];

        // Admin New Order → Text Order so it appears in the User App "စာဖြင့်" list
        // and the OS account can continue filling parcel item details.
        $data = [
            'client_id' => $request->client_id,
            'pickup_point' => $pickupPoint,
            'delivery_point' => $deliveryPoint,
            'is_text_order' => 1,
            'is_photo_order' => 0,
            'is_shop_order' => 0,
            'is_gate_order' => 0,
            'parcel_type' => 'စာဖြင့် အော်ဒါ',
            'total_weight' => 1,
            'total_parcel' => $orderCount,
            'payment_collect_from' => 'on_pickup',
            'description' => $request->remark,
            'delivery_man_id' => (int) $request->delivery_man_id,
            'status' => 'courier_assigned',
            'country_id' => $client->country_id,
            'city_id' => $client->city_id,
            'date' => $pickupDatetime,
            'pickup_datetime' => $pickupDatetime,
            'delivery_datetime' => $deliveryDatetime,
            'total_amount' => 0,
            'fixed_charges' => 0,
            'weight_charge' => 0,
            'distance_charge' => 0,
            'vehicle_charge' => 0,
            'currency' => appSettingcurrency()->currency ?? '$',
            'milisecond' => strtoupper(appSettingcurrency('prefix')) . round(microtime(true) * 1000),
            'assign_datetime' => now(),
        ];

        $result = Order::create($data);

        // Seed blank parcel slots (Order Count) for User App / rider pickup.
        app(\App\Services\TextOrderDispatchService::class)->seedBlankParcels($result->fresh());

        try {
            app(DispatchOrderAuditService::class)->logOrderCreated($result->fresh());
        } catch (\Throwable $e) {
            \Log::warning('dispatch audit failed after admin create', [
                'order_id' => $result->id,
                'error' => $e->getMessage(),
            ]);
        }

        saveOrderHistory([
            'history_type' => 'courier_assigned',
            'order_id' => $result->id,
            'order' => $result->fresh(),
        ]);

        $rider = User::find((int) $request->delivery_man_id);
        if ($rider) {
            try {
                app(DispatchOrderAuditService::class)->logPickupRiderAssigned($result->fresh(), $rider);
            } catch (\Throwable $e) {
                \Log::warning('dispatch audit failed after admin pickup rider assign', [
                    'order_id' => $result->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        app(DispatchOrderWorkflowService::class)->syncOrderWorkflow($result->fresh());

        $message = __('message.save_form', ['form' => __('message.order')]);

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'status' => true,
                'message' => $message,
                'order_id' => $result->id,
            ]);
        }

        return redirect()->route('order.create')->withSuccess($message);
    }

    public function dispatchItems(DispatchOrderItemDataTable $dataTable, $id)
    {
        if (!auth()->user()->can('order-list')) {
            $message = __('message.demo_permission_denied');
            return redirect()->back()->withErrors($message);
        }

        $order = Order::findOrFail($id);

        $hasCollectedItems = \App\Models\DispatchOrderItem::query()
            ->where('order_id', $order->id)
            ->where('status', 'collected')
            ->exists();

        // Never re-sync when parcels already exist — that can soft-delete rider extras.
        if (! $hasCollectedItems) {
            if ((int) $order->is_photo_order === 1) {
                app(PhotoOrderDispatchService::class)->sync($order);
                $order->refresh();
            }

            if ((int) ($order->is_text_order ?? 0) === 1) {
                $textService = app(TextOrderDispatchService::class);
                if (! $textService->hasAdvancedParcelItems($order)) {
                    $textService->ensureTextOrderItem($order);
                    $order->refresh();
                }
            } elseif ((int) ($order->is_shop_order ?? 0) === 1
                || (int) ($order->is_gate_order ?? 0) === 1) {
                $textService = app(TextOrderDispatchService::class);
                if (!$textService->hasAdvancedParcelItems($order)) {
                    if ($textService->isMultiRecipientOrder($order)) {
                        $textService->syncMultiRecipientItems($order);
                    } else {
                        $textService->sync($order);
                    }
                    $order->refresh();
                }
            }
        } else {
            // Existing blank Gate/Shop/Text parcels: copy User App customer fields into empty slots.
            if ((int) ($order->is_text_order ?? 0) === 1
                || (int) ($order->is_shop_order ?? 0) === 1
                || (int) ($order->is_gate_order ?? 0) === 1) {
                app(TextOrderDispatchService::class)->backfillCustomerFieldsIfEmpty($order->fresh());
            }

            if ((int) $order->is_photo_order === 1) {
                // Photo orders may still need client-photo sync, but never drop rider extras
                // or overwrite rider payment once pickup has started.
                if (empty($order->delivery_man_id)
                    && ! in_array($order->status, [
                        'courier_assigned',
                        'courier_arrived',
                        'courier_picked_up',
                        'courier_departed',
                        'completed',
                        'active',
                    ], true)) {
                    app(PhotoOrderDispatchService::class)->sync($order);
                    $order->refresh();
                }
            }
        }

        $pageTitle = __('message.order_detail_list');
        $assets = ['datatable'];

        return $dataTable->with('order_id', $id)->render('order.dispatch-items-list', compact('pageTitle', 'assets', 'order'));
    }

    public function dispatchToAssign()
    {
        if (!auth()->user()->can('order-list')) {
            $message = __('message.demo_permission_denied');
            return redirect()->back()->withErrors($message);
        }

        $workflow = app(DispatchOrderWorkflowService::class);
        $workflow->healPickupErrorChoicesToCancelled();

        $items = DispatchOrderItem::query()
            ->whereIn('status', ['collected', 'assigned', 'courier_assigned', 'courier_departed', 'pending', 'completed'])
            ->with([
                'order.client',
                'order.delivery_man',
                'order.orderHistoryasc',
                'order.dispatchItems',
                'fromBranch',
                'toBranch',
                'deliveryMan',
                'pendingPhotoMedia',
                'deliveredPhotoMedia',
            ])
            ->orderByDesc('received_date')
            ->orderByDesc('id')
            ->get();

        $pageTitle = __('message.follow_up');
        $assets = [];

        return view('order.dispatch-to-assign', compact('pageTitle', 'assets', 'items'));
    }

    public function dispatchAssignPickupRider(Request $request, $id)
    {
        if (!auth()->user()->can('order-edit')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        $request->validate([
            'delivery_man_id' => 'required|integer|exists:users,id',
        ]);

        $order = Order::findOrFail($id);
        $riderId = (int) $request->delivery_man_id;

        $rider = User::where('id', $riderId)
            ->where('user_type', 'delivery_man')
            ->where('status', 1)
            ->first();

        if (!$rider) {
            return response()->json(['message' => __('message.not_found_entry', ['name' => __('message.delivery_man')])], 422);
        }

        if (! $rider->isRiderWorkOn()) {
            return response()->json(['message' => __('message.rider_work_off_assign_blocked')], 422);
        }

        $pickupService = app(\App\Services\PickupParcelDispatchService::class);

        if (($order->status ?? '') === 'pickup_error'
            && ! in_array($order->pickup_error_choice, ['express', 'next_day'], true)) {
            return response()->json([
                'message' => __('message.pickup_error_rider_assign_requires_choice'),
            ], 422);
        }

        $workflow = app(DispatchOrderWorkflowService::class);
        if ($workflow->isPrePickUpOrder($order)) {
            return response()->json([
                'message' => __('message.pre_pickup_rider_assign_blocked'),
            ], 422);
        }

        try {
            $order = $pickupService->assignPickupRider($order, $riderId);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'status' => true,
            'message' => __('message.pickup_rider_assigned_success', ['rider' => $rider->name]),
            'order_id' => $order->id,
            'delivery_man_id' => $riderId,
            'rider_name' => $rider->name,
        ]);
    }

    public function dispatchRestorePickupCancelled($id)
    {
        if (! auth()->user()->can('order-edit')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        $order = Order::find($id);
        if (! $order) {
            return response()->json(['message' => __('message.not_found_entry', ['name' => __('message.order')])], 404);
        }

        try {
            $order = app(DispatchOrderWorkflowService::class)->restorePickupCancelledOrder($order);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        try {
            app(DispatchOrderAuditService::class)->logRestored($order, auth()->user());
        } catch (\Throwable $e) {
            \Log::warning('dispatch audit failed after admin pickup cancelled restore', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        $destination = isSameDayOrderCutoffPassed()
            ? __('message.pre_order_list')
            : __('message.list_form_title', ['form' => __('message.order')]);

        return response()->json([
            'status' => true,
            'message' => __('message.pickup_cancelled_restored_to', ['list' => $destination]),
            'order_id' => $order->id,
            'pickup_datetime' => $order->pickup_datetime,
        ]);
    }

    public function dispatchMovePrePickupToOrderList($id)
    {
        if (! auth()->user()->can('order-edit')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        $order = Order::find($id);
        if (! $order) {
            return response()->json(['message' => __('message.not_found_entry', ['name' => __('message.order')])], 404);
        }

        try {
            $order = app(DispatchOrderWorkflowService::class)->movePrePickUpToOrderList($order);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        try {
            app(DispatchOrderAuditService::class)->logPrePickupMoved($order);
        } catch (\Throwable $e) {
            \Log::warning('dispatch audit failed after pre-pickup move', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => __('message.pre_pickup_moved_to_order_list'),
            'order_id' => $order->id,
            'pickup_datetime' => $order->pickup_datetime,
            'redirect' => route('order.index', ['orders_type' => 'list']),
        ]);
    }

    public function dispatchMoveToAssign100(Request $request)
    {
        if (!auth()->user()->can('order-edit')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        $request->validate([
            'item_ids' => 'required|array|min:1|max:100',
            'item_ids.*' => 'integer|exists:dispatch_order_items,id',
        ]);

        $items = DispatchOrderItem::query()
            ->whereIn('id', $request->item_ids)
            ->where('status', 'collected')
            ->get(['id', 'order_id']);

        if ($items->isEmpty()) {
            return response()->json(['message' => __('message.no_record_found')], 422);
        }

        $updated = DispatchOrderItem::query()
            ->whereIn('id', $items->pluck('id'))
            ->update([
                'status' => 'assigned',
                'assigned_at' => now(),
                'received_date' => Carbon::now('Asia/Yangon')->toDateString(),
            ]);

        if ($updated === 0) {
            return response()->json(['message' => __('message.no_record_found')], 422);
        }

        $audit = app(DispatchOrderAuditService::class);
        foreach ($items->groupBy('order_id') as $orderId => $orderItems) {
            $order = Order::find($orderId);
            if ($order) {
                try {
                    $audit->logMovedToAssign100($order, $orderItems->count());
                } catch (\Throwable $e) {
                    \Log::warning('dispatch audit failed after move to assign 100', [
                        'order_id' => $orderId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return response()->json([
            'message' => __('message.move_to_assign_100_success', ['count' => $updated]),
            'redirect' => route('order.dispatch.assign-100'),
        ]);
    }

    public function dispatchAssign100()
    {
        if (!auth()->user()->can('order-list')) {
            $message = __('message.demo_permission_denied');
            return redirect()->back()->withErrors($message);
        }

        app(DispatchOrderWorkflowService::class)->reclaimPrematureAssign100Items();
        app(DispatchOrderWorkflowService::class)->refreshAssign100PoolReceivedDates();

        $items = DispatchOrderItem::query()
            ->where('status', 'assigned')
            ->with(['order.client', 'order.delivery_man', 'fromBranch', 'toBranch', 'deliveryMan'])
            ->orderByDesc('assigned_at')
            ->orderByDesc('id')
            ->get();

        $pageTitle = __('message.assign_100');
        $assets = [];

        return view('order.dispatch-assign-100', compact('pageTitle', 'assets', 'items'));
    }

    public function dispatchAssignRider(Request $request)
    {
        if (!auth()->user()->can('order-edit')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        $request->validate([
            'item_ids' => 'required|array|min:1|max:100',
            'item_ids.*' => 'integer|exists:dispatch_order_items,id',
            'delivery_man_id' => 'required|integer|exists:users,id',
        ]);

        $rider = User::where('id', $request->delivery_man_id)
            ->where('user_type', 'delivery_man')
            ->where('status', 1)
            ->whereNotNull('email_verified_at')
            ->whereNotNull('otp_verify_at')
            ->whereNotNull('document_verified_at')
            ->first();

        if (!$rider) {
            return response()->json(['message' => __('message.not_found_entry', ['name' => __('message.delivery_man')])], 422);
        }

        if (! $rider->isRiderWorkOn()) {
            return response()->json(['message' => __('message.rider_work_off_assign_blocked')], 422);
        }

        $updated = DispatchOrderItem::query()
            ->whereIn('id', $request->item_ids)
            ->where('status', 'assigned')
            ->update([
                'status' => 'courier_assigned',
                'delivery_man_id' => $rider->id,
                'assigned_at' => now(),
                'received_date' => Carbon::now('Asia/Yangon')->toDateString(),
            ]);

        if ($updated === 0) {
            return response()->json(['message' => __('message.no_record_found')], 422);
        }

        $items = DispatchOrderItem::query()
            ->whereIn('id', $request->item_ids)
            ->where('delivery_man_id', $rider->id)
            ->get();

        $orderIds = $items->pluck('order_id')->unique();
        $audit = app(DispatchOrderAuditService::class);
        $push = app(\App\Services\AppPushService::class);
        foreach ($orderIds as $orderId) {
            $order = Order::find($orderId);
            if ($order) {
                $count = $items->where('order_id', $orderId)->count();
                $audit->logDeliveryRiderAssigned($order, $rider, $count);
            }
        }

        foreach ($items as $item) {
            try {
                $push->notifyRiderDeliveryAssigned($rider, $item);
            } catch (\Throwable $e) {
                \Log::warning('push failed after admin delivery assign', [
                    'item_id' => $item->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'message' => __('message.dispatch_delivery_man_assigned_success', [
                'count' => $updated,
                'rider' => $rider->name,
            ]),
            'redirect' => route('order.dispatch.assign-100'),
        ]);
    }

    /**
     * Items assigned to a delivery rider from Assign 100 (status = courier_assigned).
     */
    public function dispatchAssignedItems()
    {
        if (! auth()->user()->can('order-list')) {
            $message = __('message.demo_permission_denied');

            return redirect()->back()->withErrors($message);
        }

        $items = DispatchOrderItem::query()
            ->whereHas('messages')
            ->with(['order.client.media', 'order.delivery_man', 'fromBranch', 'toBranch', 'deliveryMan'])
            ->withCount([
                'messages as unreplied_count' => function ($q) {
                    $q->where('sender_type', 'client')->whereNull('read_at');
                },
            ])
            ->withMax('messages', 'created_at')
            ->orderByDesc('id')
            ->get();

        $lastMessageIds = \App\Models\DispatchItemMessage::query()
            ->selectRaw('MAX(id) as id')
            ->whereIn('dispatch_order_item_id', $items->pluck('id')->all())
            ->groupBy('dispatch_order_item_id')
            ->pluck('id');

        $lastMessages = \App\Models\DispatchItemMessage::query()
            ->with(['media', 'sender:id,name'])
            ->whereIn('id', $lastMessageIds)
            ->get()
            ->keyBy('dispatch_order_item_id');

        foreach ($items as $item) {
            $last = $lastMessages->get($item->id);
            $lastText = trim((string) ($last?->message ?? ''));
            if ($lastText === '' && $last && getMediaFileExit($last, 'chat_image')) {
                $lastText = '[Image]';
            }
            $item->last_chat_message = $lastText !== '' ? $lastText : null;
            $item->last_chat_at = $last?->created_at;
            $item->last_chat_sender_type = $last?->sender_type;
            if (($last?->sender_type ?? '') === 'client') {
                $osName = resolveDispatchOsName($item->order);
                $item->last_chat_sender_label = ($osName !== '' && $osName !== '-') ? $osName : 'Os';
            } else {
                $adminName = trim((string) optional($last?->sender)->name);
                $item->last_chat_sender_label = $adminName !== '' ? $adminName : 'Admin';
            }
            $item->needs_reply = ($item->last_chat_sender_type === 'client');
        }

        // Unanswered (last msg from OS) stay on top; after admin replies, sink to bottom.
        $items = $items
            ->sort(function ($a, $b) {
                $aNeed = ! empty($a->needs_reply) ? 1 : 0;
                $bNeed = ! empty($b->needs_reply) ? 1 : 0;
                if ($aNeed !== $bNeed) {
                    return $bNeed <=> $aNeed;
                }

                $aTime = optional($a->last_chat_at)->timestamp ?? 0;
                $bTime = optional($b->last_chat_at)->timestamp ?? 0;

                // Within the same bucket: older first so the most recently updated sinks last.
                return $aTime <=> $bTime;
            })
            ->values();

        $pageTitle = __('message.assigned_item_list');
        $assets = [];
        $tabCounts = [
            'unread' => $items->filter(fn ($i) => (int) ($i->unreplied_count ?? 0) > 0)->count(),
            'unanswered' => $items->filter(fn ($i) => ! empty($i->needs_reply))->count(),
            'answered' => $items->filter(fn ($i) => empty($i->needs_reply))->count(),
        ];

        return view('order.dispatch-assigned-items', compact('pageTitle', 'assets', 'items', 'tabCounts'));
    }

    /**
     * Admin Rider List — per-rider Assigned / On Way / Delivered / Pending item counts.
     */
    /**
     * Admin Rider List — status counts per rider for a day (default: Yangon today).
     * From–To can widen the window; empty request dates fall back to today.
     */
    public function dispatchRiderList(Request $request)
    {
        if (! auth()->user()->can('order-list')) {
            $message = __('message.demo_permission_denied');

            return redirect()->back()->withErrors($message);
        }

        $statuses = ['courier_assigned', 'courier_departed', 'pending', 'completed'];
        $yangonToday = now('Asia/Yangon')->format('d-m-Y');
        $fromDateRaw = trim((string) $request->get('from_date', $yangonToday));
        $toDateRaw = trim((string) $request->get('to_date', $fromDateRaw !== '' ? $fromDateRaw : $yangonToday));
        if ($fromDateRaw === '') {
            $fromDateRaw = $yangonToday;
        }
        if ($toDateRaw === '') {
            $toDateRaw = $fromDateRaw;
        }
        $riderFilter = trim((string) $request->get('rider_id', 'all'));
        if ($riderFilter === '') {
            $riderFilter = 'all';
        }

        $fromDay = $this->parseDispatchDateInput($fromDateRaw)->toDateString();
        $toDay = $this->parseDispatchDateInput($toDateRaw)->toDateString();
        if ($toDay < $fromDay) {
            $toDay = $fromDay;
            $toDateRaw = $fromDateRaw;
        }
        $hasDateFilter = true;

        $countQuery = DispatchOrderItem::query()
            ->selectRaw("
                delivery_man_id,
                SUM(CASE WHEN status = 'courier_assigned' THEN 1 ELSE 0 END) as courier_assigned,
                SUM(CASE WHEN status = 'courier_departed' THEN 1 ELSE 0 END) as courier_departed,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'completed' AND admin_completed_at IS NULL THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN status = 'completed' AND admin_completed_at IS NOT NULL AND admin_finished_at IS NULL THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'completed' AND admin_finished_at IS NOT NULL THEN 1 ELSE 0 END) as finished,
                COUNT(*) as total
            ")
            ->whereNotNull('delivery_man_id')
            ->whereIn('status', $statuses)
            ->where(function ($dateQuery) use ($fromDay, $toDay) {
                $this->applyRiderListDateFilter($dateQuery, $fromDay, $toDay);
            });

        $countRows = $countQuery->groupBy('delivery_man_id')->get();

        $countsByRider = [];
        foreach ($countRows as $row) {
            $riderId = (int) $row->delivery_man_id;
            $countsByRider[$riderId] = [
                'courier_assigned' => (int) $row->courier_assigned,
                'courier_departed' => (int) $row->courier_departed,
                'pending' => (int) $row->pending,
                'delivered' => (int) $row->delivered,
                'completed' => (int) $row->completed,
                'finished' => (int) $row->finished,
                'total' => (int) $row->total,
            ];
        }

        $riderQuery = User::query()
            ->where('user_type', 'delivery_man')
            ->where('status', 1)
            ->whereIn('id', array_keys($countsByRider) ?: [0])
            ->withAvg('rating as average_rating', 'rating')
            ->withCount('rating as ratings_count')
            ->orderBy('name');

        if ($riderFilter !== 'all' && is_numeric($riderFilter)) {
            $riderQuery->where('id', (int) $riderFilter);
        }

        $riders = $riderQuery->get()->map(function (User $rider) use ($countsByRider) {
            $counts = $countsByRider[$rider->id] ?? [
                'courier_assigned' => 0,
                'courier_departed' => 0,
                'pending' => 0,
                'delivered' => 0,
                'completed' => 0,
                'finished' => 0,
                'total' => 0,
            ];

            $avg = round((float) ($rider->average_rating ?? 0), 2);
            $ratingCount = (int) ($rider->ratings_count ?? 0);

            return (object) [
                'id' => $rider->id,
                'name' => $rider->name,
                'phone' => $rider->riderAssignedPhone() ?: '-',
                'counts' => $counts,
                'average_rating' => $avg,
                'ratings_count' => $ratingCount,
            ];
        })->sortBy('name')->values();

        $riderOptions = User::query()
            ->where('user_type', 'delivery_man')
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        $pageTitle = __('message.rider_list');
        $assets = [];
        $filterFromDate = $fromDateRaw;
        $filterToDate = $toDateRaw;
        $riderOfMonthUrl = route('deliveryman.rider-of-month');

        return view('order.dispatch-rider-list', compact(
            'pageTitle',
            'assets',
            'riders',
            'riderOptions',
            'filterFromDate',
            'filterToDate',
            'riderFilter',
            'fromDay',
            'toDay',
            'hasDateFilter',
            'riderOfMonthUrl'
        ));
    }

    /**
     * Admin Rider List → Item Details for one rider + status.
     */
    public function dispatchRiderItems(Request $request, $riderId)
    {
        if (! auth()->user()->can('order-list')) {
            $message = __('message.demo_permission_denied');

            return redirect()->back()->withErrors($message);
        }

        $statusMap = [
            'courier_assigned' => __('message.follow_up_status_assigned'),
            'courier_departed' => __('message.follow_up_status_on_way'),
            'pending' => __('message.follow_up_status_pending'),
            'delivered' => __('message.follow_up_status_delivered'),
            'completed' => __('message.follow_up_status_completed'),
            'finished' => __('message.follow_up_status_finished'),
        ];

        $status = trim((string) $request->get('status', 'courier_assigned'));
        if (! array_key_exists($status, $statusMap)) {
            return redirect()
                ->route('order.dispatch.rider-list')
                ->withErrors(__('message.delivery_item_status_not_allowed'));
        }

        // Delivered / Completed / Finished all use item status "completed" with different admin flags.
        $queryStatus = in_array($status, ['delivered', 'completed', 'finished'], true)
            ? 'completed'
            : $status;

        $rider = User::query()
            ->where('id', $riderId)
            ->where('user_type', 'delivery_man')
            ->first();

        if (! $rider) {
            return redirect()
                ->route('order.dispatch.rider-list')
                ->withErrors(__('message.not_found_entry', ['name' => __('message.delivery_man')]));
        }

        $yangonToday = now('Asia/Yangon')->format('d-m-Y');
        $fromDateRaw = trim((string) $request->get('from_date', $yangonToday));
        $toDateRaw = trim((string) $request->get('to_date', $fromDateRaw !== '' ? $fromDateRaw : $yangonToday));
        if ($fromDateRaw === '') {
            $fromDateRaw = $yangonToday;
        }
        if ($toDateRaw === '') {
            $toDateRaw = $fromDateRaw;
        }
        $fromDay = $this->parseDispatchDateInput($fromDateRaw)->toDateString();
        $toDay = $this->parseDispatchDateInput($toDateRaw)->toDateString();
        if ($toDay < $fromDay) {
            $toDay = $fromDay;
            $toDateRaw = $fromDateRaw;
        }
        $hasDateFilter = true;

        $search = trim((string) $request->get('search', ''));

        $itemsQuery = DispatchOrderItem::query()
            ->where('delivery_man_id', $rider->id)
            ->where('status', $queryStatus)
            ->with(['order.client', 'fromBranch', 'toBranch', 'deliveryMan', 'photoMedia', 'pendingPhotoMedia', 'deliveredPhotoMedia'])
            ->orderByDesc('id')
            ->where(function ($dateQuery) use ($fromDay, $toDay) {
                $this->applyRiderListDateFilter($dateQuery, $fromDay, $toDay);
            });

        if ($status === 'delivered') {
            $itemsQuery->whereNull('admin_completed_at');
        } elseif ($status === 'completed') {
            $itemsQuery->whereNotNull('admin_completed_at')->whereNull('admin_finished_at');
        } elseif ($status === 'finished') {
            $itemsQuery->whereNotNull('admin_finished_at');
        }

        if ($search !== '') {
            $itemsQuery->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('customer_address', 'like', "%{$search}%")
                    ->orWhere('township', 'like', "%{$search}%")
                    ->orWhere('remark', 'like', "%{$search}%");
            });
        }

        $items = $itemsQuery->get();

        $pageTitle = __('message.item_details');
        $assets = [];
        $isDelivered = $status === 'delivered';
        $isCompleted = $status === 'completed';
        $canBulkUpdate = in_array($status, ['courier_assigned', 'courier_departed', 'pending', 'delivered', 'completed'], true);
        $bulkActions = match ($status) {
            'courier_assigned' => [
                'courier_departed' => __('message.follow_up_status_on_way'),
            ],
            'courier_departed' => [
                'pending' => __('message.follow_up_status_pending'),
                'completed' => __('message.follow_up_status_delivered'),
            ],
            'pending' => [
                'completed' => __('message.follow_up_status_delivered'),
            ],
            'delivered' => [
                'admin_completed' => __('message.follow_up_status_completed'),
            ],
            'completed' => [
                'admin_finished' => __('message.follow_up_status_finished'),
            ],
            default => [],
        };
        $statusLabel = $statusMap[$status];
        $filterFromDate = $fromDateRaw;
        $filterToDate = $toDateRaw;

        return view('order.dispatch-rider-items', compact(
            'pageTitle',
            'assets',
            'rider',
            'items',
            'status',
            'statusLabel',
            'statusMap',
            'isDelivered',
            'isCompleted',
            'canBulkUpdate',
            'bulkActions',
            'filterFromDate',
            'filterToDate',
            'fromDay',
            'toDay',
            'search'
        ));
    }

    /**
     * Admin Rider List — bulk update selected item statuses.
     * Assigned → On Way; On Way → Pending/Delivered; Pending → Delivered;
     * Delivered → Completed (admin_completed_at).
     */
    public function dispatchRiderItemsBulkUpdate(Request $request, $riderId)
    {
        if (! auth()->user()->can('order-edit')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        $toStatus = trim((string) $request->input('to_status', ''));
        $rules = [
            'item_ids' => 'required|array|min:1',
            'item_ids.*' => 'integer',
            'to_status' => 'required|string|in:courier_departed,pending,completed,admin_completed,admin_finished',
            'remark' => 'nullable|string|max:1000',
            'pending_photo' => 'nullable|image|max:10240',
            'delivered_photo' => 'nullable|image|max:10240',
            'delivered_type' => 'nullable|string|in:gate,other',
            'gate_amount' => 'nullable|numeric|min:0',
        ];
        if ($toStatus === 'pending') {
            $rules['remark'] = 'required|string|max:1000';
            $rules['pending_photo'] = 'required|image|max:10240';
        }
        if ($toStatus === 'completed') {
            $rules['delivered_type'] = 'required|string|in:gate,other';
            $rules['delivered_photo'] = 'required|image|max:10240';
            if ((string) $request->input('delivered_type') === 'gate') {
                $rules['gate_amount'] = 'required|numeric|min:0';
            }
        }
        $data = $request->validate($rules);

        $rider = User::query()
            ->where('id', $riderId)
            ->where('user_type', 'delivery_man')
            ->first();

        if (! $rider) {
            return response()->json([
                'message' => __('message.not_found_entry', ['name' => __('message.delivery_man')]),
            ], 404);
        }

        $ids = array_values(array_unique(array_map('intval', $data['item_ids'])));
        $remark = isset($data['remark']) ? trim((string) $data['remark']) : null;
        $workflow = app(DispatchOrderWorkflowService::class);
        $audit = app(DispatchOrderAuditService::class);
        $admin = auth()->user();
        $updated = 0;

        if ($toStatus === 'admin_finished') {
            $updated = DispatchOrderItem::query()
                ->where('delivery_man_id', $rider->id)
                ->where('status', 'completed')
                ->whereNotNull('admin_completed_at')
                ->whereNull('admin_finished_at')
                ->whereIn('id', $ids)
                ->update([
                    'admin_finished_at' => now(),
                    'admin_updated_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($updated <= 0) {
                return response()->json([
                    'message' => __('message.rider_items_mark_finished_none'),
                ], 422);
            }

            return response()->json([
                'message' => __('message.rider_items_marked_finished', ['count' => $updated]),
                'updated' => $updated,
            ]);
        }

        if ($toStatus === 'admin_completed') {
            $updated = DispatchOrderItem::query()
                ->where('delivery_man_id', $rider->id)
                ->where('status', 'completed')
                ->whereNull('admin_completed_at')
                ->whereIn('id', $ids)
                ->update([
                    'admin_completed_at' => now(),
                    'admin_updated_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($updated <= 0) {
                return response()->json([
                    'message' => __('message.rider_items_mark_completed_none'),
                ], 422);
            }

            return response()->json([
                'message' => __('message.rider_items_marked_completed', ['count' => $updated]),
                'updated' => $updated,
            ]);
        }

        $items = DispatchOrderItem::query()
            ->where('delivery_man_id', $rider->id)
            ->whereIn('id', $ids)
            ->with(['order', 'pendingPhotoMedia'])
            ->get();

        if ($items->isEmpty()) {
            return response()->json([
                'message' => __('message.not_found_entry', ['name' => __('message.item_name')]),
            ], 404);
        }

        $pendingPhotoId = 0;
        if ($toStatus === 'pending') {
            $pendingPhotoId = $this->storeAdminPendingRemarkPhoto(
                $items->first(),
                $request->file('pending_photo')
            );
            if ($pendingPhotoId <= 0) {
                return response()->json(['message' => 'Unable to save pending photo.'], 422);
            }
        }

        $deliveredPhotoId = 0;
        $deliveredType = null;
        $gateAmount = null;
        if ($toStatus === 'completed') {
            $deliveredType = (string) $data['delivered_type'];
            $deliveredPhotoId = $this->storeAdminDeliveredProofPhoto(
                $items->first(),
                $request->file('delivered_photo')
            );
            if ($deliveredPhotoId <= 0) {
                return response()->json(['message' => __('message.delivered_photo_required')], 422);
            }
            if ($deliveredType === 'gate') {
                $gateAmount = round((float) ($data['gate_amount'] ?? 0), 2);
            }
        }

        foreach ($items as $item) {
            try {
                $workflow->assertDeliveryStatusTransition($item, $toStatus, $remark);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            $fromStatus = (string) $item->status;
            $fill = [
                'status' => $toStatus,
                'admin_updated_at' => now(),
            ];
            if ($toStatus === 'pending') {
                $fill['remark'] = $remark;
                $fill['pending_photo_id'] = $pendingPhotoId;
            }
            if ($toStatus === 'completed') {
                $fill['delivery_locked'] = true;
                $fill['delivered_type'] = $deliveredType;
                $fill['delivered_photo_id'] = $deliveredPhotoId;
                $fill['rider_remit_at'] = null;
                $fill['delivered_at'] = now();
                $fill['rider_remit_date'] = resolveRiderRemitDate((int) ($item->delivery_man_id ?? 0));
                if ($deliveredType === 'gate' && $gateAmount !== null) {
                    $fill['gate_amount'] = $gateAmount;
                }
            }

            $item->forceFill($fill)->save();
            $updated++;

            $order = $item->order;
            if ($order) {
                try {
                    $audit->logDeliveryItemStatus(
                        $order,
                        $item->fresh(),
                        $fromStatus,
                        $toStatus,
                        $admin,
                        $remark
                    );
                } catch (\Throwable $e) {
                    \Log::warning('dispatch audit failed after admin bulk status', [
                        'order_id' => $order->id,
                        'item_id' => $item->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                if (in_array($toStatus, ['courier_departed', 'completed'], true)) {
                    try {
                        saveOrderHistory([
                            'history_type' => $toStatus,
                            'order_id' => $order->id,
                            'order' => $order,
                            'skip_push' => $toStatus === 'completed',
                        ]);
                    } catch (\Throwable $e) {
                        // ignore
                    }
                }

                try {
                    $push = app(\App\Services\AppPushService::class);
                    if ($toStatus === 'pending') {
                        $push->notifyClientItemPending($item->fresh());
                    } elseif ($toStatus === 'completed') {
                        $push->notifyClientItemDelivered($item->fresh());
                    }
                } catch (\Throwable $e) {
                    \Log::warning('push failed after admin bulk status', [
                        'order_id' => $order->id,
                        'item_id' => $item->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return response()->json([
            'message' => __('message.rider_items_status_updated', ['count' => $updated]),
            'updated' => $updated,
        ]);
    }

    /**
     * @deprecated Use dispatchRiderItemsBulkUpdate with to_status=admin_completed.
     */
    public function dispatchRiderItemsMarkCompleted(Request $request, $riderId)
    {
        $request->merge(['to_status' => 'admin_completed']);

        return $this->dispatchRiderItemsBulkUpdate($request, $riderId);
    }

    protected function storeAdminPendingRemarkPhoto(DispatchOrderItem $item, $file): int
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

    protected function storeAdminDeliveredProofPhoto(DispatchOrderItem $item, $file): int
    {
        return storeDispatchItemProofPhoto($item, $file, 'delivered_proof');
    }

    /**
     * Admin OS List — day-by-day parcel counts + amounts per Online Shop.
     */
    public function dispatchOsList(Request $request)
    {
        if (! auth()->user()->can('order-list')) {
            $message = __('message.demo_permission_denied');

            return redirect()->back()->withErrors($message);
        }

        $yangonToday = Carbon::now('Asia/Yangon')->format('d-m-Y');
        $fromDateRaw = trim((string) $request->get('from_date', $yangonToday));
        $toDateRaw = trim((string) $request->get('to_date', $fromDateRaw));
        $fromDay = $this->parseDispatchDateInput($fromDateRaw)->toDateString();
        $toDay = $this->parseDispatchDateInput($toDateRaw)->toDateString();
        if ($toDay < $fromDay) {
            $toDay = $fromDay;
            $toDateRaw = $fromDateRaw;
        }

        $osFilter = trim((string) $request->get('os_id', 'all'));
        $settlementService = app(OsSettlementService::class);

        $unfinishedItems = $settlementService
            ->completedItemsQuery(null, $fromDay, $toDay)
            ->get()
            ->filter(function ($item) use ($osFilter) {
                $osId = (int) ($item->order?->client_id ?? 0);
                if ($osFilter === '' || $osFilter === 'all') {
                    return true;
                }
                if ($osFilter === '0' || strtolower($osFilter) === 'none') {
                    return $osId === 0;
                }
                if (is_numeric($osFilter)) {
                    return $osId === (int) $osFilter;
                }

                return true;
            });

        // Only unfinished OS remain on ငွေရှင်းတမ်း; Finished rows move to Daily Check / Money Transfer.
        $grouped = $unfinishedItems->groupBy(static fn ($item) => (int) ($item->order?->client_id ?? 0));
        $osIds = $grouped->keys()
            ->map(static fn ($id) => (int) $id)
            ->unique()
            ->values();

        $clientIds = $osIds->filter(static fn ($id) => (int) $id > 0)->values()->all();

        $clients = User::query()
            ->whereIn('id', $clientIds ?: [0])
            ->with('city')
            ->get()
            ->keyBy('id');

        $drafts = OsSettlementDraft::query()
            ->where('from_date', $fromDay)
            ->where('to_date', $toDay)
            ->get()
            ->keyBy('os_user_id');

        $buildOsRow = function (int $osId, $itemGroup) use ($clients, $settlementService, $drafts) {
            if ($itemGroup->isEmpty()) {
                return null;
            }

            $client = $osId > 0 ? ($clients->get($osId) ?? null) : null;
            $name = $osId > 0
                ? (trim((string) ($client?->name ?? '')) !== '' ? trim((string) $client->name) : ('#'.$osId))
                : __('message.no_os');
            $cityName = trim((string) ($client?->city?->name ?? ''));
            if ($cityName !== '') {
                $name .= ' ('.$cityName.')';
            }

            $amount = (float) $itemGroup->sum(static fn ($item) => $item->displayOsToPay());
            $draft = $drafts->get($osId);
            $kpaySlipUrl = $draft && $draft->kpay_slip_path
                ? Storage::disk('public')->url($draft->kpay_slip_path)
                : null;
            $kpayName = $client ? $settlementService->kpayNameFromUser($client) : '';
            $kpayNo = $client ? $settlementService->kpayNoFromUser($client) : '';

            return (object) [
                'id' => $osId,
                'name' => $name,
                'phone' => $client?->contact_number ?? '-',
                'amount' => $amount,
                'kpay_name' => $kpayName,
                'kpay_no' => $kpayNo,
                'kpay_slip_url' => $kpaySlipUrl,
                'has_kpay_slip' => ! empty($kpaySlipUrl),
                'item_count' => $itemGroup->count(),
                'is_finished' => false,
                'batch_id' => null,
            ];
        };

        // Split by item sign (not net OS total): negative → pay, positive → receive.
        // Same OS can appear in both tabs when it has both outgoing and incoming items.
        $payToOsRows = $osIds->map(function ($osId) use ($grouped, $buildOsRow) {
            $osId = (int) $osId;
            $payItems = $grouped->get($osId, collect())
                ->filter(static fn ($item) => (float) $item->displayOsToPay() < 0)
                ->values();

            return $buildOsRow($osId, $payItems);
        })->filter()->sortBy(static fn ($row) => mb_strtolower($row->name), SORT_NATURAL)->values();

        $receiveFromOsRows = $osIds->map(function ($osId) use ($grouped, $buildOsRow) {
            $osId = (int) $osId;
            $receiveItems = $grouped->get($osId, collect())
                ->filter(static fn ($item) => (float) $item->displayOsToPay() > 0)
                ->values();

            return $buildOsRow($osId, $receiveItems);
        })->filter()->sortBy(static fn ($row) => mb_strtolower($row->name), SORT_NATURAL)->values();

        $rows = $payToOsRows->concat($receiveFromOsRows)->values();

        $osOptions = User::query()
            ->where('user_type', 'client')
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        $pageTitle = __('message.os_list');
        $assets = [];
        $filterFromDate = $fromDateRaw;
        $filterToDate = $toDateRaw;
        $slipCompany = $this->dispatchOsSlipCompany();

        return view('order.dispatch-os-list', compact(
            'pageTitle',
            'assets',
            'rows',
            'payToOsRows',
            'receiveFromOsRows',
            'osOptions',
            'filterFromDate',
            'filterToDate',
            'fromDay',
            'toDay',
            'osFilter',
            'slipCompany'
        ));
    }

    public function dispatchOsSettlementSlipPreview(Request $request, $osId)
    {
        if (! auth()->user()->can('order-list')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        if ((int) $osId <= 0) {
            return response()->json(['message' => __('message.something_went_wrong')], 422);
        }

        $yangonToday = Carbon::now('Asia/Yangon')->format('d-m-Y');
        $fromDateRaw = trim((string) $request->get('from_date', $yangonToday));
        $toDateRaw = trim((string) $request->get('to_date', $fromDateRaw));
        $fromDay = $this->parseDispatchDateInput($fromDateRaw)->toDateString();
        $toDay = $this->parseDispatchDateInput($toDateRaw)->toDateString();
        $osId = (int) $osId;

        $settlementService = app(OsSettlementService::class);
        $settlementSide = $request->get('settlement_side');
        $items = $settlementService->filterItemsBySettlementSide(
            $settlementService->completedItemsQuery($osId, $fromDay, $toDay)->get(),
            in_array($settlementSide, ['pay', 'receive'], true) ? $settlementSide : null
        );

        $batch = null;
        if ($items->isEmpty()) {
            $batch = OsSettlementBatch::query()
                ->where('os_user_id', $osId)
                ->where('from_date', $fromDay)
                ->where('to_date', $toDay)
                ->first();

            if ($batch && is_array($batch->item_ids) && $batch->item_ids !== []) {
                $itemIds = array_map('intval', $batch->item_ids);
                $items = DispatchOrderItem::query()
                    ->with(['order.client.city', 'order.city', 'fromBranch', 'toBranch'])
                    ->whereIn('id', $itemIds)
                    ->get()
                    ->sortBy(static fn ($item) => array_search((int) $item->id, $itemIds, true) ?: 9999)
                    ->values();
            }
        }

        if ($items->isEmpty()) {
            return response()->json(['message' => __('message.os_settlement_no_completed_items')], 422);
        }

        $osClient = $osId > 0 ? User::query()->with('city')->find($osId) : null;
        $osName = $this->resolveOsListDisplayName($osId, $osClient);
        $slipData = $settlementService->buildSlipRows($items, $toDateRaw);
        $slipSender = $settlementService->resolveSlipSender($osClient ?? new User(), $items, $osName);
        $draft = OsSettlementDraft::query()
            ->where('os_user_id', $osId)
            ->where('from_date', $fromDay)
            ->where('to_date', $toDay)
            ->first();
        $kpayImageUrl = null;
        if ($batch && $batch->kpay_slip_path) {
            $kpayImageUrl = Storage::disk('public')->url($batch->kpay_slip_path);
        } elseif ($draft?->kpay_slip_path) {
            $kpayImageUrl = Storage::disk('public')->url($draft->kpay_slip_path);
        }

        $html = view('order.dispatch-os-slip-completed', [
            'items' => $items,
            'filterFromDate' => $fromDateRaw,
            'filterToDate' => $toDateRaw,
            'slipCompany' => $this->dispatchOsSlipCompany(),
            'slipSender' => $slipSender,
            'slipRows' => $slipData['rows'],
            'slipTotals' => $slipData['totals'],
        ])->render();

        return response()->json([
            'html' => $html,
            'kpay_image_url' => $kpayImageUrl,
            'amount' => $slipData['totals']['os_to_pay'],
        ]);
    }

    public function dispatchOsSettlementUploadKpay(Request $request, $osId)
    {
        if (! auth()->user()->can('order-edit')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        if ((int) $osId <= 0) {
            return response()->json(['message' => __('message.something_went_wrong')], 422);
        }

        $request->validate([
            'from_date' => 'required|string',
            'to_date' => 'required|string',
            'kpay_slip' => 'required|image|max:10240',
        ]);

        $fromDay = $this->parseDispatchDateInput($request->input('from_date'))->toDateString();
        $toDay = $this->parseDispatchDateInput($request->input('to_date'))->toDateString();
        $osId = (int) $osId;

        $settlementService = app(OsSettlementService::class);
        $draft = $settlementService->uploadKpaySlip(
            $osId,
            $fromDay,
            $toDay,
            $request->file('kpay_slip')
        );

        return response()->json([
            'message' => __('message.os_settlement_kpay_uploaded'),
            'kpay_slip_url' => Storage::disk('public')->url($draft->kpay_slip_path),
        ]);
    }

    public function dispatchOsSettlementFinish(Request $request, $osId)
    {
        if (! auth()->user()->can('order-edit')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        if ((int) $osId <= 0) {
            return response()->json(['message' => __('message.something_went_wrong')], 422);
        }

        $request->validate([
            'from_date' => 'required|string',
            'to_date' => 'required|string',
            'delivery_format' => 'nullable|string|in:table',
            'payment_method' => 'required|string|in:kpay,cash',
            'settlement_side' => 'nullable|string|in:pay,receive',
        ]);

        $fromDay = $this->parseDispatchDateInput($request->input('from_date'))->toDateString();
        $toDay = $this->parseDispatchDateInput($request->input('to_date'))->toDateString();
        $deliveryFormat = app(OsSettlementService::class)->normalizeDeliveryFormat($request->input('delivery_format'));
        $paymentMethod = (string) $request->input('payment_method', 'kpay');
        $settlementSide = $request->input('settlement_side');
        $osId = (int) $osId;

        $osClient = $osId > 0 ? User::query()->with('city')->find($osId) : null;
        if ($osId > 0 && ! $osClient) {
            return response()->json(['message' => __('message.not_found_entry', ['name' => __('message.online_shopping')])], 404);
        }

        $settlementService = app(OsSettlementService::class);
        $osName = $this->resolveOsListDisplayName($osId, $osClient);

        try {
            $batch = $settlementService->finishOs(
                $osId,
                $fromDay,
                $toDay,
                (int) auth()->id(),
                $this->dispatchOsSlipCompany(),
                $osName,
                $osClient ?? new User(),
                $deliveryFormat,
                $paymentMethod,
                $settlementSide
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => __('message.os_settlement_finished', ['amount' => number_format((float) $batch->amount)]),
            'batch_id' => $batch->id,
            'payment_method' => $batch->payment_method,
        ]);
    }

    public function dispatchOsSettlementFinishAll(Request $request)
    {
        if (! auth()->user()->can('order-edit')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        $request->validate([
            'from_date' => 'required|string',
            'to_date' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.os_id' => 'required|integer',
            'items.*.payment_method' => 'required|string|in:kpay,cash',
            'items.*.settlement_side' => 'nullable|string|in:pay,receive',
            'delivery_format' => 'nullable|string|in:table',
            'settlement_side' => 'nullable|string|in:pay,receive',
        ]);

        $fromDay = $this->parseDispatchDateInput($request->input('from_date'))->toDateString();
        $toDay = $this->parseDispatchDateInput($request->input('to_date'))->toDateString();
        $defaultSide = $request->input('settlement_side');
        $itemsPayload = collect($request->input('items', []))
            ->map(static function ($item) use ($defaultSide) {
                $side = $item['settlement_side'] ?? $defaultSide;

                return [
                    'os_id' => (int) ($item['os_id'] ?? 0),
                    'payment_method' => ((string) ($item['payment_method'] ?? 'kpay')) === 'cash' ? 'cash' : 'kpay',
                    'settlement_side' => in_array($side, ['pay', 'receive'], true) ? $side : null,
                ];
            })
            ->filter(static fn ($item) => $item['os_id'] >= 0)
            ->unique(static fn ($item) => $item['os_id'].'|'.($item['settlement_side'] ?? 'all'))
            ->values();
        $settlementService = app(OsSettlementService::class);
        $deliveryFormat = $settlementService->normalizeDeliveryFormat($request->input('delivery_format'));
        $slipCompany = $this->dispatchOsSlipCompany();
        $finished = 0;
        $errors = [];
        $finishedIds = [];

        $osIds = $itemsPayload->pluck('os_id')->map(static fn ($id) => (int) $id)->unique()->values()->all();
        $clients = User::query()
            ->with('city')
            ->whereIn('id', array_values(array_filter($osIds, static fn ($id) => $id > 0)))
            ->get()
            ->keyBy('id');

        @set_time_limit(180);

        foreach ($itemsPayload as $itemPayload) {
            $osId = (int) $itemPayload['os_id'];
            $paymentMethod = (string) $itemPayload['payment_method'];
            $settlementSide = $itemPayload['settlement_side'];

            if ($paymentMethod === 'kpay' || $paymentMethod === 'cash') {
                $kpayPath = $settlementService->getDraftKpayPath($osId, $fromDay, $toDay);
                if (! $kpayPath) {
                    $errors[] = __('message.os_settlement_kpay_missing_for_os', [
                        'name' => $this->resolveOsListDisplayName($osId, $clients->get($osId)),
                    ]);

                    continue;
                }
            }

            $osClient = $osId > 0 ? ($clients->get($osId) ?? null) : null;
            $osName = $this->resolveOsListDisplayName($osId, $osClient);

            try {
                $settlementService->finishOs(
                    $osId,
                    $fromDay,
                    $toDay,
                    (int) auth()->id(),
                    $slipCompany,
                    $osName,
                    $osClient ?? new User(),
                    $deliveryFormat,
                    $paymentMethod,
                    $settlementSide
                );
                $finished++;
                $finishedIds[] = $osId;
            } catch (\RuntimeException $e) {
                $errors[] = $e->getMessage();
            }
        }

        if ($finished <= 0) {
            return response()->json([
                'message' => $errors[0] ?? __('message.os_settlement_finish_all_none'),
            ], 422);
        }

        return response()->json([
            'message' => __('message.os_settlement_finished_all', ['count' => $finished]),
            'finished' => $finished,
            'finished_os_ids' => $finishedIds,
            'errors' => $errors,
        ]);
    }

    protected function resolveOsListDisplayName(int $osId, ?User $client): string
    {
        if ($osId <= 0) {
            return __('message.no_os');
        }

        $name = trim((string) ($client?->name ?? ''));
        if ($name === '') {
            $name = '#'.$osId;
        }
        $cityName = trim((string) ($client?->city?->name ?? ''));
        if ($cityName !== '') {
            $name .= ' ('.$cityName.')';
        }

        return $name;
    }

    /**
     * Admin OS List → Item Detail for one OS + status.
     */
    public function dispatchOsItems(Request $request, $osId)
    {
        if (! auth()->user()->can('order-list')) {
            $message = __('message.demo_permission_denied');

            return redirect()->back()->withErrors($message);
        }

        $statusMap = [
            'collected' => __('message.follow_up_status_collected'),
            'courier_assigned' => __('message.follow_up_status_assigned'),
            'courier_departed' => __('message.follow_up_status_on_way'),
            'delivered' => __('message.follow_up_status_delivered'),
            'pending' => __('message.follow_up_status_pending'),
            'completed' => __('message.follow_up_status_completed'),
            'finished' => __('message.follow_up_status_finished'),
        ];

        $status = trim((string) $request->get('status', 'courier_assigned'));
        if (! array_key_exists($status, $statusMap)) {
            return redirect()
                ->route('order.dispatch.os-list')
                ->withErrors(__('message.delivery_item_status_not_allowed'));
        }

        $osId = (int) $osId;
        $osClient = null;
        $osName = __('message.no_os');
        if ($osId > 0) {
            $osClient = User::query()->with('city')->where('id', $osId)->where('user_type', 'client')->first();
            if (! $osClient) {
                return redirect()
                    ->route('order.dispatch.os-list')
                    ->withErrors(__('message.not_found_entry', ['name' => __('message.online_shopping')]));
            }
            $osName = trim((string) $osClient->name);
            $cityName = trim((string) ($osClient->city?->name ?? ''));
            if ($cityName !== '') {
                $osName .= ' ('.$cityName.')';
            }
        }

        $yangonToday = Carbon::now('Asia/Yangon')->format('d-m-Y');
        $fromDateRaw = trim((string) $request->get('from_date', $yangonToday));
        $toDateRaw = trim((string) $request->get('to_date', $fromDateRaw));
        $fromDay = $this->parseDispatchDateInput($fromDateRaw)->toDateString();
        $toDay = $this->parseDispatchDateInput($toDateRaw)->toDateString();
        if ($toDay < $fromDay) {
            $toDay = $fromDay;
            $toDateRaw = $fromDateRaw;
        }

        $search = trim((string) $request->get('search', ''));

        $itemsQuery = DispatchOrderItem::query()
            ->with(['order.client.city', 'order.city', 'pendingPhotoMedia', 'deliveredPhotoMedia'])
            ->whereHas('order', function ($q) use ($osId) {
                if ($osId > 0) {
                    $q->where('client_id', $osId);
                } else {
                    $q->where(function ($inner) {
                        $inner->whereNull('client_id')->orWhere('client_id', 0);
                    });
                }
            })
            ->where(function ($dateQuery) use ($fromDay, $toDay) {
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

        if ($status === 'collected') {
            $itemsQuery->whereIn('status', ['collected', 'assigned']);
        } elseif ($status === 'delivered') {
            $itemsQuery->where('status', 'completed')->whereNull('admin_completed_at');
        } elseif ($status === 'completed') {
            $itemsQuery->where('status', 'completed')
                ->whereNotNull('admin_completed_at')
                ->whereNull('admin_finished_at');
        } elseif ($status === 'finished') {
            $itemsQuery->where('status', 'completed')->whereNotNull('admin_finished_at');
        } else {
            $itemsQuery->where('status', $status);
        }

        if ($search !== '') {
            $itemsQuery->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('customer_address', 'like', "%{$search}%")
                    ->orWhere('township', 'like', "%{$search}%")
                    ->orWhere('remark', 'like', "%{$search}%");
            });
        }

        $items = $itemsQuery->orderByDesc('id')->get();

        $canBulkUpdate = $status === 'completed';
        $bulkActions = $canBulkUpdate
            ? ['admin_finished' => __('message.follow_up_status_finished')]
            : [];

        $slipCompany = $this->dispatchOsSlipCompany();
        $slipSender = [
            'name' => $osName,
            'phone' => $osId > 0
                ? (normalizeContactNumber((string) ($osClient?->contact_number ?? '')) ?: '-')
                : '-',
            'address' => '-',
        ];
        if ($items->isNotEmpty()) {
            $firstOrder = $items->first()->order;
            if ($firstOrder) {
                $slipSender['name'] = resolveDispatchOsName($firstOrder) !== '-'
                    ? resolveDispatchOsName($firstOrder)
                    : $osName;
                $phone = resolveDispatchOsPhone($firstOrder);
                $address = resolveDispatchOsAddress($firstOrder);
                if ($phone !== '-') {
                    $slipSender['phone'] = $phone;
                }
                if ($address !== '-') {
                    $slipSender['address'] = $address;
                }
            }
        }

        $pageTitle = __('message.item_details');
        $assets = [];
        $statusLabel = $statusMap[$status];
        $filterFromDate = $fromDateRaw;
        $filterToDate = $toDateRaw;

        return view('order.dispatch-os-items', compact(
            'pageTitle',
            'assets',
            'osId',
            'osName',
            'osClient',
            'items',
            'status',
            'statusLabel',
            'statusMap',
            'filterFromDate',
            'filterToDate',
            'fromDay',
            'toDay',
            'search',
            'canBulkUpdate',
            'bulkActions',
            'slipCompany',
            'slipSender'
        ));
    }

    /**
     * Admin OS List — bulk update selected item statuses (Completed → Finished).
     */
    public function dispatchOsItemsBulkUpdate(Request $request, $osId)
    {
        if (! auth()->user()->can('order-edit')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        $data = $request->validate([
            'item_ids' => 'required|array|min:1',
            'item_ids.*' => 'integer',
            'to_status' => 'required|string|in:admin_finished',
        ]);

        $osId = (int) $osId;
        $ids = array_values(array_unique(array_map('intval', $data['item_ids'])));

        $query = DispatchOrderItem::query()
            ->where('status', 'completed')
            ->whereNotNull('admin_completed_at')
            ->whereNull('admin_finished_at')
            ->whereIn('id', $ids)
            ->whereHas('order', function ($q) use ($osId) {
                if ($osId > 0) {
                    $q->where('client_id', $osId);
                } else {
                    $q->where(function ($inner) {
                        $inner->whereNull('client_id')->orWhere('client_id', 0);
                    });
                }
            });

        $updated = $query->update([
            'admin_finished_at' => now(),
            'admin_updated_at' => now(),
            'updated_at' => now(),
        ]);

        if ($updated <= 0) {
            return response()->json([
                'message' => __('message.rider_items_mark_finished_none'),
            ], 422);
        }

        return response()->json([
            'message' => __('message.rider_items_marked_finished', ['count' => $updated]),
            'updated' => $updated,
        ]);
    }

    protected function osListStatusKey(DispatchOrderItem $item): ?string
    {
        $status = (string) ($item->status ?? '');

        return match ($status) {
            'collected', 'assigned' => 'collected',
            'courier_assigned' => 'courier_assigned',
            'courier_departed' => 'courier_departed',
            'pending' => 'pending',
            'completed' => ! empty($item->admin_finished_at)
                ? 'finished'
                : (! empty($item->admin_completed_at) ? 'completed' : 'delivered'),
            default => null,
        };
    }

    /**
     * Branding block for OS List → Slip Completed.
     */
    protected function dispatchOsSlipCompany(): array
    {
        $app = appSettingData('get');
        $companyName = SettingData('order_invoice', 'company_name')
            ?: ($app->site_name ?? null)
            ?: 'Point Delivery';
        $companyPhone = SettingData('order_invoice', 'company_contact_number')
            ?: ($app->contact_number ?? null)
            ?: ($app->help_support_number ?? null)
            ?: '09400080670, 09402578059';
        $companyAddress = SettingData('order_invoice', 'company_address')
            ?: ($app->site_description ?? null)
            ?: '62A, 104A*105.';
        $companyEmail = SettingData('order_invoice', 'company_email')
            ?: ($app->site_email ?? null)
            ?: 'point@gmail.com';

        $logoUrl = SettingData('order_invoice', 'company_logo');
        if (! $logoUrl) {
            $logoSetting = Setting::where('type', 'order_invoice')->where('key', 'company_logo')->first();
            if ($logoSetting) {
                $logoUrl = getSingleMedia($logoSetting, 'company_logo', null);
            }
        }
        if (! $logoUrl && $app) {
            $logoUrl = getSingleMedia($app, 'site_logo', null);
        }

        return [
            'name' => $companyName,
            'phone' => $companyPhone,
            'address' => $companyAddress,
            'email' => $companyEmail,
            'logo' => $logoUrl,
        ];
    }

    public function dispatchItemEdit($orderId, $itemId)
    {
        if (!auth()->user()->can('order-edit')) {
            $message = __('message.demo_permission_denied');
            return redirect()->back()->withErrors($message);
        }

        $order = Order::findOrFail($orderId);
        $item = DispatchOrderItem::with('photoMedia')->where('order_id', $orderId)->findOrFail($itemId);
        $workflow = app(DispatchOrderWorkflowService::class);
        if (! $workflow->canAdminEditDispatchItemInfo($order, $item)) {
            return response()->json([
                'message' => __('message.dispatch_item_edit_requires_pickup_rider'),
            ], 422);
        }

        $cities = \App\Models\Branch::where('status', 1)->orderBy('name')->get();
        $deliveryCities = $this->dispatchDeliveryCities();

        return view('order.dispatch-item-form', compact('order', 'item', 'cities', 'deliveryCities'));
    }

    public function dispatchItemCreate($id)
    {
        if (!auth()->user()->can('order-add')) {
            $message = __('message.demo_permission_denied');
            return redirect()->back()->withErrors($message);
        }

        $order = Order::findOrFail($id);
        $workflow = app(DispatchOrderWorkflowService::class);
        if (! $workflow->canAdminEditDispatchItemInfo($order)) {
            return response()->json([
                'message' => __('message.dispatch_item_edit_requires_pickup_rider'),
            ], 422);
        }

        $cities = \App\Models\Branch::where('status', 1)->orderBy('name')->get();
        $deliveryCities = $this->dispatchDeliveryCities();

        return view('order.dispatch-item-form', compact('order', 'cities', 'deliveryCities'));
    }

    public function dispatchItemStore(Request $request, $id)
    {
        if (!auth()->user()->can('order-add')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        $order = Order::findOrFail($id);
        $workflow = app(DispatchOrderWorkflowService::class);
        if (! $workflow->canAdminEditDispatchItemInfo($order)) {
            return response()->json([
                'message' => __('message.dispatch_item_edit_requires_pickup_rider'),
            ], 422);
        }

        $data = $this->validateDispatchItem($request, $order);
        $amounts = DispatchOrderItem::computeAmounts(
            (float) $data['item_value'],
            (float) $data['deli_amount'],
            (float) $data['advance_paid'],
            (float) $data['os_paid'],
            $data['credit_to']
        );

        $item = DispatchOrderItem::create(array_merge($data, $amounts, [
            'order_id' => $order->id,
            'received_date' => $this->parseDispatchDateInput($data['received_date']),
            'status' => 'collected',
            'code' => DispatchOrderItem::generateCode(),
        ]));

        try {
            app(DispatchOrderAuditService::class)->logItemCreated($order, $item, 'admin');
        } catch (\Throwable $e) {
            \Log::warning('dispatch audit failed after admin item create', [
                'order_id' => $order->id,
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
        }

        $this->syncDispatchOrderTotals($order);

        return response()->json(['message' => __('message.save_form', ['form' => __('message.item_name')])]);
    }

    public function dispatchItemUpdate(Request $request, $orderId, $itemId)
    {
        if (!auth()->user()->can('order-edit')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        $order = Order::findOrFail($orderId);
        $item = DispatchOrderItem::where('order_id', $orderId)->findOrFail($itemId);
        $workflow = app(DispatchOrderWorkflowService::class);
        if (! $workflow->canAdminEditDispatchItemInfo($order, $item)) {
            return response()->json([
                'message' => __('message.dispatch_item_edit_requires_pickup_rider'),
            ], 422);
        }

        $audit = app(DispatchOrderAuditService::class);
        $before = $audit->itemSnapshot($item);
        $data = $this->validateDispatchItem($request, $order);
        $amounts = DispatchOrderItem::computeAmounts(
            (float) $data['item_value'],
            (float) $data['deli_amount'],
            (float) $data['advance_paid'],
            (float) $data['os_paid'],
            $data['credit_to']
        );

        // Keep User/Delivery pickup pay chip in sync with Admin credit_to / os_paid.
        $pickupPayMode = 'customer_pay';
        if ((float) ($data['os_paid'] ?? 0) > 0) {
            $pickupPayMode = 'pay_done';
        } elseif (($data['credit_to'] ?? 'customer') === 'os') {
            $pickupPayMode = 'os_pay';
        }

        $item->update(array_merge($data, $amounts, [
            'received_date' => $this->parseDispatchDateInput($data['received_date']),
            'pickup_pay_mode' => $pickupPayMode,
            'admin_updated_at' => now(),
        ]));

        $audit->logAdminItemInfo($order, $item->fresh(), null, $before);

        $this->syncDispatchOrderTotals($order);
        $order = $order->fresh();
        $workflow->syncOrderWorkflow($order);

        $fresh = $order->fresh();
        $response = ['message' => __('message.update_form', ['form' => __('message.item_name')])];
        if ($workflow->isReadyForAssign100($fresh)) {
            $response['moved_to_assign_100'] = true;
            $response['redirect'] = route('order.dispatch.assign-100');
            $response['message'] = __('message.dispatch_items_moved_to_assign_100');
        } elseif ($workflow->isAdminDoneAwaitingRider($fresh)) {
            $response['moved_to_admin_done'] = true;
            $response['redirect'] = route('order.index', [
                'orders_type' => 'list',
                'dispatch_status' => 'admin_completed',
            ]);
            $response['message'] = __('message.dispatch_items_moved_to_admin_done');
        }

        return response()->json($response);
    }

    /**
     * Production "Update Gate" — set gate fee on an item (OS/Rider Item Detail).
     */
    public function dispatchItemUpdateGate(Request $request, $orderId, $itemId)
    {
        if (! auth()->user()->can('order-edit')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        $data = $request->validate([
            'gate_amount' => 'nullable|numeric|min:0',
            'gate_os_paid' => 'nullable|numeric|min:0',
            'remark' => 'nullable|string|max:1000',
        ]);

        $item = DispatchOrderItem::query()
            ->where('order_id', (int) $orderId)
            ->findOrFail((int) $itemId);

        $item->gate_amount = (float) ($data['gate_amount'] ?? 0);
        $item->gate_os_paid = (float) ($data['gate_os_paid'] ?? 0);
        if (array_key_exists('remark', $data) && $data['remark'] !== null) {
            $item->remark = trim((string) $data['remark']);
        }
        $item->admin_updated_at = now();
        $item->save();

        $displayOsToPay = $item->displayOsToPay();
        $osToPaySlip = formatDispatchOsToPaySlip($displayOsToPay);

        return response()->json([
            'message' => __('message.updated_successfully') ?? 'Updated',
            'gate_amount' => (float) $item->gate_amount,
            'gate_os_paid' => (float) $item->gate_os_paid,
            'net_gate_charge' => $item->netGateCharge(),
            'base_os_to_pay' => $item->baseDisplayOsToPay(),
            'os_to_pay' => $displayOsToPay,
            'os_to_pay_slip' => $osToPaySlip['value'],
            'os_to_pay_display' => $osToPaySlip['formatted'],
            'os_to_pay_is_receive' => $osToPaySlip['is_receive'],
            'remark' => (string) ($item->remark ?? ''),
        ]);
    }

    public function dispatchItemDestroy($orderId, $itemId)
    {
        if (!auth()->user()->can('order-delete')) {
            if (request()->ajax()) {
                return response()->json(['message' => __('message.demo_permission_denied')], 403);
            }
            return redirect()->back()->withErrors(__('message.demo_permission_denied'));
        }

        $order = Order::findOrFail($orderId);
        $item = DispatchOrderItem::where('order_id', $orderId)->findOrFail($itemId);
        $workflow = app(DispatchOrderWorkflowService::class);
        if (! $workflow->canAdminEditDispatchItemInfo($order, $item)) {
            if (request()->ajax()) {
                return response()->json([
                    'message' => __('message.dispatch_item_edit_requires_pickup_rider'),
                ], 422);
            }

            return redirect()->back()->withErrors(__('message.dispatch_item_edit_requires_pickup_rider'));
        }

        $audit = app(DispatchOrderAuditService::class);
        $itemMeta = [
            'id' => $item->id,
            'code' => $item->code,
            'snapshot' => $audit->itemSnapshot($item),
        ];
        $item->delete();

        try {
            $audit->logItemDeleted($order, $itemMeta, 'admin');
        } catch (\Throwable $e) {
            \Log::warning('dispatch audit failed after admin item delete', [
                'order_id' => $order->id,
                'item_id' => $itemMeta['id'],
                'error' => $e->getMessage(),
            ]);
        }

        $this->syncDispatchOrderTotals($order);

        if (request()->ajax()) {
            return response()->json(['message' => __('message.delete_form', ['form' => __('message.item_name')])]);
        }

        return redirect()->route('order.dispatch.items', $orderId)->withSuccess(__('message.delete_form', ['form' => __('message.item_name')]));
    }

    public function dispatchItemMessages($orderId, $itemId)
    {
        if (! auth()->user()->can('order-list')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        $item = DispatchOrderItem::with('order.client.media')->where('order_id', $orderId)->findOrFail($itemId);

        // Admin opened the thread — mark OS replies as read.
        \App\Models\DispatchItemMessage::query()
            ->where('dispatch_order_item_id', $item->id)
            ->where('sender_type', 'client')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = \App\Models\DispatchItemMessage::query()
            ->with(['sender:id,name,user_type', 'media'])
            ->where('dispatch_order_item_id', $item->id)
            ->orderBy('created_at')
            ->get()
            ->map(function ($m) {
                $imageUrl = $m->imageUrl();

                return [
                    'id' => $m->id,
                    'message' => $m->message,
                    'message_type' => $m->message_type ?: ($imageUrl ? 'image' : 'text'),
                    'chat_image' => $imageUrl,
                    'sender_type' => $m->sender_type,
                    'sender_name' => optional($m->sender)->name,
                    'created_at' => optional($m->created_at)?->format('d-m-Y H:i'),
                    'is_admin' => $m->sender_type !== 'client',
                ];
            });

        $order = $item->order;
        $osName = resolveDispatchOsName($order);
        $osProfileImage = resolveUploadedProfileImageUrl(optional($order)->client);
        $metaLine = trim(
            ($item->code ? '#'.$item->code : '#'.$item->id)
            .' · '
            .($item->customer_phone ?: '')
        );

        return response()->json([
            'item_id' => $item->id,
            'customer_name' => $item->customer_name,
            'os_name' => $osName !== '-' ? $osName : null,
            'os_profile_image' => $osProfileImage,
            'meta_line' => $metaLine,
            'messages' => $messages,
        ]);
    }

    public function dispatchItemMessageStore(Request $request, $orderId, $itemId)
    {
        if (! auth()->user()->can('order-edit')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        $request->validate([
            'message' => 'nullable|string|max:5000',
            'chat_image' => 'nullable|image|max:10240',
        ]);

        if (! $request->filled('message') && ! $request->hasFile('chat_image')) {
            return response()->json(['message' => __('message.required', ['name' => __('message.chat')])], 422);
        }

        $order = Order::with('client')->findOrFail($orderId);
        $item = DispatchOrderItem::where('order_id', $orderId)->findOrFail($itemId);
        $user = auth()->user();
        $body = trim((string) $request->input('message', ''));
        $hasImage = $request->hasFile('chat_image');

        $message = \App\Models\DispatchItemMessage::create([
            'dispatch_order_item_id' => $item->id,
            'order_id' => $order->id,
            'client_id' => $order->client_id,
            'sender_id' => $user->id,
            'sender_type' => 'admin',
            'message_type' => $hasImage ? 'image' : 'text',
            'message' => $body !== '' ? $body : ($hasImage ? '' : ''),
        ]);

        // Admin reply counts as handling the thread — unread client messages sink to the bottom.
        \App\Models\DispatchItemMessage::query()
            ->where('dispatch_order_item_id', $item->id)
            ->where('sender_type', 'client')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if ($hasImage) {
            uploadMediaFile($message, $request->file('chat_image'), 'chat_image');
        }

        $imageUrl = $message->fresh()->imageUrl();
        $pushPreview = $body !== '' ? $body : ($hasImage ? __('message.image') : '');

        try {
            if ($order->client) {
                app(\App\Services\AppPushService::class)->notifyClientItemMessage($order->client, $item, (string) $pushPreview);
            }
        } catch (\Throwable $e) {
            \Log::warning('push failed after admin item message', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'message' => __('message.save_form', ['form' => __('message.chat')]),
            'data' => [
                'id' => $message->id,
                'message' => $message->message,
                'message_type' => $message->message_type,
                'chat_image' => $imageUrl,
                'sender_type' => $message->sender_type,
                'sender_name' => $user->name,
                'created_at' => optional($message->created_at)?->format('d-m-Y H:i'),
                'is_admin' => true,
            ],
        ]);
    }

    private function dispatchDeliveryCities(): array
    {
        $cities = config('dispatch_item_cities.cities');

        if (is_array($cities) && !empty($cities)) {
            return $cities;
        }

        return [
            ['name' => 'Mandalay', 'name_mm' => 'မန္တလေး', 'nrc_state' => 'Mandalay'],
            ['name' => 'Monywa', 'name_mm' => 'မုံရွာ', 'nrc_state' => 'Sagaing'],
            ['name' => 'Myitkyina', 'name_mm' => 'မြစ်ကြီးနား', 'nrc_state' => 'Kachin'],
            ['name' => 'Nay Pyi Taw', 'name_mm' => 'နေပြည်တော်', 'nrc_state' => 'Mandalay'],
            ['name' => 'Pyin Oo Lwin', 'name_mm' => 'ပြင်ဦးလွင်', 'nrc_state' => 'Mandalay'],
            ['name' => 'Sagaing', 'name_mm' => 'စစ်ကိုင်း', 'nrc_state' => 'Sagaing'],
            ['name' => 'Taung Gyi', 'name_mm' => 'တောင်ကြီး', 'nrc_state' => 'Shan'],
            ['name' => 'Yangon', 'name_mm' => 'ရန်ကုန်', 'nrc_state' => 'Yangon'],
        ];
    }

    private function validateDispatchItem(Request $request, Order $order): array
    {
        $data = $request->validate([
            'received_date' => 'required',
            'from_branch_id' => 'required|exists:branches,id',
            'to_branch_id' => 'required|string|max:255',
            'delivery_city' => 'required|string|max:255',
            'township' => 'required|string|max:255',
            'item_name' => 'nullable|string|max:2000',
            'remark' => 'nullable|string|max:2000',
            'weight' => 'nullable|integer|min:1|max:10',
            'advance_paid' => 'nullable|numeric|min:0',
            'os_paid' => 'nullable|numeric|min:0',
            'item_value' => 'nullable|numeric|min:0',
            'deli_amount' => 'nullable|numeric|min:0',
            'customer_phone' => 'nullable|string|max:50',
            'customer_name' => 'nullable|string|max:255',
            'customer_address' => 'nullable|string|max:500',
            'credit_to' => 'required|in:os,customer',
        ]);

        $data['to_branch_id'] = $this->resolveDispatchToBranchId($data['to_branch_id']);
        $data['delivery_city'] = trim($data['delivery_city']);
        $data['township'] = trim($data['township']);

        return $this->normalizeDispatchItemData($data, $order);
    }

    private function orderDisallowsOsCredit(Order $order): bool
    {
        return false;
    }

    private function applyDeliveryRecipientsToOrderData(Request $request, array $data, bool $isSelfOrder, ?int &$dispatchItemCount = null): array
    {
        $textService = app(TextOrderDispatchService::class);

        if (! $isSelfOrder) {
            $recipients = $this->normalizeDeliveryRecipients($request->input('delivery_recipients', []));
            if (! empty($recipients)) {
                $delivery = is_array($data['delivery_point'] ?? null) ? $data['delivery_point'] : [];
                $delivery['recipients'] = $recipients;
                $first = $recipients[0];
                $delivery['name'] = $first['name'] ?? ($delivery['name'] ?? '');
                $delivery['contact_number'] = $first['contact_number'] ?? ($delivery['contact_number'] ?? '');
                $delivery['address'] = $first['address'] ?? ($delivery['address'] ?? '');
                $data['delivery_point'] = $delivery;
                $dispatchItemCount = $textService->sumRecipientParcels($recipients);
                $data['total_parcel'] = $dispatchItemCount;

                return $data;
            }
        }

        $dispatchItemCount = max(1, (int) ($data['total_parcel'] ?? $request->input('total_parcel', 1)));
        $data['total_parcel'] = $isSelfOrder ? 1 : $dispatchItemCount;

        return $data;
    }

    private function normalizeDeliveryRecipients($recipients): array
    {
        if (! is_array($recipients)) {
            return [];
        }

        $normalized = [];

        foreach ($recipients as $recipient) {
            if (! is_array($recipient)) {
                continue;
            }

            $inputMode = (($recipient['input_mode'] ?? 'text') === 'photo') ? 'photo' : 'text';
            $name = trim((string) ($recipient['name'] ?? ''));
            $phone = trim((string) ($recipient['contact_number'] ?? ''));
            $address = trim((string) ($recipient['address'] ?? ''));
            $parcelCount = 1;

            if ($inputMode === 'text' && ($name === '' || $phone === '' || $address === '')) {
                continue;
            }

            if ($inputMode === 'photo' && $parcelCount < 1) {
                continue;
            }

            $payment = $this->normalizeDispatchPaymentPayload($recipient);

            $normalized[] = array_merge([
                'name' => $name,
                'contact_number' => $phone !== '' ? normalizeContactNumber($phone) : '',
                'address' => $address,
                'parcel_count' => $parcelCount,
                'input_mode' => $inputMode,
            ], $payment);
        }

        return $normalized;
    }

    private function normalizeDispatchPaymentPayload($payload): array
    {
        if (! is_array($payload)) {
            return ['collect_money' => 0];
        }

        if ((int) ($payload['collect_money'] ?? 0) !== 1) {
            return ['collect_money' => 0];
        }

        $creditToRaw = $payload['credit_to'] ?? 'customer';
        $creditTo = in_array($creditToRaw, ['os', 'customer'], true) ? $creditToRaw : 'customer';

        return [
            'collect_money' => 1,
            'item_value' => (float) ($payload['item_value'] ?? 0),
            'deli_amount' => (float) ($payload['deli_amount'] ?? 0),
            'credit_to' => $creditTo,
            'os_paid' => $creditTo === 'os' ? (float) ($payload['os_paid'] ?? 0) : 0,
        ];
    }

    private function resolveDispatchToBranchId(string $toBranchId): int
    {
        $toBranchId = trim($toBranchId);

        if (str_starts_with($toBranchId, 'custom:')) {
            $name = trim(substr($toBranchId, 7));
            if ($name === '') {
                throw ValidationException::withMessages([
                    'to_branch_id' => [__('message.select_name', ['select' => __('message.to')])],
                ]);
            }

            return (int) $this->findOrCreateBranch($name)->id;
        }

        if (!ctype_digit($toBranchId) || !\App\Models\Branch::where('id', $toBranchId)->where('status', 1)->exists()) {
            throw ValidationException::withMessages([
                'to_branch_id' => [__('message.select_name', ['select' => __('message.to')])],
            ]);
        }

        return (int) $toBranchId;
    }

    private function findOrCreateBranch(string $name): \App\Models\Branch
    {
        return \App\Models\Branch::firstOrCreate(
            ['name' => $name],
            ['status' => 1]
        );
    }

    private function normalizeDispatchItemData(array $data, ?Order $order = null): array
    {
        foreach (['advance_paid', 'os_paid', 'item_value', 'deli_amount'] as $field) {
            if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                $data[$field] = 0;
            }
        }

        $data['weight'] = normalizeDispatchItemSize($data['weight'] ?? 0);

        if (! empty($data['customer_phone'])) {
            $data['customer_phone'] = normalizeContactNumber($data['customer_phone']);
        }

        if ($order && $this->orderDisallowsOsCredit($order)) {
            $data['credit_to'] = 'customer';
            $data['os_paid'] = 0;
        } elseif (($data['credit_to'] ?? 'customer') === 'customer') {
            $data['os_paid'] = 0;
        }

        $data['city_id'] = null;

        return $data;
    }

    private function syncDispatchOrderTotals(Order $order): void
    {
        $collectedCount = DispatchOrderItem::query()
            ->where('order_id', $order->id)
            ->where('status', 'collected')
            ->count();

        $order->update([
            'total_parcel' => max(1, (int) $order->total_parcel, $collectedCount),
            'total_amount' => (float) $order->dispatchItems()->sum('deli_amount'),
        ]);
    }

    public function dispatchEdit($id)
    {
        if (!auth()->user()->can('order-edit')) {
            $message = __('message.demo_permission_denied');
            return redirect()->back()->withErrors($message);
        }

        $data = Order::with(['delivery_man', 'profofPictures'])->findOrFail($id);
        $gatePassImages = collectGatePassImages($data);

        $pageTitle = __('message.update_form_title', ['form' => __('message.order')]);
        $assets = ['datatable'];
        $pickupRiders = $this->getDispatchPickupRiders();

        return view('order.dispatch-form', compact('pageTitle', 'assets', 'data', 'id', 'pickupRiders', 'gatePassImages'));
    }

    public function dispatchUpdate(Request $request, $id)
    {
        if (!auth()->user()->can('order-edit')) {
            $message = __('message.demo_permission_denied');
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json(['status' => false, 'message' => $message], 403);
            }

            return redirect()->back()->withErrors($message);
        }

        $request->validate([
            'received_date' => 'required',
            'client_id' => 'required|exists:users,id',
            'os_name' => 'required|string|max:255',
            'os_phone' => 'required|string|max:50',
            'os_address' => 'required|string|max:500',
            'order_count' => 'required|integer|min:1',
            'delivery_man_id' => 'required|exists:users,id',
            'remark' => 'nullable|string|max:1000',
        ]);

        $order = Order::findOrFail($id);
        $client = User::findOrFail($request->client_id);
        $receivedDate = $this->parseDispatchDateInput($request->received_date);
        [$pickupDatetime, $deliveryDatetime] = $this->resolveDispatchSchedule($request, $receivedDate);

        $pickupPoint = [
            'name' => $request->os_name,
            'contact_number' => normalizeContactNumber($request->os_phone),
            'address' => $request->os_address,
        ];

        $existingPickup = is_array($order->pickup_point) ? $order->pickup_point : [];
        $pickupPoint = array_merge($existingPickup, $pickupPoint);

        $deliveryPoint = is_array($order->delivery_point) ? $order->delivery_point : [];
        if ((int) ($order->is_shop_order ?? 0) !== 1 && (int) ($order->is_gate_order ?? 0) !== 1) {
            $deliveryPoint = $pickupPoint;
        }

        $order->update([
            'client_id' => $request->client_id,
            'pickup_point' => $pickupPoint,
            'delivery_point' => $deliveryPoint,
            'total_parcel' => 1,
            'description' => $request->remark,
            'delivery_man_id' => (int) $request->delivery_man_id,
            'country_id' => $client->country_id,
            'city_id' => $client->city_id,
            'date' => $pickupDatetime,
            'pickup_datetime' => $pickupDatetime,
            'delivery_datetime' => $deliveryDatetime,
        ]);

        $order = app(\App\Services\PickupParcelDispatchService::class)
            ->ensureAssignedForPickup($order->fresh());

        $message = __('message.update_form', ['form' => __('message.order')]);

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'status' => true,
                'message' => $message,
                'order_id' => $order->id,
            ]);
        }

        return redirect()->route('order.dispatch.items', $order->id)->withSuccess($message);
    }

    private function resolveDispatchSchedule(Request $request, Carbon $receivedDate): array
    {
        if ($request->input('order_mode') !== 'schedule') {
            return [$receivedDate, $receivedDate->copy()->addHour()];
        }

        $scheduleDate = $this->parseDispatchDateInput($request->pickup_schedule_date ?? $request->received_date);
        $startTime = $request->pickup_start_time ?: '09:00';
        $endTime = $request->pickup_end_time ?: $startTime;

        $pickupDatetime = Carbon::parse($scheduleDate->format('Y-m-d') . ' ' . $startTime);
        $deliveryDatetime = Carbon::parse($scheduleDate->format('Y-m-d') . ' ' . $endTime);

        if ($deliveryDatetime->lte($pickupDatetime)) {
            $deliveryDatetime = $pickupDatetime->copy()->addHour();
        }

        return [$pickupDatetime, $deliveryDatetime];
    }

    private function parseDispatchDateInput($value): Carbon
    {
        try {
            return Carbon::createFromFormat('d-m-Y', $value)->startOfDay();
        } catch (\Exception $e) {
            return Carbon::parse($value)->startOfDay();
        }
    }

    /**
     * Rider List date filter (From–To inclusive) — day-by-day window.
     *
     * Delivered / Completed / Finished: dated activity in range.
     * Assigned / On Way / Pending: also include still-open items that started
     * on or before toDay (so On Way keeps showing on later days until delivered).
     */
    private function applyRiderListDateFilter($dateQuery, string $fromDay, string $toDay): void
    {
        $dateQuery->whereBetween('received_date', [$fromDay, $toDay])
            ->orWhere(function ($assigned) use ($fromDay, $toDay) {
                $assigned->whereNotNull('assigned_at')
                    ->whereDate('assigned_at', '>=', $fromDay)
                    ->whereDate('assigned_at', '<=', $toDay);
            })
            ->orWhere(function ($onWay) use ($fromDay, $toDay) {
                $onWay->where('status', 'courier_departed')
                    ->whereDate('updated_at', '>=', $fromDay)
                    ->whereDate('updated_at', '<=', $toDay);
            })
            ->orWhere(function ($delivered) use ($fromDay, $toDay) {
                $delivered->where('status', 'completed')
                    ->where(function ($when) use ($fromDay, $toDay) {
                        $when->where(function ($d) use ($fromDay, $toDay) {
                            $d->whereNotNull('delivered_at')
                                ->whereDate('delivered_at', '>=', $fromDay)
                                ->whereDate('delivered_at', '<=', $toDay);
                        })->orWhere(function ($r) use ($fromDay, $toDay) {
                            $r->whereNotNull('rider_remit_date')
                                ->whereDate('rider_remit_date', '>=', $fromDay)
                                ->whereDate('rider_remit_date', '<=', $toDay);
                        })->orWhere(function ($c) use ($fromDay, $toDay) {
                            $c->whereNotNull('admin_completed_at')
                                ->whereDate('admin_completed_at', '>=', $fromDay)
                                ->whereDate('admin_completed_at', '<=', $toDay);
                        });
                    });
            })
            ->orWhere(function ($fallback) use ($fromDay, $toDay) {
                $fallback->whereNull('received_date')
                    ->whereNull('assigned_at')
                    ->whereDate('created_at', '>=', $fromDay)
                    ->whereDate('created_at', '<=', $toDay);
            })
            // Still-open Assigned / On Way / Pending carried into later day views.
            ->orWhere(function ($open) use ($toDay) {
                $open->whereIn('status', ['courier_assigned', 'courier_departed', 'pending'])
                    ->where(function ($started) use ($toDay) {
                        $started->where(function ($a) use ($toDay) {
                            $a->whereNotNull('assigned_at')
                                ->whereDate('assigned_at', '<=', $toDay);
                        })->orWhere(function ($r) use ($toDay) {
                            $r->whereNotNull('received_date')
                                ->whereDate('received_date', '<=', $toDay);
                        })->orWhere(function ($c) use ($toDay) {
                            $c->whereNull('assigned_at')
                                ->whereNull('received_date')
                                ->whereDate('created_at', '<=', $toDay);
                        });
                    });
            });
    }

    private function getDispatchPickupRiders()
    {
        return User::select('id', 'name')
            ->where('user_type', 'delivery_man')
            ->where('status', 1)
            ->availableForAssign()
            ->orderBy('name')
            ->get();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request)
    {
        $data = $request->all();
        $symbol = $request->input('packaging_symbols');

        if (is_array($symbol)) {
            $symbols = [];
            foreach ($symbol as $charge) {
                if (isset($charge['title'], $charge['key'])) {
                    $chargeEntry = [
                        'title' => $charge['title'],
                        'key' => $charge['key'],
                    ];
                    $symbols[] = $chargeEntry;
                }
            }
            $data['packaging_symbols'] = json_encode($symbols);
        }

        $currency = appSettingcurrency()->currency ?? '$';
        $data['currency'] = $currency;
        $data['milisecond'] = strtoupper(appSettingcurrency('prefix')) . '' . round(microtime(true) * 1000);
        $data['pickup_vehicle_type'] = in_array($request->input('pickup_vehicle_type'), ['car', 'motorcycle'], true)
            ? $request->input('pickup_vehicle_type')
            : 'motorcycle';

        if ($request->has('vehicle_id') && $request->input('vehicle_id') != null) {
            $data['vehicle_data'] = Vehicle::where('id', $request->input('vehicle_id'))->first() ?? null;
        }

        if (!$request->is('api/*')) {
            $extraCharges = $request->input('extra_charges');
            if ($extraCharges) {
                $extraCharges = json_decode($extraCharges, true);
                if (is_array($extraCharges)) {
                    $formattedCharges = [];
                    foreach ($extraCharges as $charge) {
                        if (isset($charge['title'], $charge['charges'], $charge['charges_type'])) {
                            $chargeEntry = [
                                'key' => $charge['title'],
                                'value' => $charge['charges'],
                                'value_type' => $charge['charges_type']
                            ];
                            $formattedCharges[] = $chargeEntry;
                        }
                    }
                    $data['extra_charges'] = $formattedCharges;
                }
            }
        }
        if ($request->is('api/*')) {
            $data['status'] = ($data['payment_type'] == 'online') ? 'pending' : ($data['status'] ?? null);
        }

        // Apply welcome promotion discount for new users
        if ($request->is('api/*') && auth()->check() && empty($request->id)) {
            $client = auth()->user();
            $baseTotal = $data['total_amount'] ?? 0;
            $welcomeInfo = \App\Helpers\WelcomePromotionHelper::getDiscountForUser($client, $baseTotal);
            if ($welcomeInfo['eligible'] && $welcomeInfo['discount'] > 0) {
                $data['welcome_discount'] = $welcomeInfo['discount'];
                $data['total_amount'] = max(0, $baseTotal - $welcomeInfo['discount']);
            }
        }

        // Photo-based order flag
        $dispatchItemCount = null;

        if ($request->has('is_photo_order') || $request->hasFile('order_photo')) {
            $data['is_photo_order'] = 1;
            $data['is_text_order'] = 0;
            $data['is_shop_order'] = 0;
            $data['is_gate_order'] = 0;
            $data['parcel_type'] = $data['parcel_type'] ?? __('message.photo_order');
            $data['total_weight'] = $data['total_weight'] ?? 1;
            $data['total_parcel'] = 1;
            $data['payment_collect_from'] = $data['payment_collect_from'] ?? 'on_pickup';

            if (empty($request->id)) {
                $pickup = is_array($data['pickup_point'] ?? null) ? $data['pickup_point'] : [];
                $delivery = is_array($data['delivery_point'] ?? null) ? $data['delivery_point'] : [];

                if (trim((string) ($pickup['address'] ?? '')) === '') {
                    $pickup['address'] = 'ဓာတ်ပုံအော်ဒါ - တည်နေရာအတည်ပြုရန်';
                }
                $pickup['description'] = $pickup['description'] ?? 'ဓာတ်ပုံဖြင့် အော်ဒါ';

                if (trim((string) ($delivery['address'] ?? '')) === '') {
                    $delivery['address'] = 'ဓာတ်ပုံအော်ဒါ - ပို့ဆောင်ရန်လိပ်စာအတည်ပြုရန်';
                }
                $delivery['description'] = $delivery['description'] ?? 'ဓာတ်ပုံဖြင့် အော်ဒါ';

                $data['pickup_point'] = $pickup;
                $data['delivery_point'] = $delivery;
            }
        }

        if ($request->is('api/*') && empty($request->id) && (int) ($data['is_photo_order'] ?? 0) === 1) {
            if (($data['payment_type'] ?? '') !== 'online') {
                $data['status'] = 'create';
            }
        }

        if ($request->has('is_text_order') || $request->boolean('is_text_order')) {
            $data['is_text_order'] = 1;
            $data['is_photo_order'] = 0;
            $data['is_shop_order'] = 0;
            $data['is_gate_order'] = 0;
            $data['parcel_type'] = $data['parcel_type'] ?? 'စာဖြင့် အော်ဒါ';
            $data['total_weight'] = $data['total_weight'] ?? 1;
            // Item count grows as the client adds items — start at 0.
            $data['total_parcel'] = max(0, (int) ($data['total_parcel'] ?? $request->input('total_parcel', 0)));
            $data['payment_collect_from'] = $data['payment_collect_from'] ?? 'on_pickup';
            // Text orders use item-level amounts later — don't store delivery fee as DeliAmount.
            $data['total_amount'] = 0;
            $data['fixed_charges'] = 0;
            $data['weight_charge'] = 0;
            $data['distance_charge'] = 0;
            $data['vehicle_charge'] = $data['vehicle_charge'] ?? 0;

            if (empty($request->id) && $request->is('api/*') && ($data['payment_type'] ?? '') !== 'online') {
                $data['status'] = 'create';
            }
        }

        if ($request->has('is_shop_order') || $request->boolean('is_shop_order')) {
            $data['is_shop_order'] = 1;
            $data['is_photo_order'] = 0;
            $data['is_text_order'] = 0;
            $data['is_gate_order'] = 0;
            $isSelfOrder = $request->has('is_self_order')
                ? $request->boolean('is_self_order')
                : true;
            $data['is_self_order'] = $isSelfOrder ? 1 : 0;
            $data['parcel_type'] = $data['parcel_type'] ?? ($isSelfOrder ? 'ဆိုင် အော်ဒါ (မိမိဆီသို့)' : 'ဆိုင် အော်ဒါ (အခြားလူဆီသို့)');
            $data['total_weight'] = $data['total_weight'] ?? 1;
            $data = $this->applyDeliveryRecipientsToOrderData($request, $data, $isSelfOrder, $dispatchItemCount);
            $data['payment_collect_from'] = $data['payment_collect_from'] ?? 'on_pickup';

            if (empty($request->id) && $request->is('api/*') && ($data['payment_type'] ?? '') !== 'online') {
                $data['status'] = 'create';
            }
        }

        if ($request->has('is_gate_order') || $request->boolean('is_gate_order')) {
            $data['is_gate_order'] = 1;
            $data['is_photo_order'] = 0;
            $data['is_text_order'] = 0;
            $data['is_shop_order'] = 0;
            $isSelfOrder = $request->has('is_self_order')
                ? $request->boolean('is_self_order')
                : true;
            $data['is_self_order'] = $isSelfOrder ? 1 : 0;
            $data['parcel_type'] = $data['parcel_type'] ?? ($isSelfOrder ? 'ဂိတ် အော်ဒါ (မိမိဆီသို့)' : 'ဂိတ် အော်ဒါ (အခြားလူဆီသို့)');
            $data['total_weight'] = $data['total_weight'] ?? 1;
            $data = $this->applyDeliveryRecipientsToOrderData($request, $data, $isSelfOrder, $dispatchItemCount);
            $data['payment_collect_from'] = $data['payment_collect_from'] ?? 'on_pickup';

            if (empty($request->id)) {
                $pickup = is_array($data['pickup_point'] ?? null) ? $data['pickup_point'] : [];
                if ($isSelfOrder) {
                    $pickup['address'] = $pickup['address'] ?? 'ဂိတ်မှ လာယူမည်';
                }
                $pickup['description'] = $pickup['description'] ?? ($isSelfOrder ? 'ဂိတ် အော်ဒါ (မိမိဆီသို့)' : 'ဂိတ် အော်ဒါ (အခြားလူဆီသို့)');
                $data['pickup_point'] = $pickup;
            }

            if (empty($request->id) && $request->is('api/*') && ($data['payment_type'] ?? '') !== 'online') {
                $data['status'] = 'create';
            }
        }

        $data = sanitizeOrderRemarkFields($data);

        // After 11:30 (Asia/Yangon), client API orders are for next day (Admin list).
        if ($request->is('api/*') && auth()->check() && optional(auth()->user())->user_type === 'client') {
            $data = applySameDayOrderCutoffToOrderData($data);
        }

        // Attach order-level money-collect payment (photo / shop-self / gate-self).
        if ($request->has('dispatch_payment') && is_array($request->input('dispatch_payment'))) {
            $payment = $this->normalizeDispatchPaymentPayload($request->input('dispatch_payment'));
            $delivery = is_array($data['delivery_point'] ?? null) ? $data['delivery_point'] : [];
            $delivery['payment'] = $payment;
            $data['delivery_point'] = $delivery;
        }

        $result = Order::updateOrCreate(['id' => $request->id], normalizeOrderContactNumbers($data));

        if ($request->hasFile('order_photo')) {
            uploadMediaFile($result, $request->order_photo, 'order_photo');
        }

        if ((int) $result->is_photo_order === 1) {
            $photoService = app(PhotoOrderDispatchService::class);
            $photoService->ensurePickUpState($result->fresh());
            $photoService->sync($result->fresh());
        }

        if ((int) ($result->is_text_order ?? 0) === 1
            || (int) ($result->is_shop_order ?? 0) === 1
            || (int) ($result->is_gate_order ?? 0) === 1) {
            $textService = app(TextOrderDispatchService::class);
            $textService->ensurePickUpState($result->fresh());
            $dispatchItem = $request->input('dispatch_item');
            if ((int) ($result->is_text_order ?? 0) === 1) {
                // Client text orders start with zero items; items are added later.
                // Only sync when an explicit dispatch_item payload is provided.
                if (is_array($dispatchItem) && ! empty($dispatchItem)) {
                    $textService->syncWithItemData($result->fresh(), $dispatchItem, 1);
                } else {
                    $textService->ensureTextOrderItem($result->fresh());
                }
            } else {
                $freshOrder = $result->fresh();
                if (app(TextOrderDispatchService::class)->isMultiRecipientOrder($freshOrder)) {
                    $textService->syncMultiRecipientItems($freshOrder);
                } else {
                    $textService->sync($freshOrder, $dispatchItemCount);
                }
            }
        }

        // Increment welcome orders used after successful order creation
        if ($result->wasRecentlyCreated && auth()->check() && ($data['welcome_discount'] ?? 0) > 0) {
            \App\Helpers\WelcomePromotionHelper::applyAfterOrder(auth()->user());
        }
        $message = __('message.update_form', ['form' => __('message.order')]);
        if ($result->wasRecentlyCreated) {
            if ($request->cancelorderreturn == 1) {
                $message = __('message.return_order');
            } else {
                $message = __('message.save_form', ['form' => __('message.order')]);
            }

            try {
                app(DispatchOrderAuditService::class)->logOrderCreated($result->fresh());
            } catch (\Throwable $e) {
                \Log::warning('dispatch audit failed after order create', [
                    'order_id' => $result->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($request->has('save_user_address') && $request->save_user_address == 1) {
            $user_pickup_address_data = $result->pickup_point;
            $user_pickup_address_data['user_id'] = $result->client_id;
            $user_pickup_address_data['country_id'] = $result->country_id;
            $user_pickup_address_data['city_id'] = $result->city_id;

            $result->saveUserAddress()->create($user_pickup_address_data);

            $user_delivery_address_data = $result->delivery_point;
            $user_delivery_address_data['user_id'] = $result->client_id;
            $user_delivery_address_data['country_id'] = $result->country_id;
            $user_delivery_address_data['city_id'] = $result->city_id;

            $result->saveUserAddress()->create($user_delivery_address_data);
        }

        if ($request->cancelorderreturn == 1) {
            $updateSuccessful = Order::where('id', $request->order_id)
                ->update(['status' => 'cancelled', 'reason' => $request->reason]);
            if ($updateSuccessful) {
                $data['history_type'] = 'cancelled';
                $data['history_message'] = __('message.cancelled_order');
                $history_data = [
                    'reason' => $request->reason,
                    'status' => 'cancelled',
                ];
                OrderHistory::create($data);
            }
        }

        if ($result->parent_order_id != null) {
            $history_data = [
                'history_type' => 'return',
                'parent_order_id' => $result->parent_order_id,
                'order_id' => $result->parent_order_id,
                'order' => $result,
            ];
            saveOrderHistory($history_data);
        }

        if ($result->status != 'pending') {
            $history_data = [
                'history_type' => $result->status,
                'order_id' => $result->id,
                'order' => $result,
            ];
            saveOrderHistory($history_data);
        }
        if ($result->status === 'create') {
            $app_setting = AppSetting::first();

            if ((int) $result->bid_type === 1) {
                $this->nearByDeliveryman($result, $request->all());
            } else {
                if ($app_setting && $app_setting->auto_assign == 1) {
                    $this->autoAssignOrder($result, $request->all());
                }

                if (!empty($result->pickup_point['contact_number'])) {
                    $this->sendTwilioSMS($result);
                }
            }
        }
        if ($request->is('api/*')) {
            $response = [ 'order_id' => $result->id, 'message' => $message ];
            return json_custom_response($response);
        }
        return redirect()->route('order.index')->withSuccess($message);
    }

    public function autoAssignCancelOrder(Request $request)
    {
        $order_data = Order::find($request->id);

        $result = $this->autoAssignOrder($order_data, $request->all());

        $message = __('message.updated');
        if ($result->delivery_man_id == null) {
            $message = __('message.save_form', ['form' => __('message.order')]);
        }
        if ($request->is('api/*')) {
            $response = [ 'order_id' => $result->id, 'message' => $message ];
            return json_custom_response($response);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (!auth()->user()->can('order-show')) {
            $message = __('message.demo_permission_denied');
            return redirect()->back()->withErrors($message);
        }
        $user = Auth::user();
        $is_vehicle_in_order =  appSettingcurrency('is_vehicle_in_order');
        $pageTitle = __('message.add_form_title', ['form' => __('message.order')]);

        if ($user->user_type == 'client') {
            $data = Order::where('id', $id)->where('client_id', $user->id)->first();
            if (!$data) {
                return redirect()->route('home')->withErrors(__('message.demo_permission_denied'));
            }
        } elseif ($user->user_type == 'admin' || $user->hasRole(['admin', 'demo_admin'])) {
            $data = Order::withTrashed()->with('client')->findOrFail($id);
        } else {
            $data = Order::withTrashed()->findOrFail($id);
        }

        $complate_data = Order::withTrashed()->where('parent_order_id', $data->id)->first();

        $customerSupport = CustomerSupport::where('order_id', $data->id)->get();
        $orderChatSupport = CustomerSupport::where('order_id', $data->id)
            ->whereIn('status', ['pending', 'inreview', 'open'])
            ->latest('id')
            ->first();

        $courierCompany = $data->couriercompany ?? null;
        $trackingId = ($courierCompany && strpos($courierCompany->link, '=') !== false) ? trim(explode('=', $courierCompany->link)[1]) : null;

        if ((int) $data->is_photo_order === 1) {
            app(PhotoOrderDispatchService::class)->sync($data);
            $data->refresh();
        }

            $profpicture = Profofpictures::where('order_id', $data->id)->get();
            $mediaItems = [ 'prof_file' => [] ];
            if ($profpicture->isNotEmpty()) {
                foreach ($profpicture as $picture) {
                    $mediaItems['prof_file'] = array_merge($mediaItems['prof_file'], $picture->getMedia('prof_file')->all());
                }
            }

            $photoOrderImages = collect();
            if ((int) $data->is_photo_order === 1) {
                foreach ($mediaItems['prof_file'] as $file) {
                    if (str_starts_with((string) $file->mime_type, 'image/')) {
                        $photoOrderImages->push($file);
                    }
                }
                foreach ($data->getMedia('order_photo') as $file) {
                    if (str_starts_with((string) $file->mime_type, 'image/')) {
                        $photoOrderImages->push($file);
                    }
                }
            }
        return view('order.show', compact('id', 'data', 'pageTitle', 'complate_data', 'courierCompany', 'trackingId', 'is_vehicle_in_order','mediaItems','customerSupport', 'photoOrderImages', 'orderChatSupport'));
    }

    public function updatePhotoOrderDetails(Request $request, $id)
    {
        if (auth()->user()->user_type !== 'admin' && !auth()->user()->hasRole(['admin', 'demo_admin']) && !auth()->user()->can('order-edit')) {
            return redirect()->back()->withErrors(__('message.demo_permission_denied'));
        }

        $order = Order::findOrFail($id);
        if ((int) $order->is_photo_order !== 1) {
            return redirect()->route('order.show', $id)->withErrors(__('message.not_found_entry', ['name' => __('message.photo_order')]));
        }

        $request->validate([
            'pickup_point.address' => 'required|string|max:500',
            'pickup_point.contact_number' => 'required|string|max:30',
            'pickup_point.name' => 'required|string|max:255',
            'delivery_point.address' => 'required|string|max:500',
            'delivery_point.contact_number' => 'required|string|max:30',
            'delivery_point.name' => 'required|string|max:255',
        ]);

        $pickup = array_merge($order->pickup_point ?? [], $request->input('pickup_point', []));
        $delivery = array_merge($order->delivery_point ?? [], $request->input('delivery_point', []));

        $order->pickup_point = $pickup;
        $order->delivery_point = $delivery;
        $order->save();

        app(PhotoOrderDispatchService::class)->sync($order->fresh());

        $message = __('message.update_form', ['form' => __('message.photo_order')]);

        return redirect()->route('order.show', $id)->withSuccess($message);
    }

    public function createReturnOrder(Request $request, $id)
    {
        if (auth()->user()->user_type !== 'admin') {
            return redirect()->back()->withErrors(__('message.demo_permission_denied'));
        }

        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $parent = Order::findOrFail($id);

        if ($parent->status !== 'completed') {
            return redirect()->back()->withErrors(__('message.order_must_be_completed_for_return'));
        }

        if (Order::where('parent_order_id', $parent->id)->exists()) {
            return redirect()->back()->withErrors(__('message.return_order_already_exists'));
        }

        $returnData = $parent->only([
            'client_id', 'country_id', 'city_id', 'parcel_type', 'total_weight', 'total_parcel',
            'vehicle_id', 'currency', 'payment_collect_from', 'packaging_symbols', 'description',
            'payment_type', 'fixed_charges', 'total_amount', 'distance_charge', 'weight_charge',
        ]);
        $returnData['parent_order_id'] = $parent->id;
        $returnData['pickup_point'] = $parent->delivery_point;
        $returnData['delivery_point'] = $parent->pickup_point;
        $returnData['status'] = 'create';
        $returnData['reason'] = $request->reason;
        $returnData['milisecond'] = strtoupper(appSettingcurrency('prefix')) . round(microtime(true) * 1000);

        $returnOrder = Order::create($returnData);

        \App\Models\OrderHistory::create([
            'order_id' => $returnOrder->id,
            'history_type' => 'return',
            'history_message' => __('message.return_order'),
            'parent_order_id' => $parent->id,
        ]);

        return redirect()->route('order.show', $returnOrder->id)->withSuccess(__('message.return_order'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $user  = auth()->user();
        $pageTitle = __('message.update_form_title', ['form' => __('message.order')]);
        $data = order::findOrFail($id);
        if (auth()->user()) {
            if ($data->client_id === auth()->id() && $data->status === 'draft') {
            } else {
                $message = __('message.demo_permission_denied');
                return redirect()->back()->withErrors($message);
            }
        } else {
            return redirect()->back();
        }
        $assets = ['phone', 'contact_nbr', 'location'];
        $staticData = StaticData::get();

        return view('order.form', compact('data', 'pageTitle', 'id', 'staticData', 'assets'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(OrderRequest $request, $id)
    {
        $order = Order::findOrFail($id);

        if ($request->is('api/*') && $request->input('status') === 'cancelled') {
            $user = auth()->user();
            if ($user && $user->user_type === 'client' && ($order->status ?? '') !== 'pickup_error') {
                return json_custom_response([
                    'status' => false,
                    'message' => __('message.order_cancel_admin_only'),
                ], 403);
            }
        }

        if ($request->is('api/*') && $request->input('status') === 'pickup_error') {
            $user = auth()->user();
            if (! $user || $user->user_type !== 'delivery_man') {
                return json_custom_response([
                    'status' => false,
                    'message' => __('message.order_not_editable'),
                ], 403);
            }
            if (! in_array($order->status, ['courier_assigned', 'courier_arrived', 'active'], true)) {
                return json_custom_response([
                    'status' => false,
                    'message' => __('message.order_not_editable'),
                ], 422);
            }
            if (! $request->filled('reason')) {
                return json_custom_response([
                    'status' => false,
                    'message' => __('message.pickup_error_reason_required'),
                ], 422);
            }
        }

        // Client / shared: restore Pick Up Cancelled → Order List or Pre Pick Up.
        if ($request->is('api/*') && $request->input('pickup_error_choice') === 'restore') {
            $user = auth()->user();
            if (! $user || ! in_array($user->user_type, ['client', 'admin', 'demo_admin'], true)) {
                return json_custom_response([
                    'status' => false,
                    'message' => __('message.order_not_editable'),
                ], 403);
            }
            if ($user->user_type === 'client' && (int) $order->client_id !== (int) $user->id) {
                return json_custom_response([
                    'status' => false,
                    'message' => __('message.order_not_editable'),
                ], 403);
            }

            try {
                $order = app(\App\Services\DispatchOrderWorkflowService::class)
                    ->restorePickupCancelledOrder($order);
            } catch (\InvalidArgumentException $e) {
                return json_custom_response([
                    'status' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            try {
                app(\App\Services\DispatchOrderAuditService::class)->logRestored($order, $user);
            } catch (\Throwable $e) {
                \Log::warning('dispatch audit failed after pickup cancelled restore', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return json_custom_response([
                'message' => __('message.pickup_cancelled_restored'),
                'order_id' => $order->id,
                'status' => $order->status,
                'pickup_datetime' => $order->pickup_datetime,
            ]);
        }

        // Client pickup-error choices: cancel | express | next_day | reorder
        if ($request->is('api/*') && $request->filled('pickup_error_choice')) {
            $user = auth()->user();
            if (! $user || $user->user_type !== 'client') {
                return json_custom_response([
                    'status' => false,
                    'message' => __('message.order_not_editable'),
                ], 403);
            }
            if (($order->status ?? '') !== 'pickup_error') {
                return json_custom_response([
                    'status' => false,
                    'message' => __('message.order_not_editable'),
                ], 422);
            }

            $choice = (string) $request->input('pickup_error_choice');
            $window = pickupErrorClientWindow($order);
            $allowedKeys = collect($window['options'] ?? [])->pluck('key')->all();
            if (! in_array($choice, $allowedKeys, true)) {
                return json_custom_response([
                    'status' => false,
                    'message' => __('message.order_not_editable'),
                ], 422);
            }

            if ($choice === 'cancel') {
                // Leave Pick Up Error → Pick Up Cancelled.
                if (empty($order->pickup_error_at)) {
                    $order->forceFill(['pickup_error_at' => now()])->save();
                }

                \App\Models\DispatchOrderItem::where('order_id', $order->id)
                    ->whereIn('status', ['assigned', 'courier_assigned', 'courier_departed'])
                    ->update([
                        'status' => 'collected',
                        'delivery_man_id' => null,
                        'assigned_at' => null,
                    ]);

                $order->forceFill([
                    'status' => 'cancelled',
                    'pickup_error_choice' => 'cancel',
                    'pickup_error_choice_at' => now(),
                    'delivery_man_id' => null,
                    'assign_datetime' => null,
                    'reason' => $order->reason ?: 'User cancelled after pickup error',
                ])->save();

                $order = $order->fresh();

                try {
                    app(DispatchOrderAuditService::class)->logUserChoice($order, 'cancel', $user);
                } catch (\Throwable $e) {
                    \Log::warning('dispatch audit failed after user cancel choice', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                return json_custom_response([
                    'message' => __('message.update_form', ['form' => __('message.order')]),
                    'order_id' => $order->id,
                    'status' => $order->status,
                    'pickup_error' => pickupErrorClientWindow($order),
                ]);
            } elseif ($choice === 'express') {
                // Leave Pick Up Error → Pick Up Cancelled (status cancelled).
                // User App still gets အမြန် SERVICE box via pickup_error window.
                if (empty($order->pickup_error_at)) {
                    $order->forceFill(['pickup_error_at' => now()])->save();
                }

                \App\Models\DispatchOrderItem::where('order_id', $order->id)
                    ->whereIn('status', ['assigned', 'courier_assigned', 'courier_departed'])
                    ->update([
                        'status' => 'collected',
                        'delivery_man_id' => null,
                        'assigned_at' => null,
                    ]);

                $order->forceFill([
                    'status' => 'cancelled',
                    'pickup_error_choice' => 'express',
                    'pickup_error_choice_at' => now(),
                    'delivery_man_id' => null,
                    'assign_datetime' => null,
                    'reason' => $order->reason ?: 'User chose express after pickup error',
                ])->save();

                $order = $order->fresh();

                try {
                    app(DispatchOrderAuditService::class)->logUserChoice($order, 'express', $user);
                } catch (\Throwable $e) {
                    \Log::warning('dispatch audit failed after user express choice', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                return json_custom_response([
                    'message' => __('message.update_form', ['form' => __('message.order')]),
                    'order_id' => $order->id,
                    'status' => $order->status,
                    'pickup_error' => pickupErrorClientWindow($order),
                ]);
            } elseif ($choice === 'reorder') {
                // Same order returns as Created for Admin Order List / re-assign.
                $order->forceFill([
                    'status' => 'create',
                    'delivery_man_id' => null,
                    'assign_datetime' => null,
                    'pickup_error_choice' => 'reorder',
                    'pickup_error_choice_at' => now(),
                    'pickup_error_at' => null,
                ])->save();

                // Reset pickup-stage dispatch items so the order reappears in the normal list.
                \App\Models\DispatchOrderItem::where('order_id', $order->id)
                    ->whereIn('status', ['assigned', 'courier_assigned', 'courier_departed'])
                    ->update([
                        'status' => 'collected',
                        'delivery_man_id' => null,
                        'assigned_at' => null,
                    ]);

                $order = $order->fresh();
                try {
                    app(DispatchOrderAuditService::class)->logUserChoice($order, 'reorder', $user);
                } catch (\Throwable $e) {
                    \Log::warning('dispatch audit failed after user reorder choice', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }
                try {
                    saveOrderHistory([
                        'history_type' => 'create',
                        'order_id' => $order->id,
                        'order' => $order,
                    ]);
                } catch (\Throwable $e) {
                    \Log::warning('saveOrderHistory failed after pickup_error reorder', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                return json_custom_response([
                    'message' => __('message.update_form', ['form' => __('message.order')]),
                    'order_id' => $order->id,
                    'status' => $order->status,
                ]);
            } elseif ($choice === 'next_day') {
                // Leave Pick Up Error → Created + Pre Pick Up (next Yangon day).
                $order->forceFill([
                    'status' => 'create',
                    'pickup_datetime' => nextDayOrderReceivedDatetime(),
                    'pickup_error_choice' => 'next_day',
                    'pickup_error_choice_at' => now(),
                    'pickup_error_at' => null,
                    'delivery_man_id' => null,
                    'assign_datetime' => null,
                ])->save();

                \App\Models\DispatchOrderItem::where('order_id', $order->id)
                    ->whereIn('status', ['assigned', 'courier_assigned', 'courier_departed'])
                    ->update([
                        'status' => 'collected',
                        'delivery_man_id' => null,
                        'assigned_at' => null,
                    ]);

                $order = $order->fresh();
                try {
                    app(DispatchOrderAuditService::class)->logUserChoice($order, 'next_day', $user);
                } catch (\Throwable $e) {
                    \Log::warning('dispatch audit failed after user next_day choice', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }
                try {
                    saveOrderHistory([
                        'history_type' => 'create',
                        'order_id' => $order->id,
                        'order' => $order,
                    ]);
                } catch (\Throwable $e) {
                    \Log::warning('saveOrderHistory failed after pickup_error next_day', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                return json_custom_response([
                    'message' => __('message.update_form', ['form' => __('message.order')]),
                    'order_id' => $order->id,
                    'status' => $order->status,
                    'pickup_datetime' => $order->pickup_datetime,
                    'pickup_error' => pickupErrorClientWindow($order),
                ]);
            } else {
                return json_custom_response([
                    'status' => false,
                    'message' => __('message.order_not_editable'),
                ], 422);
            }
        }

        if ($request->is('api/*') && $request->input('status') === 'courier_picked_up') {
            $pickupService = app(\App\Services\PickupParcelDispatchService::class);
            if ($pickupService->isDispatchPickupOrder($order)) {
                $pickupService->ensureDispatchItems($order);
                $order = $order->fresh();
            }
            if ($pickupService->isDispatchPickupOrder($order) && ! $pickupService->allCollectedItemsHaveSize($order)) {
                return json_custom_response([
                    'status' => false,
                    'message' => __('message.pickup_item_size_required'),
                ], 422);
            }
            if ($pickupService->isDispatchPickupOrder($order) && ! $pickupService->allCollectedItemsHavePickupPhotos($order)) {
                $required = $pickupService->requiredPickupPhotoCount($order);

                return json_custom_response([
                    'status' => false,
                    'message' => __('message.pickup_parcel_photos_required', ['count' => $required]),
                ], 422);
            }
        }

        try {
            DB::beginTransaction();

            $incomingStatus = $request->input('status');
            $order->fill($request->all())->update();

            if ($incomingStatus === 'pickup_error') {
                $order->forceFill([
                    'pickup_error_at' => $order->pickup_error_at ?: now(),
                    'pickup_error_choice' => null,
                    'pickup_error_choice_at' => null,
                ])->save();
            }

            $payment = Payment::where('order_id', $id)->first();

            if ($payment != null && $payment->payment_status == 'paid' && $order->status == 'completed') {
                $this->walletTransactionCompleted($order->id);
            }
            if ($order->status == 'cancelled') {
                $this->walletTransactionCancelled($order->id);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return json_custom_response($e);
        }

        uploadMediaFile($order, $request->pickup_time_signature, 'pickup_time_signature');
        uploadMediaFile($order, $request->delivery_time_signature, 'delivery_time_signature');
        $message = __('message.update_form', ['form' => __('message.order')]);


        $status = $order->status;
        $allowedStatuses = [
            'active',
            'delayed',
            'cancelled',
            'failed',
            'pickup_error',
            'courier_picked_up',
            'courier_arrived',
            'completed',
            'courier_departed',
        ];

        if (in_array($status, $allowedStatuses)) {
            $history_data = [
                'history_type' => $status,
                'order_id'     => $id,
                'order'        => $order,
            ];

            try {
                saveOrderHistory($history_data);
            } catch (\Throwable $e) {
                // Order status is already persisted; do not fail the API (e.g. Firebase misconfig).
                \Log::warning('saveOrderHistory failed after order update', [
                    'order_id' => $id,
                    'status' => $status,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($request->is('api/*') && auth()->check()) {
            app(DispatchOrderWorkflowService::class)->syncItemStatusFromDeliveryApp(
                (int) $order->id,
                (int) auth()->id(),
                (string) $status
            );
        }

        // When rider completes pick-up, move to Assign 100 if Admin is already Done.
        if ($status === 'courier_picked_up') {
            $order = $order->fresh(['delivery_man']);
            app(DispatchOrderAuditService::class)->logPickupCompleted($order);
            app(DispatchOrderWorkflowService::class)->syncOrderWorkflow($order);
        }

        // if ($status == 'active') {
        //     $deliveryManId = auth()->id();

        //     $vehicleHistory = DeliverymanVehicleHistory::where('delivery_man_id', $deliveryManId)->where('is_active', 1)->first();

        //     if ($vehicleHistory) {
        //         $vehicleInfo = json_encode($vehicleHistory->vehicle_info, true);

        //         $orderVehicleData = [
        //             'order_id' => $id,
        //             'delivery_man_id' => $deliveryManId,
        //             'vehicle_info' => $vehicleInfo,
        //         ];

        //         OrderVehicleHistory::create($orderVehicleData);

        //         return response()->json(['message' => 'Data updated successfully.'], 200);
        //     } else {
        //         return response()->json(['error' => 'No vehicle info found for the delivery man.'], 404);
        //     }
        // }
        if ($request->is('api/*')) {
            return json_message_response($message);
        }
        return redirect()->route('draft-order')->with($message);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if(auth()->user()->user_type == 'admin'){
            if (!auth()->user()->can('order-delete')) {
                $message = __('message.demo_permission_denied');
                return response()->json(['status' => true, 'message' => $message]);
            }
        }

        if (env('APP_DEMO') && auth()->user()->hasRole('admin')) {
            $message = __('message.demo_permission_denied');
            if (request()->is('api/*')) {
                return response()->json(['status' => true, 'message' => $message]);
            }
            if (request()->ajax()) {
                return response()->json(['status' => false, 'message' => $message, 'event' => 'validation']);
            }
            return redirect()->route('order.index')->withErrors($message);
        }
        $order = order::find($id);
        $status = 'error';
        $message = __('message.not_found_entry', ['name' => __('message.order')]);

        if ($order != '') {
            $order->delete();
            $status = 'success';
            $message = __('message.delete_form', ['form' => __('message.order')]);
        }

        if (request()->is('api/*')) {
            return response()->json(['status' => true, 'message' => $message]);
        }
        if (request()->ajax()) {
            return response()->json(['status' => true, 'message' => $message]);
        }

        return redirect()->back()->with($status, $message);
    }

    public function action(Request $request)
    {
        $id = $request->id;
        $order = Order::withTrashed()->where('id', $id)->first();

        $message = __('message.not_found_entry', ['name' => __('message.order')]);
        if ($request->type === 'restore') {
            $order->restore();
            $message = __('message.msg_restored', ['name' => __('message.order')]);
        }

        if ($request->type === 'forcedelete') {
            if (env('APP_DEMO')) {
                $message = __('message.demo_permission_denied');
                if (request()->is('api/*')) {
                    return response()->json(['status' => true, 'message' => $message]);
                }
                if (request()->ajax()) {
                    return response()->json(['status' => false, 'message' => $message, 'event' => 'validation']);
                }
                return redirect()->route('order.index')->withErrors($message);
            }
            $order->forceDelete();
            $search = "id" . '":' . $id;
            Notification::where('data', 'like', "%{$search}%")->delete();
            $document_name = 'order_' . $id;
            app('firebase.firestore')->database()->collection('delivery_man')->document($document_name)->delete();
            $message = __('message.msg_forcedelete', ['name' => __('message.order')]);
        }

        if ($request->type == 'courier_assigned') {
            if ($order->delivery_man_id != null) {
                $message = __('message.couriertransfer');
                $history_type = 'courier_transfer';
                $data['assign_datetime'] = now();
            } else {
                $message = __('message.courierassigned');
                $history_type = 'courier_assigned';
                $data['assign_datetime'] = now();
            }

            Order::updateOrCreate(['id' => $request->id], $data);
            $order->update(['delivery_man_id' => $request->delivery_man_id, 'status' => $request->status]);
            $history_data = [
                'history_type' => $history_type,
                'order_id' => $id,
                'order' => $order,
            ];

            saveOrderHistory($history_data);
        }

        if ($request->type == 'courier_departed') {
            $order->update(['status' => $request->status]);
            $history_data = [
                'history_type' => 'courier_departed',
                'order_id' => $id,
                'order' => $order,
            ];

            saveOrderHistory($history_data);
        }

        if ($request->type == 'completed') {
            $order->update(['status' => $request->type]);
            $history_data = [
                'history_type' => 'completed',
                'order_id' => $id,
                'order' => $order,
            ];

            saveOrderHistory($history_data);
        }

        if (request()->is('api/*')) {
            return response()->json(['status' => true, 'message' => $message]);
        }
            return redirect()->route('order.index')->withSuccess($message);
    }

    public function InvoicePdf($id)
    {
        $order = Order::find($id);
        $today = Carbon::now()->format('d/m/Y');

        $companyName = Setting::where('type', 'order_invoice')->where('key', 'company_name')->first();
        $companynumber = Setting::where('type', 'order_invoice')->where('key', 'company_contact_number')->first();
        $companyAddress = Setting::where('type', 'order_invoice')->where('key', 'company_address')->first();
        $invoice = Setting::where('type', 'order_invoice')->where('key', 'company_logo')->first();
        $pdf = Pdf::loadView('order.invoice', compact('invoice', 'companyName', 'companyAddress', 'companynumber', 'order', 'today'), []);
        return $pdf->download('invoice_' . $order->id . '.pdf');
    }

    public function ApiInvoicePdf($id)
    {
        $order = Order::find($id);
        $today = Carbon::now()->format('d/m/Y');

        $companyName = Setting::where('type', 'order_invoice')->where('key', 'company_name')->first();
        $companynumber = Setting::where('type', 'order_invoice')->where('key', 'company_contact_number')->first();
        $companyAddress = Setting::where('type', 'order_invoice')->where('key', 'company_address')->first();
        $invoice = Setting::where('type', 'order_invoice')->where('key', 'company_logo')->first();
        $pdf = Pdf::loadView('order.invoice', compact('invoice', 'companyName', 'companyAddress', 'companynumber', 'order', 'today'), []);
        return $pdf->stream('invoice_' . $order->id . '.pdf');
    }

    public function assign($id)
    {
        $order = Order::find($id);
        $deliveryMenQuery = User::where('city_id', $order->city_id)
            ->where('status', 1)
            ->where('user_type', 'delivery_man')
            ->availableForAssign()
            ->where(function ($query) {
                $query->whereNotNull('email_verified_at')
                    ->whereNotNull('otp_verify_at')
                    ->whereNotNull('document_verified_at');
            });
        $deliveryMen = $deliveryMenQuery->get();

        $pageTitle = __('message.assign_order');
        return view('order.assgin', compact('pageTitle', 'deliveryMen', 'id', 'order'));
    }
    public function filterOrder()
    {
        $pageTitle = __('message.order_filter');
        $params = null;

        $params = [
            'status' => request('status') ?? null,
            'from_date' => request('from_date') ?? null,
            'to_date' => request('to_date') ?? null,
            'created_at' => request('created_at') ?? null,
            'city_id' => request('city_id') ?? null,
            'country_id' => request('country_id') ?? null,
        ];
        if (!isset($params['city_id'])) {
            $params['city_id'] = null;
        }
        if (!isset($params['country_id'])) {
            $params['country_id'] = null;
        }
        $selectedCityId = request('city_id');
        $cities = City::pluck('name', 'id')->prepend(__('message.select_name', ['select' => __('message.city')]), '')->toArray();
        $selectedCountryId = request('country_id');
        $country = Country::pluck('name', 'id')->prepend(__('message.select_name', ['select' => __('message.country')]), '')->toArray();

        return view('global.order-datatable', compact('pageTitle', 'params', 'selectedCityId', 'cities', 'selectedCountryId', 'country'));
    }
    public function draftOrder(Request $request)
    {
        $pageTitle = __('message.add_form_title', ['form' => __('message.order')]);

        $client = Auth::user();

        $orderquery = Order::where('client_id', $client->id)
            ->where(function ($query) use ($client, $request) {
                $query->where('city_id', $client->city_id)
                    ->orWhere('city_id', $request->city_id);
            })
            ->where(function ($query) use ($client, $request) {
                $query->where('country_id', $client->country_id)
                    ->orWhere('country_id', $request->country_id);
            })
            ->where('status', 'draft')->orderBy('created_at', 'asc');

        if ($request->filled('city_id')) {
            $orderquery->whereHas('city', function ($query) use ($request) {
                $query->where('id', $request->input('city_id'));
            });
        }

        if ($request->filled('country_id')) {
            $orderquery->whereHas('country', function ($query) use ($request) {
                $query->where('id', $request->input('country_id'));
            });
        }

        $orders = $orderquery->get();

        $cityId = $client->city_id;
        $countryId = $client->country_id;

        if ($request->filled('city_id')) {
            $cityId = $request->input('city_id');
        }

        if ($request->filled('country_id')) {
            $countryId = $request->input('country_id');
        }
        if ($cityId != null && $countryId != null) {
            $selectedCity = City::where('id', $cityId)->pluck('name', 'id');
            $selectedCountry = Country::where('id', $countryId)->pluck('name', 'id');
        } else {
            $selectedCity = City::pluck('name', 'id')->prepend('Select City', '');
            $selectedCountry = Country::pluck('name', 'id')->prepend('Select Country', '');
        }

        return view('clientside.draftorder', compact('pageTitle', 'orders', 'selectedCity', 'selectedCountry'));
    }

    public function multipleLabel(Request $request)
    {
        $ids = $request->input('print_checked_ids');

        $orders = Order::whereIn('id', $ids)->get();

        // Fetch company info
        $companyName = Setting::where('type', 'order_invoice')->where('key', 'company_name')->first();
        $companynumber = Setting::where('type', 'order_invoice')->where('key', 'company_contact_number')->first();
        $companyAddress = Setting::where('type', 'order_invoice')->where('key', 'company_address')->first();
        $invoice = Setting::where('type', 'order_invoice')->where('key', 'company_logo')->first();
        $labelnumber = SettingData('mobile_number_allow', 'mobile_number_allow');

        $barcodeBase64 = [];
        $generator = new BarcodeGeneratorPNG();
        foreach ($orders as $order) {
            $barcode = $generator->getBarcode($order->milisecond, $generator::TYPE_CODE_128);
            $barcodeBase64[$order->id] = base64_encode($barcode);
        }

        return view('order.multiplelabel', compact('orders', 'barcodeBase64', 'companyName', 'companynumber', 'companyAddress', 'invoice','labelnumber'));
    }

    public function getOrderDetails($id)
    {
        $order = Order::findOrFail($id);
        $generator = new BarcodeGeneratorPNG();
        $barcode = $generator->getBarcode($order->milisecond, $generator::TYPE_CODE_128);
        $barcodeBase64 = base64_encode($barcode);
        $companyName = Setting::where('type', 'order_invoice')->where('key', 'company_name')->first();
        $companynumber = Setting::where('type', 'order_invoice')->where('key', 'company_contact_number')->first();
        $companyAddress = Setting::where('type', 'order_invoice')->where('key', 'company_address')->first();
        $invoice = Setting::where('type', 'order_invoice')->where('key', 'company_logo')->first();
        $labelnumber = SettingData('mobile_number_allow', 'mobile_number_allow');

        return compact('order', 'barcodeBase64', 'companyName', 'companynumber', 'companyAddress', 'invoice', 'id','labelnumber');
    }

    public function labelprint($id)
    {
        $data = $this->getOrderDetails($id);
        return view('order.label', $data);
    }

    public function printorder($id)
    {
        $data = $this->getOrderDetails($id);
        return view('order.print', $data);
    }
    public function printOrderMultiple(Request $request)
    {
        $ids = $request->input('print_checked_ids');

        $orders = Order::whereIn('id', $ids)->get();

        $companyName = Setting::where('type', 'order_invoice')->where('key', 'company_name')->first();
        $companyNumber = Setting::where('type', 'order_invoice')->where('key', 'company_contact_number')->first();
        $labelnumber = SettingData('mobile_number_allow','mobile_number_allow');
        $barcodeBase64 = [];

        foreach ($orders as $order) {
            $generator = new BarcodeGeneratorPNG();
            $barcode = $generator->getBarcode($order->milisecond, $generator::TYPE_CODE_128);
            $barcodeBase64[$order->id] = base64_encode($barcode);
        }
        return view('order.multipleprint', compact('orders', 'companyName', 'companyNumber', 'barcodeBase64','labelnumber'));
    }
    public function getOrderDetailsQrcode($id)
    {
        $order = Order::findOrFail($id);
        $generator = new BarcodeGeneratorPNG();
        $barcode = $generator->getBarcode($order->milisecond, $generator::TYPE_CODE_128);

        $barcodeBase64 = base64_encode($barcode);
        $companyName = Setting::where('type', 'order_invoice')->where('key', 'company_name')->first();
        $companynumber = Setting::where('type', 'order_invoice')->where('key', 'company_contact_number')->first();
        $invoice = Setting::where('type', 'order_invoice')->where('key', 'company_logo')->first();
        $labelnumber = SettingData('mobile_number_allow', 'mobile_number_allow');

        return compact('order', 'barcodeBase64', 'companyName', 'companynumber', 'id', 'invoice', 'labelnumber');
    }
    public function printbarcode($id)
    {
        $data = $this->getOrderDetailsQrcode($id);
        return view('order.printbarcode', $data);
    }
    public function printorderqrSingal($id)
    {
        Order::find($id);

        $data = $this->getOrderDetailsQrcode($id);
        return view('order.qrcode', $data);
    }

    public function updateCourierCompany(Request $request, $id)
    {
        $setting = SettingData('order_mail', 'order_shipped_mail');
        $order = Order::findOrFail($id);

        $order->couriercompany_id = $request->input('couriercompany_id');
        $order->is_shipped = $request->has('is_shipped') ? 1 : 0;
        $order->shipped_verify_at = $request->input('shipped_verify_at', $request->date_shipped);
        $status = $request->input('status');
        if ($status === 'courier_departed' || $status === 'courier_picked_up') {
            $order->status = 'shipped';
        }
        $order->save();
        $emailData = OrderMail::where('type', $order->status)->first();
        if ($setting == 1) {
            $dynamicData = [
                '[order ID]' => $order->id,
                '[status]' => $order->status,
                '[Company name]' => config('app.name'),
            ];

            $email = $order->client_id ? $order->client->email : null;
            if ($email) {
                $mailDescription = str_replace(array_keys($dynamicData), array_values($dynamicData), $emailData->mail_description);
                Mail::to($email)->send(new sendmail($emailData->subject, $mailDescription, [
                    'order_id' => $order->id,
                    'status' => $order->status,
                    'company_name' => config('app.name'),
                ]));
            }
        }

        $trackingId = $request->input('tracking_id');
        $courierCompanyId = $request->input('couriercompany_id');
        $trackingDetails = $request->input('tracking_details');
        $trackingNumber = $request->input('tracking_number');
        $shippingProvider = $request->input('shipping_provider');
        $dateShipped = $request->input('date_shipped');

        $courierCompany = CourierCompanies::find($courierCompanyId);
        if ($courierCompany) {
            $linkParts = explode('=', $courierCompany->link);
            $courierCompany->link = $linkParts[0] . '=' . $trackingId;

            $courierCompany->tracking_details = $trackingDetails;
            $courierCompany->tracking_number = $trackingNumber;
            $courierCompany->shipping_provider = $shippingProvider;
            $courierCompany->date_shipped = $dateShipped;
            $courierCompany->save();

            if ($order) {
                $data['order_id'] = $order->id;
                $data['history_type'] = 'shipped_order';
                $data['history_message'] = __('message.order_has_been_shipped');
                $data['history_message'] = __('message.order_has_been_shipped_via_tracking', [
                    'courier_company' => $courierCompany->name,
                    'tracking_id' => $trackingId,
                ]);
                $data['datetime'] = now();
                OrderHistory::create($data);
            }
            $notification_data = [
                'id'   => $order->id,
                'type'      => __('message.shipped_order'),
                'subject'     => __('message.shipped_order', ['id' => $order->id]),
                'message' => $data['history_message'] ?? null,
            ];
            $admins = User::admin()->get();
            foreach ($admins as $admin) {
                $admin->notify(new CustomerSupportNotification($notification_data));
            }
            $message = __('message.shipped_order_add');
            if ($request->is('api/*')) {
                $order->is_shipped = 1;
                $order->save();
                return json_message_response($message);
            }
        } else {
            return redirect()->back()->with('error', __('message.not_found_entry', ['name' => __('message.courier_companies')]));
        }

        return redirect()->back()->with('success', __('message.courier_companies_updated'));
    }

    public function bulkorderdata()
    {
        $auth_user = authSession();
        if (!auth()->user()->can('bulkimport-list')) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }
        $pageTitle = __('message.bulk_import_order_data');

        return view('order.bulkimport', compact(['pageTitle']));
    }

    public function importorderdata(Request $request)
    {
        Excel::import(new ImportOrderdata, $request->file('order_data')->store('files'));
        $message = __('message.save_form', ['form' => __('message.order_data')]);
        return redirect()->route('bulk.order.data')->withSuccess($message);
    }

    public function orderhelp()
    {
        $pageTitle = __('message.order_data_bulk_upload_fields');
        return view('order.help', compact(['pageTitle']));
    }

    public function orderdownloadtemplate()
    {
        $pageTitle = __('message.download_template');
        return view('order.downloadtemplate', compact(['pageTitle']));
    }

    public function ordertemplateExcel()
    {
        $file = public_path("exportorder.xlsx");
        return response()->download($file);
    }

    public function isReschedule(Request $request)
    {
        $data = $request->all();

        $order = Order::find($data['order_id']);
        if ($order == null) {
            return json_message_response(__('message.not_found_entry', ['name' => __('message.order')]), 400);
        }
        if ($order) {
            $order->status = 'reschedule';
            $order->save();
        }
        Reschedule::create([
            'date' => now(),
            'order_id' => $order->id,
            'reason' => $data['reason'],
        ]);

        if ($order) {
            $order->rescheduledatetime =  $data['date'];
            $order->save();
            $emailData = OrderMail::where('type', 'reschedule')->first();
            if ($order->rescheduledatetime) {
                $setting = SettingData('order_mail', 'order_reschedule_mail');
                if ($setting == 1) {
                    $dynamicData = [
                        '[order ID]' => $order->id,
                        '[status]' => 'reschedule',
                        '[Company name]' => config('app.name'),
                    ];

                    $email = $order->client_id ? $order->client->email : null;
                    if ($email) {
                        $mailDescription = str_replace(array_keys($dynamicData), array_values($dynamicData), $emailData->mail_description);
                        Mail::to($email)->send(new sendmail($emailData->subject, $mailDescription, [
                            'order_id' => $order->id,
                            'status' => 'reschedule',
                            'company_name' => config('app.name'),
                        ]));
                    }
                }
            }
        }
       if ($order) {
            $history_data = [
                'history_type' => $order->status,
                'order_id' => $order->id,
                'order' => $order,
            ];
            saveOrderHistory($history_data);
        }
        $message = __('message.order_reschedule_succesfully');

        if ($request->is('api/*')) {
            return response()->json(['status' => true, 'message' => $message]);
        }
        return redirect()->back()->with('success', $message);
    }
    public function shippedOrder(ShippedOrderDataTable $datatable)
    {
        $pageTitle = __('message.list_form_title', ['form' => __('message.shipped_order')]);
        $auth_user = authSession();
        $assets = ['datatable'];
        $params = null;
        $multi_checkbox_delete = $auth_user->can('order-delete') ? '<button id="deleteSelectedBtn" checked-title = "order-checked " class="float-left btn btn-sm ">' . __('message.delete_selected') . '</button>' : '';
        return $datatable->render('global.order-filter', compact('pageTitle', 'auth_user', 'params', 'multi_checkbox_delete'));
    }

    public function deliveryManVehiclehistory(VehicleHistoryRequest $request)
    {
        $json_data = $request->all();
        unset($json_data['vehicle_history_image']);
        $data = json_encode($json_data, true);
        $user = auth()->user();
        $userdata = User::find($user->id);
        $userdata->vehicle_id = $request->vehicle_id;
        $userdata->save();

        $currentDatetime = now();

        $activeRecord = DeliverymanVehicleHistory::where('delivery_man_id', $user->id)->where('is_active', 1)->first();

        if ($activeRecord) {
            $activeRecord->update([
                'is_active' => 0,
                'end_datetime' => $currentDatetime,
            ]);
        }

        $vehicle_data = [
            'delivery_man_id' => $user->id,
            'start_datetime' => $currentDatetime,
            'end_datetime' => null,
            'is_active' => 1,
            'vehicle_info' => $data,
        ];
        $user = auth()->user();
        if($user->hasRole('delivery_man')){
        $model = DeliverymanVehicleHistory::create($vehicle_data);
        }

        if ($request->hasFile('vehicle_history_image')) {
            $model->clearMediaCollection('vehicle_history_image');

            foreach ($request->file('vehicle_history_image') as $image) {
                $model->addMedia($image)->toMediaCollection('vehicle_history_image');
            }
        }
        $message = __('message.deliveryman_vehicle_history');

        $item = new DeliverymanVehicleHistoryResource($model);
        $response = [ 'message' => $message, 'data' => $item ];

        return json_custom_response($response);
    }
    public function clientOrderdatatable(ClientOrderDataTable $datatable)
    {
        $pageTitle = __('message.list_form_title', ['form' => __('message.order')]);
        $auth_user = authSession();
        $assets = ['datatable'];
        $params = null;
        $params = [
            'status' => request('status') ?? null,
            'from_date' => request('from_date') ?? null,
            'to_date' => request('to_date') ?? null,
            'created_at' => request('created_at') ?? null,
            'city_id' => request('city_id') ?? null,
            'country_id' => request('country_id') ?? null,
        ];

        $filter_file_button = '<a href="' . route('filter.order.data', $params) . '" class=" mr-1 mt-1 btn btn-sm btn-success  text-dark loadRemoteModel"><i class="fas fa-filter"></i> ' . __('message.filter') . '</a>';
        $reset_file_button = '<a href="' . route('order.index') . '" class="float-right mr-1 mt-0 mb-1 btn btn-sm btn-info text-dark mt-1 pt-1 pb-1"><i class="ri-repeat-line" style="font-size:12px"></i> ' . __('message.reset_filter') . '</a>';
        $multi_checkbox_delete = $auth_user->can('order-delete') ? '<button id="deleteSelectedBtn" checked-title = "order-checked " class="float-left btn btn-sm ">' . __('message.delete_selected') . '</button>' : '';
        return $datatable->render('global.order-filter', compact('pageTitle', 'auth_user', 'params', 'multi_checkbox_delete', 'filter_file_button', 'reset_file_button'));
    }

    public function applyBidOrder(Request $request)
    {
        $auth_user = auth()->user();
        $deliveryManId = $auth_user->id;

        $orderData = Order::find($request->order_id);

        if (!$orderData) {
            return json_message_response(__('message.not_found_entry', ['name' => __('message.order')]), 404);
        }
        $existingBid = OrderBid::where('order_id', $request->order_id)->where('delivery_man_id', $deliveryManId)->where('is_bid_accept',0)->first();

        if ($existingBid) {
            return json_message_response(__('message.already_bid_applied', ['id' => $request->order_id, 'delivery_man' => $auth_user->name]), 400);
        }
        $orderBidData =  OrderBid::updateOrCreate(
            ['id' => $request->id],
            [
                'order_id' => $request->order_id,
                'is_bid_accept' => 0,
                'delivery_man_id' => $deliveryManId,
                'bid_amount' => $request->bid_amount,
                'notes' => $request->notes,
            ]
        );

        if($orderBidData){
            $orderData->accept_delivery_man_ids = json_encode([$deliveryManId]);
            $orderData->save();
        }

        $history_data = [
            'history_type' => 'bid_placed',
            'order_id' => $orderData->id,
            'order' => $orderData,
            'deliveryManId' => $deliveryManId,
        ];
        saveOrderHistory($history_data);

        return json_message_response(__('message.bid_applied', ['id' => $request->order_id, 'delivery_man' => $auth_user->name]), 201);
    }

    public function getBiddingDeliveryMan(Request $request)
    {
        $order_id = $request->id;
        $orderData = Order::find($order_id);
        if (!$orderData) {
            return response()->json(['error' => 'Order not found.'], 404);
        }

        $bidding_drivers = DB::table('order_bids')
            ->join('users', 'order_bids.delivery_man_id', '=', 'users.id')
            ->where('order_bids.order_id', $order_id)
            ->where('order_bids.is_bid_accept', 0)
            ->select(
                'users.id as delivery_man_id',
                'users.name as delivery_man_name',
                'order_bids.bid_amount',
                'order_bids.notes',
                'order_bids.is_bid_accept',
                'order_bids.created_at',
                'order_bids.updated_at',
            )->get();
        foreach ($bidding_drivers as $driver) {
            $user = User::find($driver->delivery_man_id);
            $driver->profile_image = $user->getFirstMedia('profile_image') ?
                $user->getFirstMedia('profile_image')->getUrl() : null;
        }

        return response()->json([
            'success' => true,
            'data' => $bidding_drivers,
            'start_address' => $orderData->pickup_point['address'],
            'end_address' => $orderData->delivery_point['address'],
        ]);
    }

    public function acceptBidRequest(Request $request)
    {
        $orderData = Order::find($request->id);
        if ($orderData == null) {
            $message = __('message.not_found_entry', ['name' => __('message.order')]);
            return json_message_response($message);
        }

        if ($orderData->status == 'courier_assigned') {
            $message = __('message.not_found_entry', ['name' => __('message.order')]);
            return json_message_response($message, 400);
        }

        $deliveryMnaIds = (array) request('delivery_man_id');
        $deliveryManId = $deliveryMnaIds[0];

        if (request()->has('is_bid_accept') && request('is_bid_accept') == 1) {

            $orderBid = OrderBid::where('delivery_man_id',$deliveryManId)->where('order_id',$orderData->id)->first();
            $history_data = [
                'history_type' => 'bid_accept',
                'order_id' => $orderData->id,
                'order' => $orderData,
                'deliveryManId' => $deliveryManId,
            ];
            saveOrderHistory($history_data);
            $orderData->delivery_man_id = $deliveryManId;
            $orderData->status = 'courier_assigned';
            $orderData->total_amount = $orderBid->bid_amount;
            $orderData->fixed_charges = $orderBid->bid_amount;
            $orderData->save();

            $bid = OrderBid::where('order_id', $orderData->id)->where('delivery_man_id', $deliveryManId)->first();

            if ($bid) {
                $bid->is_bid_accept = 1;
                $bid->save();

                OrderBid::where('order_id', $orderData->id)
                    ->where('delivery_man_id', '!=', $deliveryManId)
                    ->update(['is_bid_accept' => 2]);
            }
            $history_data = [
                'history_type' => 'courier_assigned',
                'order_id' => $orderData->id,
                'order' => $orderData,
            ];
            saveOrderHistory($history_data);
            $document_name = 'order_' . $orderData->id;
            $firebaseData = app('firebase.firestore')->database()->collection('delivery_man')->document($document_name);
            if ($firebaseData) {
                $orderData = [
                    'delivery_man_ids' => (array)$orderData->delivery_man_id ?? [],
                    'order_id' => $orderData->id ?? '',
                    'client_id' => $orderData->client_id ?? '',
                    'status' => $orderData->status ?? '',
                    'client_name' => $orderData->client->name,
                    'client_email' => $orderData->client->email,
                    'client_image' => getSingleMedia($orderData->client, 'profile_image', null),
                    'delivery_man_listening' => 0,
                    'payment_status' => '',
                    'payment_type' => '',
                    'order_has_bids' => $orderData->bid_type == 1 ? 1 : 0,
                    'created_at' => $orderData->created_at,
                ];
            }
            $firebaseData->set($orderData);

            $message = __('message.updated');
        } elseif (request()->has('is_bid_accept') && request('is_bid_accept') == 2) {

            $bid = OrderBid::where('order_id', $orderData->id)->where('delivery_man_id', $deliveryManId)->first();

            if ($bid) {
                $bid->is_bid_accept = 2;
                $bid->save();
            } else {
                OrderBid::create([
                    'order_id' => $orderData->id,
                    'delivery_man_id' => $deliveryManId,
                    'bid_amount' => 0,
                    'is_bid_accept' => 2,
                ]);
            }
            $rejectedDeliveryMen = json_decode($orderData->reject_delivery_man_ids, true) ?? [];
            $acceptedDeliveryMen = json_decode($orderData->accept_delivery_man_ids, true) ?? [];

            if (!in_array($deliveryManId, $rejectedDeliveryMen)) {
                $rejectedDeliveryMen[] = $deliveryManId;
            }

            if (($key = array_search($deliveryManId, $acceptedDeliveryMen)) !== false) {
                unset($acceptedDeliveryMen[$key]);
            }

            $orderData->reject_delivery_man_ids = json_encode($rejectedDeliveryMen);
            $orderData->accept_delivery_man_ids = json_encode(array_values($acceptedDeliveryMen));

            $orderData->save();

            $history_data = [
                'history_type' => 'reject_bid',
                'order_id' => $orderData->id,
                'order' => $orderData,
                'deliveryManId' => $deliveryManId
            ];
            saveOrderHistory($history_data);

        } elseif (request()->has('is_bid_accept') && request('is_bid_accept') == 3) {
              $bid = OrderBid::where('order_id', $orderData->id)->where('delivery_man_id', $deliveryManId)->first();

            if ($bid) {
                $bid->is_bid_accept = 2;
                $bid->save();
            } else {
                OrderBid::create([
                    'order_id' => $orderData->id,
                    'delivery_man_id' => $deliveryManId,
                    'bid_amount' => 0,
                    'is_bid_accept' => 2,
                ]);
            }
            $rejectedDeliveryMen = json_decode($orderData->reject_delivery_man_ids, true) ?? [];
            $acceptedDeliveryMen = json_decode($orderData->accept_delivery_man_ids, true) ?? [];

            if (!in_array($deliveryManId, $rejectedDeliveryMen)) {
                $rejectedDeliveryMen[] = $deliveryManId;
            }

            if (($key = array_search($deliveryManId, $acceptedDeliveryMen)) !== false) {
                unset($acceptedDeliveryMen[$key]);
            }

            $orderData->reject_delivery_man_ids = json_encode($rejectedDeliveryMen);
            $orderData->accept_delivery_man_ids = json_encode(array_values($acceptedDeliveryMen));

            $orderData->save();

            $history_data = [
                'history_type' => 'deliveryman_reject_bid',
                'order_id' => $orderData->id,
                'order' => $orderData,
                'deliveryManId' => $deliveryManId
            ];
            saveOrderHistory($history_data);

        }else {
            $bid = OrderBid::where('order_id', $orderData->id)->where('delivery_man_id', $deliveryManId)->first();

            if ($bid) {
                $bid->is_bid_accept = 0;
                $bid->save();
            }
        }
        $response = [ 'message' => $message ?? __('message.reject_bid') ];

        if ($request->is('api/*')) {
            return json_custom_response($response);
        }

        return response()->json($response);
    }

    public function assignOrder(Request $request)
    {
        $order = Order::findOrFail($request->order_id);
        if ($order->status === 'create') {
            $order->status = 'courier_assigned';
            $order->delivery_man_id = auth()->id();
            $order->save();

            saveOrderHistory([
                'history_type' => 'courier_assigned',
                'order_id' => $order->id,
                'order' => $order,
            ]);
        }
        $order->fill($request->all())->update();
        return response()->json([ 'success' => true, 'message' => __('message.update_form', ['form' => __('message.order')]) ]);
    }

    public function rating(Request $request)
    {
        $user = auth()->user();
        if (! $user) {
            return json_message_response(__('message.unauthorized'), 401);
        }

        $itemId = (int) $request->input('dispatch_order_item_id', 0);
        if ($itemId > 0) {
            $item = \App\Models\DispatchOrderItem::query()
                ->with('order')
                ->find($itemId);

            if (! $item) {
                return json_message_response(__('message.not_found_entry', ['name' => __('message.order')]), 404);
            }

            $order = $item->order;
            $ownsItem = $order
                && (int) $order->client_id === (int) $user->id
                && $user->user_type === 'client';

            if (! $ownsItem) {
                return json_message_response(__('message.demo_permission_denied'), 403);
            }

            if (($item->status ?? '') !== 'completed' || empty($item->delivery_man_id)) {
                return json_message_response(__('message.rider_rating_not_allowed'), 422);
            }

            $request->validate([
                'rating' => 'required|numeric|min:1|max:5',
                'comment' => 'nullable|string|max:1000',
            ]);

            \App\Models\Ratings::updateOrCreate(
                [
                    'dispatch_order_item_id' => $item->id,
                    'user_id' => $user->id,
                ],
                [
                    'order_id' => $item->order_id,
                    'review_user_id' => (int) $item->delivery_man_id,
                    'rating' => (float) $request->input('rating'),
                    'comment' => trim((string) $request->input('comment', '')),
                    'rating_by' => $user->user_type,
                ]
            );

            return json_message_response(__('message.rated_successfully'));
        }

        $order = Order::query()->find((int) $request->input('order_id'));

        if (! $order) {
            return json_message_response(__('message.not_found_entry', ['name' => __('message.order')]), 404);
        }

        // OS rates pickup rider after Pick Up Completed.
        if ($user->user_type === 'client') {
            $ownsOrder = (int) $order->client_id === (int) $user->id;
            $pickupDone = in_array((string) $order->status, [
                'courier_picked_up',
                'courier_departed',
                'completed',
            ], true);
            $hasRider = ! empty($order->delivery_man_id);

            if (! $ownsOrder) {
                return json_message_response(__('message.demo_permission_denied'), 403);
            }
            if (! $pickupDone || ! $hasRider) {
                return json_message_response(__('message.rider_rating_pickup_not_allowed'), 422);
            }

            $request->validate([
                'rating' => 'required|numeric|min:1|max:5',
                'comment' => 'nullable|string|max:1000',
            ]);

            $existing = Ratings::query()
                ->where('order_id', $order->id)
                ->where('user_id', $user->id)
                ->whereNull('dispatch_order_item_id')
                ->first();

            $payload = [
                'user_id' => $user->id,
                'review_user_id' => (int) $order->delivery_man_id,
                'order_id' => $order->id,
                'dispatch_order_item_id' => null,
                'rating' => (float) $request->input('rating'),
                'comment' => trim((string) $request->input('comment', '')),
                'rating_by' => $user->user_type,
            ];

            if ($existing) {
                $existing->fill($payload)->save();
            } else {
                Ratings::query()->create($payload);
            }

            return json_message_response(__('message.rated_successfully'));
        }

        $data = $request->all();
        $data['user_id'] = $user->id;
        if ($user->user_type == 'client') {
            $data['review_user_id'] = $order->delivery_man_id;
        } else {
            $data['review_user_id'] = $order->client_id;
        }

        $data['rating_by'] = $user->user_type;
        Ratings::updateOrCreate(['id' => $request->id], $data);

        $message = __('message.rated_successfully');

        return json_message_response($message);
    }

    public function ordercancel($id)
    {
        $pageTitle = __('message.cancel_order');
        return view('order.cancelmodel',compact('pageTitle','id'));
    }

    public function updateDispatchStatus(Request $request, $id)
    {
        if (!auth()->user()->can('order-edit')) {
            return response()->json(['status' => false, 'message' => __('message.demo_permission_denied')], 403);
        }

        $request->validate([
            'status' => 'required|in:create,courier_assigned,courier_picked_up,completed',
        ]);

        $order = Order::findOrFail($id);

        if (in_array($order->status, ['cancelled', 'completed'])) {
            return response()->json(['status' => false, 'message' => __('message.order_status_cannot_change')], 422);
        }

        $order->status = $request->status;
        $order->save();

        $history_data = [
            'history_type' => $order->status,
            'order_id' => $order->id,
            'order' => $order,
        ];
        saveOrderHistory($history_data);

        return response()->json([
            'status' => true,
            'message' => __('message.status_updated'),
        ]);
    }

    public function saveCancelOrder(Request $request)
    {
        $order = Order::find($request->id);

        if (!$order) {
            return redirect()->back()->with('error', __('message.not_found_entry', ['name' => __('message.order')]));
        }

        $reason = $request->reason === 'Other' ? $request->other_reason : $request->reason;

        $order->status = 'cancelled';
        $order->reason = $reason;
        $order->save();
        $message = __('message.cancelled_order');

        if ($order->payment_id) {
            if ($order->payment->payment_type !== 'cash' && $order->payment->payment_type !== 'wallet') {
                $wallet = Wallet::where('user_id', $order->client_id)->first();

                if ($wallet) {
                    $wallet->total_amount += $order->payment->total_amount;
                    $wallet->save();
                } else {
                    $wallet = Wallet::create([
                        'user_id'      => $order->client_id,
                        'total_amount' => $order->payment->total_amount,
                    ]);
                }

                // Save wallet history
                WalletHistory::create([
                    'user_id'          => $order->client_id,
                    'amount'           => $order->payment->total_amount,
                    'type'             => 'credit',
                    'transaction_type' => 'order_cancel_refund',
                    'order_id'         => $order->id,
                    'note'             => 'Order cancelled refund',
                ]);
            }
        }

        $history_data = [
            'history_type' => $order->status,
            'order_id' => $order->id,
            'order' => $order,
        ];
        saveOrderHistory($history_data);
        return redirect()->back()->withSuccess($message);
    }
}
