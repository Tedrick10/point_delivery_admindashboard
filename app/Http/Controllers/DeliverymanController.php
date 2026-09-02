<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\UserRequest;
use App\DataTables\DeliverymanDataTable;
use App\DataTables\RatingDataTable;
use App\DataTables\WalletHistoryDataTable;
use App\Exports\UsersExport;
use App\Http\Resources\DeliveryManEarningResource;
use App\Http\Resources\WalletHistoryResource;
use App\Http\Resources\UserDetailResource;
use App\Models\User;
use App\Models\Payment;
use App\Models\Order;
use App\Models\City;
use App\Models\Country;
use App\Models\DeliveryManDocument;
use App\Models\DeliverymanVehicleHistory;
use App\Models\Document;
use App\Models\OrderVehicleHistory;
use App\Models\Wallet;
use App\Models\WithdrawRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class DeliverymanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(DeliverymanDataTable $dataTable)
    {
        if (!auth()->user()->can('deliveryman-list')) {
            $message = __('message.demo_permission_denied');
            return redirect()->back()->withErrors($message);
        }
        $pageTitle = __('message.list_form_title', ['form' => __('message.delivery_man')]);
        $auth_user = authSession();
        $assets = ['datatable'];
        $params = null;
        $params = [
            'city_id' => request('city_id') ?? null,
            'country_id' => request('country_id') ?? null,
            'last_actived_at' => request('last_actived_at') ?? null,
        ];
        if (!is_array($params['city_id']) && !is_object($params['city_id'])) {
            $params['city_id'] = null;
        }
        if (!is_array($params['country_id']) && !is_object($params['country_id'])) {
            $params['country_id'] = null;
        }
        $selectedCityId = request('city_id');
        $cities = City::pluck('name', 'id')->prepend(__('message.select_name', ['select' => __('message.city')]), '')->toArray();
        $selectedCountryId = request('country_id');
        $country = Country::pluck('name', 'id')->prepend(__('message.select_name', ['select' => __('message.country')]), '')->toArray();

        if(request('status') == 'active') {
            $pageTitle = __('message.active_list_form_title',['form' => __('message.delivery_man')] );
        } elseif (request('status') == 'inactive') {
            $pageTitle = __('message.inactive_list_form_title',['form' => __('message.delivery_man')] );
        } elseif (request('status') == 'pending') {
            $pageTitle = __('message.pending_list_form_title',['form' => __('message.delivery_man')] );

        }
        $reset_file_button = '<a href="' . route('deliveryman.index') . '" class=" mr-1 mt-0 btn btn-sm btn-info text-dark mt-3 pt-2 pb-2"><i class="ri-repeat-line" style="font-size:12px"></i> ' . __('message.reset_filter') . '</a>';
        $button = $auth_user->can('deliveryman-add')
            ? '<a href="' . route('deliveryman.create') . '" class="btn btn-sm btn-primary"><i class="fa fa-plus-circle"></i> ' . __('message.add_form_title', ['form' => __('message.delivery_man')]) . '</a>'
            : '';
        $export = $auth_user->can('deliveryman-list')
            ? '<a href="'.route('deliveryman.excel').'" class="btn btn-sm btn-success loadRemoteModel"><i class="fa fa-download"></i> '. __('message.export').'</a>'
            : '';
        $multi_checkbox_delete = $auth_user->can('deliveryman-delete') ? '<button id="deleteSelectedBtn" checked-title = "deliveryman-checked" class="float-left btn btn-sm ">' . __('message.delete_selected') . '</button>' : '';
        return $dataTable->with('status', request('status'))->render('global.deliveryman-filter', compact('assets', 'pageTitle', 'button', 'auth_user', 'multi_checkbox_delete','params','reset_file_button','selectedCityId','cities','selectedCountryId','country','export'));
    }

    public function riderOfTheMonth()
    {
        if (! auth()->user()->can('deliveryman-list')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        $from = now()->startOfMonth()->toDateTimeString();
        $to = now()->endOfMonth()->toDateTimeString();

        $finishedCounts = \App\Models\DispatchOrderItem::query()
            ->selectRaw('delivery_man_id, COUNT(*) as finished_ways')
            ->whereNotNull('delivery_man_id')
            ->whereNotNull('admin_finished_at')
            ->whereBetween('admin_finished_at', [$from, $to])
            ->groupBy('delivery_man_id')
            ->pluck('finished_ways', 'delivery_man_id');

        $riders = User::query()
            ->where('user_type', 'delivery_man')
            ->whereNull('deleted_at')
            ->withAvg('rating as average_rating', 'rating')
            ->withCount('rating as ratings_count')
            ->get()
            ->map(function (User $rider) use ($finishedCounts) {
                $avg = round((float) ($rider->average_rating ?? 0), 2);
                $finished = (int) ($finishedCounts[$rider->id] ?? 0);

                return [
                    'id' => (int) $rider->id,
                    'name' => $rider->name,
                    'average_rating' => $avg,
                    'ratings_count' => (int) ($rider->ratings_count ?? 0),
                    'finished_ways' => $finished,
                    'profile_image' => getSingleMedia($rider, 'profile_image', null),
                    'score' => ($avg * 1000) + $finished,
                ];
            })
            ->filter(fn ($row) => $row['ratings_count'] > 0 || $row['finished_ways'] > 0)
            ->sortByDesc('score')
            ->values();

        $winner = $riders->first();

        return response()->json([
            'month_label' => now()->format('M Y'),
            'rider' => $winner,
            'message' => $winner
                ? null
                : __('message.rider_of_the_month_empty'),
        ]);
    }

    public function reviews($id)
    {
        if (! auth()->user()->can('deliveryman-list') && ! auth()->user()->can('order-list')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        $rider = User::query()
            ->where('user_type', 'delivery_man')
            ->whereNull('deleted_at')
            ->findOrFail((int) $id);

        $avg = round((float) $rider->rating()->avg('rating'), 2);
        $count = (int) $rider->rating()->count();

        $reviews = \App\Models\Ratings::query()
            ->with(['user:id,name', 'dispatchOrderItem:id,code,customer_name'])
            ->where('review_user_id', $rider->id)
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(static function ($row) {
                return [
                    'id' => (int) $row->id,
                    'rating' => (float) $row->rating,
                    'comment' => (string) ($row->comment ?? ''),
                    'rating_by' => (string) ($row->rating_by ?? ''),
                    'reviewer_name' => optional($row->user)->name ?: '-',
                    'item_id' => $row->dispatch_order_item_id ? (int) $row->dispatch_order_item_id : null,
                    'item_code' => optional($row->dispatchOrderItem)->code,
                    'customer_name' => optional($row->dispatchOrderItem)->customer_name,
                    'order_id' => $row->order_id ? (int) $row->order_id : null,
                    'created_at' => optional($row->created_at)?->format('d/m/Y H:i'),
                ];
            })
            ->values();

        return response()->json([
            'rider' => [
                'id' => (int) $rider->id,
                'name' => $rider->name,
                'average_rating' => $avg,
                'ratings_count' => $count,
                'profile_image' => getSingleMedia($rider, 'profile_image', null),
            ],
            'reviews' => $reviews,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!auth()->user()->can('deliveryman-add')) {
            $message = __('message.demo_permission_denied');
            return redirect()->back()->withErrors($message);
        }
        $pageTitle = __('message.add_form_title', ['form' => __('message.delivery_man')]);
        $assets = ['phone'];
        $branches = \App\Models\Branch::query()->where('status', 1)->orderBy('name')->pluck('name', 'id')->toArray();

        return view('deliveryman.form', compact('pageTitle', 'assets', 'branches'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(UserRequest $request)
    {
        $is_email_verification = registrationSettingValue('driver_registration_setting', 'email_verification');
        $is_mobile_verification = registrationSettingValue('driver_registration_setting', 'mobile_verification');
        $is_document_verification = registrationSettingValue('driver_registration_setting', 'document_verification');

        $request['password'] = bcrypt($request->password);
        $request['username'] = $request->username ?? stristr($request->email, "@", true) . rand(100, 1000);
        $request['display_name'] = $request['name'];
        $request['user_type'] = 'delivery_man';
        $request['status'] = $request->status ?? 1;

        $contactNumber = preg_replace('/\s+/', '', (string) $request->input('contact_number', ''));
        $request->merge(['contact_number' => $contactNumber !== '' ? $contactNumber : null]);

        $request['referral_code'] = generateRandomCode();

        if ($is_email_verification == 0) {
            $request['email_verified_at'] = now();
        }

        if ($is_mobile_verification == 0) {
            $request['otp_verify_at'] = now();
        }

        if ($is_document_verification == 0) {
            $request['document_verified_at'] = now();
        }

        $forcedBranchId = forcedBranchId(auth()->user());
        if ($forcedBranchId && ! $request->filled('branch_id')) {
            $request->merge(['branch_id' => $forcedBranchId]);
        }

        $result = User::create($request->all());
        uploadMediaFile($result, $request->profile_image, 'profile_image');
        $result->assignRole($request->user_type);

        $message = __('message.save_form', ['form' => __('message.delivery_man')]);
        if ($request->is('api/*')) {
            return json_message_response($message);
        }
        return redirect()->route('deliveryman.index')->withSuccess($message);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(DeliverymanDataTable $dataTable, WalletHistoryDataTable $wallethistorydatatable,RatingDataTable $ratingdatatable, $id)
    {
        if (!auth()->user()->can('deliveryman-show')) {
            $message = __('message.demo_permission_denied');
            return redirect()->back()->withErrors($message);
        }
        $user = User::where('id', $id)->first();
        $auth_user = authSession();
        $pageTitle = __('message.view_form_title', ['form' => __('message.delivery_man')]);
        $data = User::findOrFail($id);
        $type = request('type') ?? 'detail';

        $requiredDocumentIds = Document::where('is_required', 1)
        ->where('status', 1)
        ->pluck('id')
        ->toArray();

        switch ($type) {
            case 'detail':
                $bank_detail = $user->userBankAccount()->orderBy('id', 'desc')->paginate(10);
                $bank_detail_items = UserDetailResource::collection($bank_detail);
                return $dataTable->with($id)->render('deliveryman.show', compact('pageTitle', 'type', 'data', 'bank_detail', 'bank_detail_items','user','requiredDocumentIds'));
                break;

            case 'wallethistory':
                $wallet_history = $user->userWalletHistory()->orderBy('id', 'desc')->get();
                $wallet_history_items = WalletHistoryResource::collection($wallet_history);

                $earning_list = Payment::with('order')->withTrashed()->where('payment_status', 'paid')
                    ->whereHas('order', function ($query) use ($user) {
                        $query->whereIn('status', ['completed', 'cancelled'])->where('delivery_man_id', $user->id);
                    })->orderBy('id', 'desc')->paginate(10);


                $earning_detail_items = DeliveryManEarningResource::collection($earning_list);

                $earning_detail = User::select('id', 'name')->withTrashed()->where('id', $user->id)
                    ->with([
                        'userWallet:total_amount,total_withdrawn',
                        'getPayment:order_id,delivery_man_commission,admin_commission'
                    ])
                    ->withCount([
                        'deliveryManOrder as total_order',
                        'getPayment as paid_order' => function ($query) {
                            $query->where('payment_status', 'paid');
                        }
                    ])
                    ->withSum('userWallet', 'total_amount')
                    ->withSum('userWallet', 'total_withdrawn')
                    ->withSum('getPayment', 'admin_commission')
                    ->withSum('getPayment as delivery_man_commission', 'delivery_man_commission')
                    ->first();

                return $wallethistorydatatable->with($id)->render('deliveryman.show', compact('pageTitle', 'type', 'data', 'id',  'earning_detail', 'wallet_history', 'wallet_history_items', 'earning_list', 'earning_detail_items'));
                break;
            case 'orderhistory':
                $order = Order::where('delivery_man_id', $id)->get();
                return view('deliveryman.show', compact('pageTitle', 'data', 'type', 'order'));
                break;
            case 'withdrawrequest':
                $wallte = Wallet::where('user_id',$id)->first();
                $withdraw = WithdrawRequest::where('user_id', $id)->get();
                return view('deliveryman.show', compact('pageTitle', 'data', 'type', 'withdraw','wallte'));
                break;
            case 'document':
                $documents = DeliveryManDocument::where('delivery_man_id',$user->id)->get();
                return view('deliveryman.show', compact('pageTitle', 'data', 'type', 'documents'));
                break;
            case 'vehicle_information':
                $deliverymanvehicle = DeliverymanVehicleHistory::where('delivery_man_id', $user->id)->get();
                return view('deliveryman.show', compact('pageTitle', 'data', 'type','deliverymanvehicle'));
                break;

                case 'rating':
                    return $ratingdatatable->with(['delivery_man_id'=>$id])->render('deliveryman.show', compact('pageTitle', 'data', 'type'));
                    break;

                default:
                break;
        }
        return $dataTable->with($id)->render('deliveryman.show', compact('pageTitle', 'data', 'id',  'auth_user'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!auth()->user()->can('deliveryman-edit')) {
            $message = __('message.demo_permission_denied');
            return redirect()->back()->withErrors($message);
        }
        $pageTitle = __('message.update_form_title', ['form' => __('message.delivery_man')]);
        $data = User::find($id);
        $profileImage = getSingleMedia($data, 'profile_image');
        $assets = ['phone'];
        $branches = \App\Models\Branch::query()->where('status', 1)->orderBy('name')->pluck('name', 'id')->toArray();
        if ($data && $data->branch_id && ! isset($branches[$data->branch_id])) {
            $current = \App\Models\Branch::withTrashed()->find($data->branch_id);
            if ($current) {
                $branches = [$current->id => $current->name] + $branches;
            }
        }

        return view('deliveryman.form', compact('data', 'pageTitle', 'id', 'assets', 'profileImage', 'branches'));
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('deliveryman-edit')) {
            $message = __('message.demo_permission_denied');
            return redirect()->back()->withErrors($message);
        }
        $deliveryman = User::find($id);

        $deliveryman->removeRole($deliveryman->user_type);
        $message = __('message.not_found_entry', ['name' => __('message.delivery_man')]);
        if ($deliveryman == null) {
            return json_custom_response(['status' => false, 'message' => $message]);
        }

        $request->validate([
            'branch_id' => 'required|exists:branches,id',
        ]);

        $deliveryman->fill($request->all())->update();

        if ($request->hasFile('profile_image')) {
            uploadMediaFile($deliveryman, $request->profile_image, 'profile_image');
        }

        $deliveryman->assignRole($request['user_type']);

        $message = __('message.update_form', ['form' => __('message.delivery_man')]);
        if ($request->is('api/*')) {
            return json_message_response($message);
        }
        return redirect()->route('deliveryman.index')->withSuccess($message);
    }

    /**
     * Inline update rider contact number from Rider List table.
     */
    public function updateContactNumber(Request $request, $id)
    {
        if (! auth()->user()->can('deliveryman-edit')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        if (env('APP_DEMO')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        $deliveryman = User::where('user_type', 'delivery_man')->findOrFail($id);

        $request->validate([
            'contact_number' => 'required|max:20|unique:users,contact_number,'.$deliveryman->id,
        ], [
            'contact_number.unique' => __('message.contact_number_already_taken'),
        ]);

        $phone = preg_replace('/\s+/', '', (string) $request->input('contact_number', ''));
        $deliveryman->contact_number = $phone;
        $deliveryman->save();

        return response()->json([
            'message' => __('message.update_form', ['form' => __('message.contact_number')]),
            'contact_number' => $deliveryman->contact_number,
            'display' => maskSensitiveInfo('contact_number', $deliveryman->contact_number),
        ]);
    }

    public function updateWorkStatus(Request $request, int $id)
    {
        if (! auth()->user()->can('deliveryman-edit')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        if (env('APP_DEMO')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        $request->validate([
            'work_on' => 'required|boolean',
        ]);

        $deliveryman = User::query()
            ->where('user_type', 'delivery_man')
            ->whereNull('deleted_at')
            ->findOrFail($id);

        $forcedBranchId = forcedBranchId(auth()->user());
        if ($forcedBranchId) {
            $branchId = (int) ($deliveryman->branch_id ?? 0);
            if ($branchId > 0 && $branchId !== $forcedBranchId) {
                return response()->json(['message' => __('message.demo_permission_denied')], 403);
            }
        }

        $workOn = $request->boolean('work_on');
        $deliveryman->rider_work_on = $workOn;
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'rider_work_off_date')) {
            $deliveryman->rider_work_off_date = $workOn ? null : now('Asia/Yangon')->toDateString();
        }
        $deliveryman->save();

        return response()->json([
            'message' => __('message.rider_work_status_updated'),
            'work_on' => (bool) $deliveryman->rider_work_on,
            'label' => $deliveryman->rider_work_on
                ? __('message.rider_work_on')
                : __('message.rider_work_off'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('deliveryman-delete')) {
            $message = __('message.demo_permission_denied');
            return redirect()->back()->withErrors($message);
        }
        if(env('APP_DEMO')){
            $message = __('message.demo_permission_denied');
            if(request()->is('api/*')){
                return response()->json(['status' => true, 'message' => $message ]);
            }
            if(request()->ajax()) {
                return response()->json(['status' => false, 'message' => $message, 'event' => 'validation']);
            }
            return redirect()->route('deliveryman.index')->withErrors($message);
        }
        $deliveryman = User::find($id);
        if ($deliveryman == null) {
            $message = __('message.not_found_entry', ['name' => __('message.delivery_man')]);
            return json_message_response($message, 400);
        }

        if ($deliveryman != '') {
            $deliveryman->userBankAccount()->delete();
            $deliveryman->userAddress()->delete();
            DeliveryManDocument::where('delivery_man_id', $deliveryman->id)->delete();
            $deliveryman->forceDelete();
            $status = 'success';
            $message = __('message.delete_form',['form' => __('message.delivery_man')]);
        }

        if (request()->ajax()) {
            return response()->json(['status' => true, 'message' => $message]);
        }
        if (request()->is('api/*')) {
            return json_message_response($message);
        }
        return redirect()->route('deliveryman.index')->withSuccess($message);
    }
    public function action(Request $request, $id)
    {
        $id = $request->id ?? $id;
        $users = User::withTrashed()->where('id', $id)->first();

        $message = __('message.not_found_entry', ['name' => __('message.delivery_man')]);
        if ($request->type === 'restore') {
            $users->restore();
            $message = __('message.msg_restored', ['name' => __('message.delivery_man')]);
        }

       if($request->type === 'forcedelete'){
            if(env('APP_DEMO')){
                $message = __('message.demo_permission_denied');
                if(request()->is('api/*')){
                    return response()->json(['status' => true, 'message' => $message ]);
                }
                if(request()->ajax()) {
                    return response()->json(['status' => false, 'message' => $message, 'event' => 'validation']);
                }
                return redirect()->route('deliveryman.index')->withErrors($message);
            }
            if ($users) {
                $users->userBankAccount()->delete();
                $users->userAddress()->delete();
                DeliveryManDocument::where('delivery_man_id', $users->id)->delete();
                $users->forceDelete();
                $message = __('message.msg_forcedelete', ['name' => __('message.delivery_man')]);
            }
        }
        if (request()->is('api/*')) {
            return json_custom_response(['message' => $message, 'status' => true]);
        }

        return redirect()->route('deliveryman.index')->withSuccess($message);
    }
    public function updateVerification(User $user, Request $request)
    {
        if ($request->confirm === 'yes') {
            $user = User::findOrFail($request->id);
            switch ($request->type) {
                case 'email':
                    $user->is_autoverified_email = 0;
                    $user->email_verified_at = null;
                    $user->save();
                    return redirect()->back()->with('success',__('message.re_email_verification'));
                    break;
                case 'mobile':
                    $user->is_autoverified_mobile = 0;
                    $user->otp_verify_at = null;
                    $user->save();
                    return redirect()->back()->with('success', __('message.re_mobile_verification'));
                    break;
                case 'document':
                    $user->is_autoverified_document = 0;
                     $user->document_verified_at = null;
                    $user->save();
                    $documents = DeliveryManDocument::where('delivery_man_id', $user->id)->get();
                    foreach ($documents as $document) {
                        $document->delete();
                    }
                    return redirect()->back()->with('success', __('message.re_document_verification'));
                    break;
                default:
                    break;
            }
        } else {
            return redirect()->back()->with('info', __('message.cancel_verification'));
        }
    }
    public function vehicleInformationOrder(Request $request, $id)
    {
        $pageTitle = __('message.vehicle_information');
        $ordervehiclehistorydata = OrderVehicleHistory::where('order_id', $id)->get();
        foreach ($ordervehiclehistorydata as $history) {
            $history->vehicle_info = json_decode($history->vehicle_info, true);
        }
        return view('deliveryman.ordervehicleinfromation', compact('pageTitle','id','ordervehiclehistorydata'));
    }

    public function vehicleInformation(Request $request, $id)
    {
        $pageTitle = __('message.vehicle_information');
        $ordervehiclehistory = DeliverymanVehicleHistory::where('id', $id)->get();
        foreach ($ordervehiclehistory as $history) {
            $history->vehicle_info = json_decode($history->vehicle_info, true);
        }

        return view('deliveryman.vehicleinfromation', compact('pageTitle', 'id', 'ordervehiclehistory'));
    }
    public function updateVehicleStatus(Request $request)
    {
        if (env('APP_DEMO')) {
            $message = __('message.demo_permission_denied');
            return redirect()->back()->withErrors($message);
        }

        $vehicle = DeliverymanVehicleHistory::find($request->id);

        if ($vehicle) {
            $newStatus = $vehicle->is_active == 1 ? 0 : 1;

            DeliverymanVehicleHistory::where('delivery_man_id', $vehicle->delivery_man_id)
                ->where('id', '!=', $vehicle->id)
                ->update(['is_active' => 0]);

            $vehicle->is_active = $newStatus;
            $vehicle->save();

            $message = $newStatus == 1 ? __('message.vehicle_is_now_active') : __('message.vehicle_is_now_inactive');

            if (request()->is('api/*')) {
                return response()->json(['status' => true, 'message' => $message]);
            }

            return redirect()->back()->with('success', $message);
        }

        return redirect()->back()->with('error', 'Something went wrong.');
    }


    public function deliverymanExcel()
    {
        return view('deliveryman.excel');
    }

    public function downloadDeliverymanReport(Request $request, $fileType = 'xlsx')
    {
        $startDate = $request->input('from_date');
        $endDate   = $request->input('to_date');

        $start = $startDate ? Carbon::parse($startDate)->format('Y-m-d') : null;
        $end   = $endDate ? Carbon::parse($endDate)->format('Y-m-d') : null;

       $deliverymanData = User::where('user_type','delivery_man')->when($startDate, fn($q) => $q->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('created_at', '<=', $endDate))->get();

        $export = new UsersExport($deliverymanData, $request);
        $filenameDatePart = '';
        if ($start && $end) {
            $filenameDatePart = "_{$start}_to_{$end}";
        } elseif ($start) {
            $filenameDatePart = "_from_{$start}";
        } elseif ($end) {
            $filenameDatePart = "_to_{$end}";
        } else {
            $filenameDatePart = "_" . now()->format('Y-m-d');
        }

        $filename = "DeliveryMan-report{$filenameDatePart}.{$fileType}";

        $format = match (strtolower($fileType)) {
            'csv'  => \Maatwebsite\Excel\Excel::CSV,
            'xls'  => \Maatwebsite\Excel\Excel::XLS,
            'ods'  => \Maatwebsite\Excel\Excel::ODS,
            'html' => \Maatwebsite\Excel\Excel::HTML,
            default => \Maatwebsite\Excel\Excel::XLSX,
        };

        return Excel::download($export, $filename, $format);
    }

    public function downloadDeliverymanPdf(Request $request)
    {
        $startDate = $request->input('from_date') ;
        $endDate   = $request->input('to_date') ;

        $deliverymanData = User::where('user_type','delivery_man')->when($startDate, fn($q) => $q->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('created_at', '<=', $endDate))->get();

        $export = new UsersExport($deliverymanData, $request);
        $collection = $export->collection();
        $mappedData = $collection->map([$export, 'map']);
        $headings = $export->headings();

        $dateFilterText = '';
        $filenameDatePart = '';
        if ($startDate && $endDate) {
            $fromDateFormatted = Carbon::parse($startDate)->format('Y-m-d');
            $toDateFormatted = Carbon::parse($endDate)->format('Y-m-d');
            $dateFilterText = 'From Date: ' . $fromDateFormatted . ' To Date: ' . $toDateFormatted;
            $filenameDatePart = '_from_' . $fromDateFormatted . '_to_' . $toDateFormatted;
        } elseif ($startDate) {
            $fromDateFormatted = Carbon::parse($startDate)->format('Y-m-d');
            $dateFilterText = 'From Date: ' . $fromDateFormatted;
            $filenameDatePart = '_from_' . $fromDateFormatted;
        } elseif ($endDate) {
            $toDateFormatted = Carbon::parse($endDate)->format('Y-m-d');
            $dateFilterText = 'To Date: ' . $toDateFormatted;
            $filenameDatePart = '_to_' . $toDateFormatted;
        }

        $htmlContent = '<h1>DeliveryMan Report</h1>';
        if ($dateFilterText) {
            $htmlContent .= '<p><strong>' . $dateFilterText . '</strong></p>';
        }

        $htmlContent .= '<style>
            body { font-family: "DejaVu Sans", sans-serif; }
            table { width: 100%; border-collapse: collapse; border-bottom: 1px solid black; }
            th, td { padding: 8px; text-align: left; border-bottom: 1px solid #bfbfbf; }
            h1 { text-align: center; }
            p { font-size: 18px; }
        </style>';

        $htmlContent .= '<table>';
        $htmlContent .= '<thead><tr>';
        foreach ($headings[2] ?? $headings as $heading) {
            $htmlContent .= '<th>' . $heading . '</th>';
        }
        $htmlContent .= '</tr></thead>';
        $htmlContent .= '<tbody>';
        foreach ($mappedData as $row) {
            $htmlContent .= '<tr>';
            foreach ($row as $cell) {
                $htmlContent .= '<td>' . $cell . '</td>';
            }
            $htmlContent .= '</tr>';
        }
        $htmlContent .= '</tbody></table>';

        // Generate PDF
        $pdf = Pdf::loadHTML($htmlContent)->setPaper('a4', 'landscape');
        $filename = 'DeliveryMan-report' . $filenameDatePart . '.pdf';

        return $pdf->download($filename);
    }
}
