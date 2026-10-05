<?php

namespace App\Http\Controllers;

use App\DataTables\ClaimsDataTable;
use Illuminate\Http\Request;
use App\Http\Requests\UserRequest;
use App\DataTables\ClientDataTable;
use App\DataTables\WalletHistoryDataTable;
use App\DataTables\RatingDataTable;
use App\DataTables\ReferenceDataTable;
use App\Exports\UsersExport;
use App\Http\Resources\WalletHistoryResource;
use App\Http\Resources\UserDetailResource;
use App\Models\User;
use App\Models\Order;
use App\Models\Wallet;
use App\Models\Branch;
use App\Models\City;
use App\Services\AppPushService;
use App\Models\Claims;
use App\Models\Country;
use App\Models\UserAddress;
use App\Models\WithdrawRequest;
use App\Models\UserBankAccount;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(ClientDataTable $dataTable)
    {
        if (!auth()->user()->can('users-list')) {
            $message = __('message.demo_permission_denied');
            return redirect()->back()->withErrors($message);
        }
        $pageTitle = __('message.list_form_title', ['form' => __('message.online_shop')]);
        $auth_user = authSession();
        $assets = ['datatable'];
        $approvalTab = request('status');
        if (! in_array($approvalTab, ['pending', 'approved', 'rejected', 'kyo_shin'], true)) {
            return redirect()->route('users.index', array_merge(request()->query(), ['status' => 'pending']));
        }
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

        [$selectedBranchId, $branchFilter, $branchTabs] = resolveDestinationBranchFilter(request());
        $osCountQuery = User::query()->where('user_type', 'client');
        excludeScrubbedOnlineShops($osCountQuery);
        applyClientBranchScope($osCountQuery, auth()->user(), $selectedBranchId);

        $pendingCountQuery = User::query()->where('user_type', 'client');
        excludeScrubbedOnlineShops($pendingCountQuery);
        applyOnlineShopListBranchScope($pendingCountQuery, auth()->user(), $selectedBranchId, 'pending');

        $approvalCounts = [
            'pending' => applyOnlineShopApprovalTab(clone $pendingCountQuery, 'pending')->count(),
            'approved' => (clone $osCountQuery)->where('approval_status', User::APPROVAL_APPROVED)->count(),
            'rejected' => (clone $osCountQuery)->where('approval_status', User::APPROVAL_REJECTED)->count(),
            'kyo_shin' => (clone $osCountQuery)->where('is_kyo_shin', true)->count(),
        ];
        $branchTabCounts = User::query()
            ->where('user_type', 'client')
            ->whereNull('deleted_at')
            ->selectRaw('branch_id, COUNT(*) as total')
            ->groupBy('branch_id')
            ->pluck('total', 'branch_id');
        if (forcedBranchId()) {
            $forced = (int) forcedBranchId();
            $branchTabCounts = $branchTabCounts->only([$forced]);
        }

        if ($approvalTab === 'approved') {
            $pageTitle = __('message.active_list_form_title', ['form' => __('message.online_shop')]);
        } elseif ($approvalTab === 'rejected') {
            $pageTitle = __('message.inactive_list_form_title', ['form' => __('message.online_shop')]);
        } elseif ($approvalTab === 'kyo_shin') {
            $pageTitle = __('message.kyo_shin_os_list_title');
        } else {
            $pageTitle = __('message.pending_list_form_title', ['form' => __('message.online_shop')]);
        }

        $reset_file_button = '<a href="' . route('users.index', ['status' => $approvalTab]) . '" class="btn btn-sm btn-outline-primary"><i class="ri-repeat-line"></i> ' . __('message.reset_filter') . '</a>';
        $button = $auth_user->can('users-add') ? '<a href="' . route('users.create') . '" class="btn btn-sm btn-primary pds-os-list-add"><i class="fa fa-plus"></i> ' . __('message.add_form_title', ['form' => __('message.online_shop')]) . '</a>' : '';
        $multi_checkbox_delete = $auth_user->can('users-delete')
            ? '<button id="deleteSelectedBtn" checked-title="users-checked" class="btn btn-sm btn-outline-danger" style="display:none" hidden>' . __('message.delete_selected') . '</button>'
            : '';
        $export = $auth_user->can('users-add') ? '<a href="'.route('user.excel').'" class="btn btn-sm btn-outline-success loadRemoteModel"><i class="fa fa-download"></i> '. __('message.export').'</a>' : '';
        return $dataTable->with([
            'branch_id' => $selectedBranchId,
        ])->render('global.user-filter', compact(
            'assets',
            'pageTitle',
            'button',
            'auth_user',
            'multi_checkbox_delete',
            'params',
            'reset_file_button',
            'selectedCityId',
            'cities',
            'selectedCountryId',
            'country',
            'export',
            'approvalTab',
            'approvalCounts',
            'branchTabs',
            'selectedBranchId',
            'branchTabCounts',
            'branchFilter'
        ));
    }
    public function referenceindex(ReferenceDataTable $dataTable)
    {
        $pageTitle = __('message.list_form_title', ['form' => __('message.reference_program')]);
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
        $reset_file_button = '<a href="' . route('reference-list') . '" class=" mr-1 mt-0 btn btn-sm btn-info text-dark mt-3 pt-2 pb-2"><i class="ri-repeat-line" style="font-size:12px"></i> ' . __('message.reset_filter') . '</a>';
        $multi_checkbox_delete = null;
        return $dataTable->render('global.reference-filter', compact('assets', 'pageTitle','auth_user', 'multi_checkbox_delete','params','reset_file_button','selectedCityId','cities','selectedCountryId','country'));
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!auth()->user()->can('users-add')) {
            $message = __('message.demo_permission_denied');
            return redirect()->back()->withErrors($message);
        }
        $pageTitle = __('message.add_form_title', ['form' => __('message.online_shop')]);
        $assets = ['phone'];
        return view('users.form', compact('pageTitle', 'assets'));
    }

    public function createOsAccount()
    {
        if (!auth()->user()->can('users-add')) {
            $message = __('message.demo_permission_denied');
            return response()->json(['status' => false, 'message' => $message], 403);
        }

        return view('users.os-account-modal');
    }

    public function storeOsAccount(Request $request)
    {
        if (!auth()->user()->can('users-add')) {
            return response()->json(['status' => false, 'message' => __('message.demo_permission_denied')], 403);
        }

        $rawPhone = $request->input('contact_number');
        if ((! $request->filled('contact_number') || $rawPhone === '' || $rawPhone === null) && $request->filled('phone')) {
            $rawPhone = $request->input('phone');
        }
        if (is_array($rawPhone)) {
            $rawPhone = collect($rawPhone)->filter()->last();
        }
        if ($rawPhone !== null && $rawPhone !== '') {
            $contactNumber = normalizeContactNumber((string) $rawPhone);
            if ($contactNumber === '' || preg_match('/^\+\d{1,4}$/', $contactNumber)) {
                $contactNumber = null;
            }
            $request->merge(['contact_number' => $contactNumber]);
        }

        $osProfile = prepareUserOsProfileFromRequest($request);
        $address = buildUserAddressFromProfile($osProfile);
        $username = sanitizeRegistrationUsername((string) $request->username);
        if ($username === '') {
            $username = registrationUsernameFromPhone((string) ($request->contact_number ?? ''));
        }

        $branchId = destinationBranchIdFromOsLocation(
            $osProfile['state_division'] ?? null,
            $osProfile['township'] ?? null
        ) ?: defaultDestinationBranchId() ?: forcedBranchId();

        $city = null;
        if ($request->filled('city_id')) {
            $city = City::find($request->city_id);
        }
        if (! $city && $branchId) {
            $branchCity = (string) (Branch::query()->where('id', $branchId)->value('city_name') ?? '');
            if ($branchCity !== '') {
                $city = City::query()->where('status', 1)->where('name', $branchCity)->first();
            }
        }

        if ($request->boolean('from_dispatch')) {
            $existingUser = User::where('user_type', 'client')
                ->where(function ($query) use ($request, $username) {
                    if ($request->filled('contact_number')) {
                        $query->where('contact_number', $request->contact_number);
                    }
                    if ($username !== '') {
                        $query->orWhere('username', $username);
                    }
                })
                ->first();

            if ($existingUser) {
                $existingUser->update([
                    'name' => $request->name,
                    'username' => $username !== '' ? $username : $existingUser->username,
                    'contact_number' => $request->contact_number ?: $existingUser->contact_number,
                    'address' => $address !== '' ? $address : $existingUser->address,
                    'city_id' => $city?->id ?? $existingUser->city_id,
                    'country_id' => $city?->country_id ?? $existingUser->country_id,
                    'branch_id' => $existingUser->branch_id ?: $branchId,
                    'os_profile' => array_merge(
                        is_array($existingUser->os_profile) ? $existingUser->os_profile : [],
                        $osProfile
                    ),
                ]);
                if ($request->hasFile('profile_image')) {
                    uploadMediaFile($existingUser, $request->profile_image, 'profile_image');
                }

                return response()->json([
                    'status' => true,
                    'message' => __('message.os_account_already_exists'),
                    'user' => $this->formatOsDispatchUserPayload($existingUser->fresh(), $city),
                ]);
            }
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|min:3|max:50|unique:users,username',
            'password' => 'required|string|min:6|confirmed',
            'contact_number' => 'required|string|max:30|unique:users,contact_number',
            'os_profile.address_unit' => 'required|string|max:255',
            'os_profile.state_division' => 'required|string|max:255',
            'os_profile.township' => 'required|string|max:255',
            'os_profile.kpay_name' => 'required|string|max:255',
            'os_profile.kpay_no' => 'required|string|max:50',
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png,gif',
        ]);

        $email = registrationEmailFromPhone((string) $request->contact_number);
        if (User::withTrashed()->where('email', $email)->exists()) {
            $digits = preg_replace('/\D+/', '', (string) $request->contact_number) ?: 'user';
            $email = $digits.'.'.uniqid('os', false).'@pointdelivery.local';
        }

        $user = User::create([
            'name' => $request->name,
            'username' => $username,
            'email' => $email,
            'password' => bcrypt($request->password),
            'contact_number' => $request->contact_number,
            'address' => $address,
            'city_id' => $city?->id,
            'country_id' => $city?->country_id,
            'branch_id' => $branchId,
            'user_type' => 'client',
            'status' => 1,
            'approval_status' => User::APPROVAL_APPROVED,
            'created_by_admin' => 1,
            'is_temp_password' => 1,
            'email_verified_at' => now(),
            'otp_verify_at' => now(),
            'referral_code' => generateRandomCode(),
            'os_profile' => $osProfile,
        ]);

        $user->assignRole('client');
        if ($request->hasFile('profile_image')) {
            uploadMediaFile($user, $request->profile_image, 'profile_image');
        }
        createDefaultUserAddressFromRegistration($user, $request->contact_number);

        return response()->json([
            'status' => true,
            'message' => __('message.save_form', ['form' => __('message.online_shop')]),
            'user' => $this->formatOsDispatchUserPayload($user, $city),
        ]);
    }

    private function prepareOsProfileFromRequest(Request $request): array
    {
        $osProfile = array_filter($request->input('os_profile', []), function ($value) {
            return ! is_null($value) && $value !== '';
        });

        if (!empty($osProfile['nrc_region']) || !empty($osProfile['nrc_number'])) {
            $osProfile['nrc'] = trim(
                ($osProfile['nrc_region'] ?? '') . '/' .
                ($osProfile['nrc_town'] ?? '') . '(' .
                ($osProfile['nrc_type'] ?? '') . ')' .
                ($osProfile['nrc_number'] ?? '')
            );
        }

        return $osProfile;
    }

    private function formatOsDispatchUserPayload(User $user, ?City $city = null): array
    {
        $city = $city ?? City::find($user->city_id);
        $displayName = $user->name . ($city && $city->name ? ' (' . $city->name . ')' : '');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'text' => $displayName,
            'phone' => normalizeContactNumber($user->contact_number),
            'address' => $user->address,
        ];
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(UserRequest $request)
    {
        try {
            $is_email_verification = registrationSettingValue('user_registration_setting', 'email_verification');
            $is_mobile_verification = registrationSettingValue('user_registration_setting', 'mobile_verification');

            $osProfile = prepareUserOsProfileFromRequest($request);
            $address = buildUserAddressFromProfile($osProfile);
            $username = sanitizeRegistrationUsername((string) $request->username);
            if ($username === '') {
                $username = registrationUsernameFromPhone($request->contact_number);
            }
            $email = registrationEmailFromPhone($request->contact_number);
            if (User::withTrashed()->where('email', $email)->exists()) {
                $digits = preg_replace('/\D+/', '', (string) $request->contact_number) ?: 'user';
                $email = $digits.'.'.uniqid('os', false).'@pointdelivery.local';
            }

            $payload = $request->only(['name', 'contact_number']);
            $payload['password'] = bcrypt($request->password);
            $payload['username'] = $username;
            $payload['email'] = $email;
            $payload['address'] = $address;
            $payload['os_profile'] = $osProfile;
            $payload['display_name'] = $request->name;
            $payload['user_type'] = 'client';
            $payload['referral_code'] = generateRandomCode();
            $payload['created_by_admin'] = $request->created_by_admin ?? 1;
            $payload['is_temp_password'] = $request->is_temp_password ?? 1;
            $payload['is_vip'] = 0;

            $approvalStatus = $request->input('approval_status', User::APPROVAL_APPROVED);
            if (! in_array($approvalStatus, User::approvalStatuses(), true)) {
                $approvalStatus = User::APPROVAL_APPROVED;
            }
            $payload['approval_status'] = $approvalStatus;
            $payload['status'] = $approvalStatus === User::APPROVAL_APPROVED ? 1 : 0;

            if ($is_email_verification == 0) {
                $payload['email_verified_at'] = now();
            }

            if ($is_mobile_verification == 0) {
                $payload['otp_verify_at'] = now();
            }

            $payload['branch_id'] = $payload['branch_id']
                ?? defaultDestinationBranchId()
                ?: forcedBranchId();

            $result = User::create($payload);
            uploadMediaFile($result, $request->profile_image, 'profile_image');
            if (! $result->hasRole('client')) {
                $result->assignRole('client');
            }
            createDefaultUserAddressFromRegistration($result, $request->contact_number);
            $message = __('message.save_form', ['form' => __('message.online_shop')]);
            if ($request->is('api/*')) {
                return json_message_response($message);
            }
            return redirect()->route('users.index')->withSuccess($message);
        } catch (\Throwable $e) {
            report($e);
            $message = __('message.something_went_wrong') ?: 'Unable to create online shop. Please try again.';
            if ($request->is('api/*') || $request->ajax()) {
                return response()->json(['status' => false, 'message' => $message], 500);
            }
            return redirect()->back()->withInput()->withErrors($message);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(ClientDataTable $dataTable,  WalletHistoryDataTable $wallethistorydatatable,ClaimsDataTable $claimsdataTable, RatingDataTable $ratingdatatable, $id)
    {
        if (!auth()->user()->can('users-show')) {
            $message = __('message.demo_permission_denied');
            return redirect()->back()->withErrors($message);
        }
        $user = User::where('id', $id)->first();
        $pageTitle = $user->name ?: __('message.online_shop');
        $data = User::findOrFail($id);
        $profileImage = getSingleMedia($data, 'profile_image');
        $type = request('type') ?? 'detail';

        switch ($type) {
            case 'detail':
                $bank_detail = $user->userBankAccount()->orderBy('id', 'desc')->paginate(10);
                $bank_detail_items = UserDetailResource::collection($bank_detail);

                return $dataTable->with($id)->render('users.show', compact('pageTitle', 'type', 'data','bank_detail','bank_detail_items','user'));
                break;

            case 'wallethistory':
                $wallet_history = $user->userWalletHistory()->get();
                $wallet_history_items = WalletHistoryResource::collection($wallet_history);
                $earning_detail = User::select('id', 'name')->withTrashed()->where('id', $user->id)
                    ->with([
                        'userWallet:total_amount,total_withdrawn',
                        'getPayment:order_id,admin_commission'
                    ])
                    ->withCount([
                        'deliveryManOrder as total_order',
                        'getPayment as paid_order' => function ($query) {
                            $query->where('payment_status', 'paid');
                        }
                    ])
                    ->withSum('userWallet', 'total_amount')
                    ->withSum('userWallet', 'total_withdrawn')
                    ->first();
                return $wallethistorydatatable->with('id', $id)->render('users.show', compact('pageTitle', 'type', 'data', 'wallet_history', 'wallet_history_items','earning_detail'));
                break;

                case 'orderhistory':
                    $order = Order::where('client_id', $id)->get();
                    return view('users.show', compact('pageTitle', 'data', 'type', 'order'));
                break;
                case 'withdrawrequest':
                    $wallte = Wallet::where('user_id',$id)->first();
                    $withdraw = WithdrawRequest::where('user_id', $id)->get();
                    return view('users.show', compact('pageTitle', 'data', 'type', 'withdraw','wallte'));
                break;
                case 'useraddress':
                    $userAddresses = UserAddress::where('user_id', $id)->get();
                    return view('users.show', compact('pageTitle', 'data', 'type', 'userAddresses'));
                break;
                case 'claimsinfo':
                    $claims = Claims::where('client_id',  $id)->get();
                    return view('users.show', compact('pageTitle', 'data', 'type', 'claims'));
                break;
                case 'rating':
                    return $ratingdatatable->with(['delivery_man_id' => $id])->render('users.show', compact('pageTitle', 'data', 'type'));
                    break;
            default:
                break;
        }
        return $dataTable->with($id)->render('users.show', compact('pageTitle', 'data', 'id', 'type', 'profileImage'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!auth()->user()->can('users-edit')) {
            $message = __('message.demo_permission_denied');
            return redirect()->back()->withErrors($message);
        }
        $pageTitle = __('message.update_form_title', ['form' => __('message.online_shop')]);
        $data = User::findOrFail($id);
        $profileImage = getSingleMedia($data, 'profile_image');
        $assets = ['phone'];

        return view('users.form', compact('data', 'pageTitle', 'id', 'assets', 'profileImage'));
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
        if (!auth()->user()->can('users-edit')) {
            $message = __('message.demo_permission_denied');
            return redirect()->back()->withErrors($message);
        }
        $user = User::findOrFail($id);

        $user->removeRole($user->user_type);
        $message = __('message.not_found_entry', ['name' => __('message.online_shop')]);
        if ($user == null) {
            return json_custom_response(['status' => false, 'message' => $message]);
        }

        $request->validate([
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        $existingProfile = is_array($user->os_profile) ? $user->os_profile : [];
        $osProfile = array_merge($existingProfile, prepareUserOsProfileFromRequest($request));
        $payload = $request->only(['name']);
        $payload['address'] = buildUserAddressFromProfile($osProfile);
        $payload['os_profile'] = $osProfile;

        if ($request->filled('approval_status') && in_array($request->approval_status, User::approvalStatuses(), true)) {
            $user->applyApprovalStatus($request->approval_status);
            $payload['approval_status'] = $user->approval_status;
            $payload['status'] = $user->status;
        }

        if ($request->filled('password')) {
            $payload['password'] = bcrypt($request->password);
            $payload['is_temp_password'] = 0;
        }

        $user->fill($payload)->update();

        if ($request->hasFile('profile_image')) {
            uploadMediaFile($user, $request->profile_image, 'profile_image');
        }

        $user->assignRole($user->user_type);

        $message = __('message.update_form', ['form' => __('message.online_shop')]);
        if ($request->is('api/*')) {
            return json_message_response($message);
        }
        return redirect()->route('users.index')->withSuccess($message);
    }

    public function updateApprovalStatus(Request $request, $id)
    {
        if (! auth()->user()->can('users-edit')) {
            return response()->json([
                'status' => false,
                'message' => __('message.demo_permission_denied'),
            ], 403);
        }

        $request->validate([
            'approval_status' => 'required|in:pending,approved,rejected',
        ]);

        $user = User::where('id', $id)->where('user_type', 'client')->first();
        if (! $user) {
            return response()->json([
                'status' => false,
                'message' => __('message.not_found_entry', ['name' => __('message.online_shop')]),
            ], 404);
        }

        $previous = (string) ($user->approval_status ?? '');
        $user->applyApprovalStatus($request->approval_status);

        $plainPassword = null;
        $username = sanitizeRegistrationUsername((string) ($user->username ?? ''));
        if ($user->approval_status === User::APPROVAL_APPROVED && $previous !== User::APPROVAL_APPROVED) {
            if ($username === '') {
                $username = registrationUsernameFromPhone((string) ($user->contact_number ?? ''));
                if ($username !== '') {
                    $user->username = $username;
                }
            }
            $plainPassword = generateOsLoginPassword();
            $user->password = bcrypt($plainPassword);
            $user->is_temp_password = 1;
        }

        $user->save();

        if ($plainPassword && $username !== '') {
            try {
                app(AppPushService::class)->notifyOsAccountApproved($user->fresh(), $username, $plainPassword);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response()->json([
            'status' => true,
            'message' => __('message.approved_reject_form', [
                'form' => __('message.online_shop'),
                'status' => __('message.'.$user->approval_status),
            ]),
            'approval_status' => $user->approval_status,
            'label' => __('message.'.$user->approval_status),
            'username' => $plainPassword ? $username : null,
            'password' => $plainPassword,
        ]);
    }

    public function updateKyoShinFlag(Request $request, $id)
    {
        if (! auth()->user()->can('users-edit')) {
            return response()->json([
                'status' => false,
                'message' => __('message.demo_permission_denied'),
            ], 403);
        }

        $request->validate([
            'is_kyo_shin' => 'required|boolean',
        ]);

        $user = User::where('id', $id)->where('user_type', 'client')->first();
        if (! $user) {
            return response()->json([
                'status' => false,
                'message' => __('message.not_found_entry', ['name' => __('message.online_shop')]),
            ], 404);
        }

        $user->is_kyo_shin = (bool) $request->boolean('is_kyo_shin');
        $user->save();

        return response()->json([
            'status' => true,
            'message' => $user->is_kyo_shin
                ? __('message.kyo_shin_os_flag_enabled')
                : __('message.kyo_shin_os_flag_disabled'),
            'is_kyo_shin' => (bool) $user->is_kyo_shin,
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
        if (!auth()->user()->can('users-delete')) {
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
            return redirect()->route('users.index')->withErrors($message);
        }
        $user = User::where('user_type', 'client')->find($id);
        if ($user == null) {
            $message = __('message.not_found_entry', ['name' => __('message.online_shop')]);
            if (request()->ajax()) {
                return response()->json(['status' => false, 'message' => $message]);
            }
            return redirect()->back()->withErrors($message);
        }

        $this->scrubAndHideOnlineShop($user);
        $message = __('message.delete_form', ['form' => __('message.online_shop')]);

        if (request()->ajax()) {
            return response()->json(['status' => true, 'message' => $message]);
        }

        return redirect()->back()->withSuccess($message);
    }

    /**
     * Anonymize PII and soft-delete so the shop disappears from every active list.
     * Keeping deleted_at=null after rename was leaving "Deleted X Client" rows visible.
     */
    private function scrubAndHideOnlineShop(User $user): void
    {
        $now = now();
        $userId = $user->id;
        $stamp = $now->format('YmdHis');

        $user->forceFill([
            'name' => 'Deleted '.$userId.' client',
            'username' => 'deleted_'.$userId.'_'.$stamp,
            'address' => null,
            'email' => $stamp.$userId.'@deleted.com',
            'contact_number' => $now->format('ymdHis').$userId,
            'os_profile' => null,
            'status' => 0,
            'approval_status' => User::APPROVAL_REJECTED,
        ])->save();

        $user->userBankAccount()->delete();
        $user->userAddress()->delete();

        if (! $user->trashed()) {
            $user->delete();
        }
    }

    public function action(Request $request)
    {
        $id = $request->id;
        $users = User::withTrashed()->where('id', $id)->first();

        $message = __('message.not_found_entry', ['name' => __('message.online_shop')]);
        if ($request->type === 'restore') {
            // Restoring scrubbed shops is not supported — they stay hidden.
            if ($users && preg_match('/^Deleted\s+\d+\s+client$/i', (string) $users->name)) {
                $message = __('message.not_found_entry', ['name' => __('message.online_shop')]);
            } else {
                $users->restore();
                $message = __('message.msg_restored', ['name' => __('message.online_shop')]);
            }
        }

        if ($request->type === 'forcedelete') {
            if(env('APP_DEMO')){
                $message = __('message.demo_permission_denied');
                if(request()->is('api/*')){
                    return response()->json(['status' => true, 'message' => $message ]);
                }
                if(request()->ajax()) {
                    return response()->json(['status' => false, 'message' => $message, 'event' => 'validation']);
                }
                return redirect()->route('users.index')->withErrors($message);
            }
            if ($users) {
                $this->scrubAndHideOnlineShop($users);
                $message = __('message.delete_form', ['form' => __('message.online_shop')]);
            }
        }
        if (request()->is('api/*')) {
            return json_custom_response(['message' => $message, 'status' => true]);
        }

        if (request()->ajax()) {
            return response()->json(['status' => true, 'message' => $message]);
        }

        return redirect()->back()->withSuccess($message);
    }

    public function userdelete(Request $request){
        $users = User::withTrashed()->where('id',  $request->id)->first();
        if ($users) {
            $now = now();
            $usersId = $users->id;
            $systemTime = $now->format('YmdHis');

            $users->forceFill([
                'name' => 'Deleted ' . $usersId . ' User',
                'username' => 'Deleted ' . $usersId . ' User',
                'address' => null,
                'email' => $systemTime . $usersId . '@deleted.com',
                'contact_number' => $now->format('ymdHis') . $usersId,
                'deleted_at' => null,
            ])->save();

            $users->userBankAccount()->delete();
            $users->userAddress()->delete();
        }
         $message = __('message.account_deleted');

        return json_message_response($message);
    }
    public function frontendclientstore(UserRequest $request)
    {
        if (User::where('email', $request->email)->exists()) {
            $notification = [
                'message' => 'Email already exists',
                'alert-type' => 'error'
            ];
            return redirect()->back()->with($notification);
        }

        $request['password'] = bcrypt($request->password);
        $request['username'] = $request->username ?? stristr($request->email, "@", true) . rand(100, 1000);
        $request['display_name'] = $request['name'];
        $request['user_type'] = 'client';
        $request['approval_status'] = User::APPROVAL_PENDING;
        $request['status'] = 0;
        $request['created_by_admin'] = 0;

        $result = User::create($request->all());
        $result->assignRole($request->user_type);
        $message = __('message.os_signup_pending_approval');
        if ($request->is('api/*')) {
            return json_message_response($message);
        }
        $notification = array(
            'message' => $message,
            'alert-type' => 'success'
        );

        return redirect()->route('admin-login')->with($notification);
    }

    public function userExcel()
    {
        return view('users.excel');
    }

    public function downloadUsersReport(Request $request, $fileType = 'xlsx')
    {
        $startDate = $request->input('from_date');
        $endDate   = $request->input('to_date');

        $start = $startDate ? Carbon::parse($startDate)->format('Y-m-d') : null;
        $end   = $endDate ? Carbon::parse($endDate)->format('Y-m-d') : null;

        $userData = User::where('user_type','client')->when($startDate, fn($q) => $q->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('created_at', '<=', $endDate))
            ->get();

        $export = new UsersExport($userData, $request);
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

        $filename = "users-report{$filenameDatePart}.{$fileType}";

        $format = match (strtolower($fileType)) {
            'csv'  => \Maatwebsite\Excel\Excel::CSV,
            'xls'  => \Maatwebsite\Excel\Excel::XLS,
            'ods'  => \Maatwebsite\Excel\Excel::ODS,
            'html' => \Maatwebsite\Excel\Excel::HTML,
            default => \Maatwebsite\Excel\Excel::XLSX,
        };

        return Excel::download($export, $filename, $format);
    }


    public function downloadUsersPdf(Request $request)
    {
        $startDate = $request->input('from_date');
        $endDate   = $request->input('to_date');

        $userData = User::where('user_type','client')->when($startDate, fn($q) => $q->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('created_at', '<=', $endDate))
            ->get();

        $export = new UsersExport($userData, $request);
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

        $htmlContent = '<h1>Users Report</h1>';
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
        $filename = 'Users-report' . $filenameDatePart . '.pdf';

        return $pdf->download($filename);
    }
}
