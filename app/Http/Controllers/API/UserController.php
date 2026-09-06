<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Http\Resources\DeliveryManEarningResource;
use App\Http\Resources\OrderResource;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\PermissionResource;
use App\Http\Resources\UserDetailResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\WalletHistoryResource;
use App\Models\AppSetting;
use App\Models\City;
use App\Models\Country;
use App\Models\DeliveryManDocument;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Setting;
use App\Models\SMSSetting;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\VerificationCode;
use App\Models\Wallet;
use App\Notifications\EmailVerification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Twilio\Rest\Client;

class UserController extends Controller
{
    public function getKeyAndIV()
    {
        $keyUtf8 = config('api_encryption.secret_key');
        $ivUtf8 = config('api_encryption.vikey');

        if (empty($keyUtf8) || empty($ivUtf8)) {
            return;
        }

        $key = substr(hash('sha256', $keyUtf8, true), 0, 32);
        $iv = substr(hash('sha256', $ivUtf8, true), 0, 16);

        return [$key, $iv];
    }

    public function decryptData($encryptedBase64)
    {
        list($key, $iv) = $this->getKeyAndIV();
        $cipher = 'AES-256-CBC';

        if (!is_string($encryptedBase64) || trim($encryptedBase64) === '') {
            return '';
        }

        $encrypted = base64_decode(trim($encryptedBase64));

        $decrypted = openssl_decrypt($encrypted, $cipher, $key, OPENSSL_RAW_DATA, $iv);

        return $decrypted !== false ? $decrypted : '';
    }

    public function encryptData($plainText)
    {
        list($key, $iv) = $this->getKeyAndIV();
        $cipher = 'AES-256-CBC';
        $encrypted = openssl_encrypt((string) $plainText, $cipher, $key, OPENSSL_RAW_DATA, $iv);

        return base64_encode($encrypted);
    }

    public function newlogin(Request $request)
    {
        if (empty($this->getKeyAndIV())) {
            return response()->json(['error' => __('message.key_value_set')], 404);
        }

        $decryptedEmail = $this->decryptData($request->input('email'));
        $decryptedPassword = $this->decryptData($request->input('password'));
        $decryptedPlayerId = $this->decryptData($request->input('player_id'));
        $decryptedfcmToken = $this->decryptData($request->input('fcm_token'));
        $decryptedUsername = $request->has('username') ? $this->decryptData($request->input('username')) : '';

        $loginValue = trim($decryptedUsername !== '' ? $decryptedUsername : $decryptedEmail);
        $normalizedPhone = $loginValue !== '' ? normalizeContactNumber($loginValue) : '';

        $user = User::where(function ($query) use ($loginValue, $normalizedPhone) {
            $query->where('username', $loginValue)
                ->orWhere('email', $loginValue)
                ->orWhere('contact_number', $loginValue);
            if ($normalizedPhone !== '' && $normalizedPhone !== $loginValue) {
                $query->orWhere('username', $normalizedPhone)
                    ->orWhere('contact_number', $normalizedPhone);
            }
        })->first();

        if ($user && \Hash::check($decryptedPassword, $user->password)) {
            if ($user->user_type === 'client' && ! $user->isClientApprovalApproved()) {
                $approval = $user->approval_status ?? User::APPROVAL_PENDING;
                $message = $approval === User::APPROVAL_REJECTED
                    ? __('message.os_account_rejected')
                    : __('message.os_account_pending_approval');

                return json_message_response($message, 403);
            }

            Auth::login($user);
            if ($decryptedPlayerId != null) {
                $user->player_id = $decryptedPlayerId;
            }

            if ($decryptedfcmToken != null) {
                $user->fcm_token = $decryptedfcmToken;
            }
            $user->last_actived_at = now();
            $user->save();

            return json_custom_response(['data' => $this->buildLoginPayload($user)], 200);
        } else {
            $message = __('auth.failed');
            return json_message_response($message, 402);
        }
    }

    public function newregister(Request $request)
    {
        if (empty($this->getKeyAndIV())) {
            return response()->json([
                'errors' => ['message' => __('message.key_value_set')],
            ], 404);
        }
        $decryptedData = [
            'name' => $this->decryptData($request->input('name')),
            'username' => $this->decryptData($request->input('username')),
            'user_type' => $this->decryptData($request->input('user_type')),
            'contact_number' => $this->decryptData($request->input('contact_number')),
            'email' => $request->has('email') ? $this->decryptData($request->input('email')) : '',
            'password' => $this->decryptData($request->input('password')),
            'player_id' => $request->has('player_id') ? $this->decryptData($request->input('player_id')) : '',
            'partner_referral_code' => $request->has('partner_referral_code') ? $this->decryptData($request->input('partner_referral_code')) : '',
            'branch_id' => $request->has('branch_id') ? $this->decryptData($request->input('branch_id')) : null,
            'date_of_birth' => $request->has('date_of_birth') ? $this->decryptData($request->input('date_of_birth')) : '',
            'address_unit' => $request->has('address_unit') ? $this->decryptData($request->input('address_unit')) : '',
            'state_division' => $request->has('state_division') ? $this->decryptData($request->input('state_division')) : '',
            'township' => $request->has('township') ? $this->decryptData($request->input('township')) : '',
            'street' => $request->has('street') ? $this->decryptData($request->input('street')) : '',
            'nrc' => $request->has('nrc') ? $this->decryptData($request->input('nrc')) : '',
            'kpay_name' => $request->has('kpay_name') ? $this->decryptData($request->input('kpay_name')) : '',
            'kpay_no' => $request->has('kpay_no') ? $this->decryptData($request->input('kpay_no')) : '',
        ];

        $user_id = $request->route('users') ?? null;

        // Validation Rules
        $isClient = ($decryptedData['user_type'] ?? '') === 'client';
        $isDeliveryMan = ($decryptedData['user_type'] ?? '') === 'delivery_man';
        $validator = Validator::make($decryptedData, [
            'name' => ($isClient || $isDeliveryMan) ? 'required|string|max:255' : 'sometimes|nullable|string|max:255',
            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^[A-Za-z0-9._-]+$/',
                function ($attribute, $value, $fail) {
                    if (User::where('username', $value)->exists()) {
                        $fail(__('message.username_taken'));
                    }
                },
            ],
            'email' => $isDeliveryMan
                ? 'required|email|unique:users,email,'.$user_id
                : 'sometimes|nullable|email|unique:users,email,'.$user_id,
            'contact_number' => ($isClient || $isDeliveryMan)
                ? 'required|max:20|unique:users,contact_number,'.$user_id
                : 'sometimes|required|max:20|unique:users,contact_number,'.$user_id,
            'password' => ($isClient || $isDeliveryMan) ? 'required|string|min:6' : 'sometimes|required|string|min:6',
            'branch_id' => $isDeliveryMan ? 'required|exists:branches,id' : 'sometimes|nullable|exists:branches,id',
            'date_of_birth' => 'sometimes|nullable|date',
            'address_unit' => $isClient ? 'required|string|max:255' : 'sometimes|nullable|string|max:255',
            'state_division' => $isClient ? 'required|string|max:255' : 'sometimes|nullable|string|max:255',
            'township' => $isClient ? 'required|string|max:255' : 'sometimes|nullable|string|max:255',
            'street' => 'sometimes|nullable|string|max:255',
            'nrc' => 'sometimes|nullable|string|max:100',
            'kpay_name' => $isClient ? 'required|string|max:255' : 'sometimes|nullable|string|max:255',
            'kpay_no' => $isClient ? 'required|string|max:50' : 'sometimes|nullable|string|max:50',
        ], [
            'username.regex' => __('message.username_invalid'),
            'username.min' => __('message.username_invalid'),
        ]);

        // Handle Validation Failure
        if ($validator->fails()) {
            return json_custom_response([ 'message' => $validator->errors()->first(), 'success' => false ], 409);
        }

        $input = $decryptedData;

        $is_email_verification = SettingData('email_verification', 'email_verification');
        $is_mobile_verification = SettingData('mobile_verification', 'mobile_verification');
        $is_document_verification = SettingData('document_verification', 'document_verification');
        $allowDeliverymanSetting = SettingData('allow_deliveryman', 'allow_deliveryman');
        $is_allow_deliveryman = !($allowDeliverymanSetting !== null && $allowDeliverymanSetting !== '' && (int) $allowDeliverymanSetting === 0);

        if (empty($input['name'])) {
            $input['name'] = $input['contact_number'] ?? $input['email'];
        }

        $phone = $input['contact_number'] ?? '';
        $input['username'] = sanitizeRegistrationUsername($input['username'] ?? '')
            ?: registrationUsernameFromPhone($phone);
        $input['email'] = $input['email'] ?: registrationEmailFromPhone($phone);
        $input['os_profile'] = buildUserOsProfileFromArray($input);
        $input['address'] = buildUserAddressFromProfile($input['os_profile']);

        if ($isDeliveryMan && ! empty($input['branch_id'])) {
            $input['branch_id'] = (int) $input['branch_id'];
        } else {
            unset($input['branch_id']);
        }

        unset(
            $input['date_of_birth'],
            $input['address_unit'],
            $input['state_division'],
            $input['township'],
            $input['street'],
            $input['nrc']
        );

        $input['user_type'] = $input['user_type'] ?? 'client';

        if ($input['user_type'] === 'delivery_man' && !$is_allow_deliveryman) {
            return json_custom_response([ 'message' => __('message.demo_permission_denied'), 'success' => false ], 403);
        }

        if (!empty($input['partner_referral_code'])) {
            $referral_code_exists = User::where('referral_code', $input['partner_referral_code'])->exists();
            if (!$referral_code_exists) {
                return json_custom_response([ 'message' => __('message.invalid_referral_code'), 'success' => false ], 400);
            }
        }
        unset($input['partner_referral_code']);

        $input['password'] = Hash::make($input['password']);

        if ($is_document_verification == 0) {
            $input['document_verified_at'] = now();
            $input['is_autoverified_document'] = 1;
        }

        if ($is_email_verification == 0) {
            $input['email_verified_at'] = now();
            $input['is_autoverified_email'] = 1;
        }

        $regGroup = ($input['user_type'] ?? 'client') === 'delivery_man'
            ? 'driver_registration_setting'
            : 'user_registration_setting';
        $regMobileVerification = registrationSettingValue($regGroup, 'mobile_verification');
        if ($regMobileVerification === null || $regMobileVerification === '') {
            $regMobileVerification = $is_mobile_verification;
        }

        // Skip OTP for mobile registration — phone + password auth only.
        $input['otp_verify_at'] = now();
        $input['is_autoverified_mobile'] = 1;

        $input['referral_code'] = generateRandomCode();

        // App/Website OS self-signup waits for admin approval before login works.
        if (($input['user_type'] ?? '') === 'client') {
            $input['approval_status'] = User::APPROVAL_PENDING;
            $input['status'] = 0;
            $input['created_by_admin'] = 0;
        }

        $user = User::create($input);

        if ($user->user_type === 'delivery_man' && ! empty($input['contact_number'])) {
            $user->daily_contact_number = normalizeContactNumber($input['contact_number']);
            $user->daily_contact_date = \Carbon\Carbon::now('Asia/Yangon')->toDateString();
            $user->save();
        }

        createDefaultUserAddressFromRegistration($user, $input['contact_number'] ?? null);

        if (SettingData('order_mail', 'register_mail') == 1 && $is_email_verification == 1) {
            $user->notify(new EmailVerification($user));
        }

        $user->assignRole($input['user_type']);

        if ($request->has('user_detail') && $request->user_detail != null) {
            $user->userDetail()->create($request->user_detail);
        }

        if ($request->has('user_bank_account') && $request->user_bank_account != null) {
            $user->userBankAccount()->create($request->user_bank_account);
        }

        if ($user->user_type === 'client' && ($user->approval_status ?? '') === User::APPROVAL_PENDING) {
            return json_custom_response([
                'message' => __('message.os_signup_pending_approval'),
                'success' => true,
                'approval_status' => User::APPROVAL_PENDING,
                'is_email_verification' => ($is_email_verification == 0) ? true : false,
                'is_mobile_verification' => ($is_mobile_verification == 0) ? true : false,
                'is_document_verification' => ($is_document_verification == 0) ? true : false,
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'user_type' => $user->user_type,
                    'approval_status' => $user->approval_status,
                    'status' => (int) $user->status,
                ],
            ]);
        }

        $message = __('message.save_form', ['form' => __('message.'.$input['user_type'])]);
        $user->api_token = $user->createToken('auth_token')->plainTextToken;
        $user->profile_image = getSingleMedia($user, 'profile_image', null);

        $user_detail = User::where('id', $user->id)->first();
        $user->otp_verify_at = $user_detail->otp_verify_at ?? null;
        $user->email_verified_at = $user_detail->email_verified_at ?? null;
        $user->document_verified_at = $user_detail->document_verified_at ?? null;
        $user->status = $user_detail->status ?? 1;
        $user->approval_status = $user_detail->approval_status ?? User::APPROVAL_APPROVED;

        return json_custom_response([
            'message' => $message,
            'is_email_verification' => ($is_email_verification == 0) ? true : false,
            'is_mobile_verification' => ($is_mobile_verification == 0) ? true : false,
            'is_document_verification' => ($is_document_verification == 0) ? true : false,
            'data' => $user,
        ]);
    }

    public function userList(Request $request)
    {
        $user_type = isset($request['user_type']) && in_array($request['user_type'], ['client', 'delivery_man']) ? $request['user_type'] : 'client';

        $user_list = User::query();

        $user_list->when(request('user_type'), function ($q) use ($user_type) {
            return $q->where('user_type', $user_type);
        });

        $user_list->when(request('country_id'), function ($q) {
            return $q->where('country_id', request('country_id'));
        });

        $user_list->when(request('city_id'), function ($q) {
            return $q->where('city_id', request('city_id'));
        });

        if ($request->has('status') && isset($request->status)) {
            $user_list = $user_list->where('status', request('status'));
        }

        if ($request->user_status) {
            switch ($request->user_status) {
                case 'active':
                    $model = $user_list->where('status', 1)->whereNull('deleted_at')
                        ->whereNotNull('email_verified_at')
                        ->whereNotNull('otp_verify_at');
                    if ($request->user_type == 'delivery_man') {
                        $model = $model->whereNotNull('document_verified_at');
                    }
                    break;

                case 'inactive':
                    $model = $user_list->where('status', 0);
                    break;

                case 'pending':
                    $model = $user_list->where('status', 1)->where(function ($query) use ($request) {
                        $query->where('is_autoverified_email', 0)
                            ->whereNull('email_verified_at')
                            ->orWhere('is_autoverified_mobile', 0)
                            ->whereNull('otp_verify_at');
                        if ($request->user_type == 'delivery_man') {
                            $query->orWhere('is_autoverified_document', 0)
                                  ->whereNull('document_verified_at');
                        }
                    });
                    break;

                default:
                    break;
            }
        }

        if ($request->has('is_deleted') && isset($request->is_deleted) && $request->is_deleted) {
            $user_list = $user_list->withTrashed();
        }
        if (request('is_admin') == '1') {
            $per_page = $user_list->count();
        } else {
            $per_page = config('constant.PER_PAGE_LIMIT');
            if ($request->has('per_page') && !empty($request->per_page)) {
                if (is_numeric($request->per_page)) {
                    $per_page = $request->per_page;
                }
                if ($request->per_page == -1) {
                    $per_page = $user_list->count();
                }
            }
        }

        $user_list = $user_list->orderBy('name', 'asc')->paginate($per_page);

        $items = UserResource::collection($user_list);

        $response = [
            'pagination' => json_pagination_response($items),
            'data' => $items,
        ];

        return json_custom_response($response);
    }

    public function userFilterList(Request $request)
    {
        $params = $request->search;
        $user_list = User::where('name', 'LIKE', '%'.$params.'%');

        $user_type = $request->has('user_type') ? request('user_type') : 'client';

        $user_list->when(request('user_type'), function ($q) use ($user_type) {
            return $q->where('user_type', $user_type);
        });

        if ($request->has('is_deleted') && isset($request->is_deleted) && $request->is_deleted) {
            $user_list = $user_list->withTrashed();
        }

        $per_page = config('constant.PER_PAGE_LIMIT');
        if ($request->has('per_page') && !empty($request->per_page)) {
            if (is_numeric($request->per_page)) {
                $per_page = $request->per_page;
            }
            if ($request->per_page == -1) {
                $per_page = $user_list->count();
            }
        }

        $user_list = $user_list->paginate($per_page);

        $items = UserResource::collection($user_list);

        $response = [
            'pagination' => json_pagination_response($items),
            'data' => $items,
        ];

        return json_custom_response($response);
    }

    public function userDetail(Request $request)
    {
        $id = $request->id;

        $user = User::where('id', $id)->withTrashed()->first();
        if (empty($user)) {
            $message = __('message.not_found_entry',['name' =>__('message.user')]);

            return json_message_response($message, 400);
        }

        $user_detail = new UserResource($user);

        $response = [ 'data' => $user_detail ];

        return json_custom_response($response);
    }

    public function changePassword(Request $request)
    {
        $user = User::where('id', Auth::user()->id)->first();

        if ($user == '') {
            $message = __('message.not_found_entry',['name' =>__('message.user')]);

            return json_message_response($message, 400);
        }

        $hashedPassword = $user->password;

        $match = Hash::check($request->old_password, $hashedPassword);

        $same_exits = Hash::check($request->new_password, $hashedPassword);
        if ($match) {
            if ($same_exits) {
                $message = __('message.old_new_pass_same');

                return json_message_response($message, 400);
            }

            $user->fill([
                'password' => Hash::make($request->new_password),
                'is_temp_password' => 0,
            ])->save();

            $message = __('message.password_change');

            return json_message_response($message, 200);
        } else {
            $message = __('message.valid_password');

            return json_message_response($message, 400);
        }
    }

    public function updateProfile(UserRequest $request)
    {
        $user = Auth::user();

        $userReferralCode = User::where('id', $request->id)->first();
        if ($userReferralCode->referral_code == null) {
            $request['referral_code'] = generateRandomCode();
        }
        if ($request->has('id') && !empty($request->id)) {
            $user = User::where('id', $request->id)->first();
        }
        if ($user == null) {
            return json_message_response(__('message.no_record_found'), 400);
        }

        $user->fill($request->all())->update();

        if (isset($request->profile_image) && $request->profile_image != null) {
            $user->clearMediaCollection('profile_image');
            $user->addMediaFromRequest('profile_image')->toMediaCollection('profile_image');
        }

        $user_data = User::find($user->id);
        if ($user_data->userBankAccount != null && $request->has('user_bank_account')) {
            $user_data->userBankAccount->fill($request->user_bank_account)->update();
        } elseif ($request->has('user_bank_account') && $request->user_bank_account != null) {
            $user_data->userBankAccount()->create($request->user_bank_account);
        }

        $message = __('message.updated');
        unset($user_data['media']);

        $user_resource = new UserResource($user_data);

        $response = [
            'data' => $user_resource,
            'message' => $message,
        ];

        return json_custom_response($response);
    }

    /**
     * Rider App — set today's working phone (resets next calendar day Asia/Yangon).
     */
    public function updateDailyContact(Request $request)
    {
        $user = Auth::user();
        if (! $user || $user->user_type !== 'delivery_man') {
            return json_message_response(__('message.demo_permission_denied'), 403);
        }

        $raw = $request->input('daily_contact_number')
            ?? $request->input('contact_number')
            ?? '';

        if ($request->has('daily_contact_number') && method_exists($this, 'decryptData') && ! empty($this->getKeyAndIV())) {
            $maybeEncrypted = $request->input('daily_contact_number');
            $decrypted = $this->decryptData($maybeEncrypted);
            if (! empty($decrypted)) {
                $raw = $decrypted;
            }
        }

        $phone = normalizeContactNumber(trim((string) $raw));
        if ($phone === '') {
            return json_custom_response([
                'message' => __('message.please_enter_contact_number'),
                'success' => false,
            ], 422);
        }

        $user->daily_contact_number = $phone;
        $user->daily_contact_date = \Carbon\Carbon::now('Asia/Yangon')->toDateString();
        $user->save();

        return json_custom_response([
            'message' => __('message.updated'),
            'success' => true,
            'data' => [
                'daily_contact_number' => $user->daily_contact_number,
                'daily_contact_date' => $user->daily_contact_date
                    ? \Carbon\Carbon::parse($user->daily_contact_date)->toDateString()
                    : null,
                'needs_daily_contact' => ! $user->hasDailyContactForToday(),
                'assigned_phone' => $user->riderAssignedPhone(),
                'contact_number' => $user->contact_number,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($request->is('api*')) {
            $clear = request('clear');
            if ($clear != null) {
                $user->$clear = null;
            }
            $user->fcm_token = null;
            $user->save();

            return json_message_response('Logout successfully');
        }
    }

    public function newforgetPassword(Request $request)
    {
        if (empty($this->getKeyAndIV())) {
            return response()->json(['error' => __('message.key_value_set')], 404);
        }

        $decryptedEmail = $this->decryptData($request->input('email'));

        $request->merge(['email' => $decryptedEmail]);

        $request->validate([
            'email' => 'required|email',
        ]);

        $response = Password::sendResetLink(
            ['email' => $decryptedEmail]
        );

        return $response == Password::RESET_LINK_SENT
            ? response()->json(['message' => __($response), 'status' => true], 200)
            : response()->json(['message' => __($response), 'status' => false], 400);
    }

    public function updateUserStatus(Request $request)
    {
        $user_id = $request->id ?? auth()->user()->id;

        $user = User::where('id', $user_id)->first();

        if ($user == '') {
            $message = __('message.not_found_entry',['name' =>__('message.user')]);

            return json_message_response($message, 400);
        }
        if ($request->has('status')) {
            $user->status = $request->status;
        }

        if ($request->has('uid')) {
            $user->uid = $request->uid;
        }

        if ($request->has('latitude')) {
            $user->latitude = $request->latitude;
        }
        if ($request->has('longitude')) {
            $user->longitude = $request->longitude;
        }

        if ($request->has('country_id')) {
            $user->country_id = $request->country_id;
        }

        if ($request->has('city_id')) {
            $user->city_id = $request->city_id;
        }

        if ($request->has('app_version')) {
            $user->app_version = $request->app_version;
        }

        if ($request->has('app_source')) {
            $user->app_source = $request->app_source;
        }

        if ($request->has('last_actived_at')) {
            $user->last_actived_at = $request->last_actived_at;
        }

        if ($request->has('latitude') && $request->has('longitude')) {
            $user->last_location_update_at = date('Y-m-d H:i:s');
        }

        if ($request->has('player_id')) {
            $user->player_id = $request->player_id;
        }

        if ($request->has('fcm_token')) {
            $token = trim((string) $request->fcm_token);
            $user->fcm_token = $token !== '' ? $token : null;
        }

        if ($request->has('otp_verify_at')) {
            $user->otp_verify_at = $request->otp_verify_at;
        }

        if ($request->has('partner_referral_code')) {
            $user->partner_referral_code = $request->partner_referral_code;
        }

        if ($request->has('document_verified_at')) {
            $user->document_verified_at = $request->document_verified_at;
        }
        if ($request->has('is_autoverified_document')) {
            $user->is_autoverified_document = $request->is_autoverified_document;
        }
        if ($request->has('is_autoverified_email')) {
            $user->is_autoverified_email = $request->is_autoverified_email;
        }
        if ($request->has('is_autoverified_mobile')) {
            $user->is_autoverified_mobile = $request->is_autoverified_mobile;
        }

        $user->save();
        syncUserDefaultAddressLocation($user);
        $message = __('message.update_form', ['form' => __('message.status')]);
        $response = [
            'message' => $message,
        ];

        return json_custom_response($response);
    }

    public function updateAppSetting(Request $request)
    {
        $data = $request->all();
        AppSetting::updateOrCreate(['id' => $request->id], $data);
        $message = __('message.save_form', ['form' => __('message.app_setting')]);
        $response = [
            'data' => AppSetting::first(),
            'message' => $message,
        ];

        return json_custom_response($response);
    }

    public function getAppSetting(Request $request)
    {
        if ($request->has('id') && isset($request->id)) {
            $data = AppSetting::where('id', $request->id)->first();
        } else {
            $data = AppSetting::first();
        }
        $data['reference_amount'] = SettingData('reference_amount', 'reference_amount');

        $data['reference_type'] = SettingData('reference_type', 'reference_type');
        $data['max_earning_per_month'] = SettingData('max_earning_per_month', 'max_earning_per_month');

        $insuranceallow = Setting::where('type', 'insurance_allow')->where('key', 'insurance_allow')->first();
        $data['insurance_allow'] = $insuranceallow ? $insuranceallow->value : null;
        $insurancepercentage = Setting::where('type', 'insurance_percentage')->where('key', 'insurance_percentage')->first();
        $data['insurance_percentage'] = $insurancepercentage ? $insurancepercentage->value : null;
        $insurancedescription = Setting::where('type', 'insurance_description')->where('key', 'insurance_description')->first();
        $data['insurance_description'] = $insurancedescription ? $insurancedescription->value : null;
        $claim_duration = Setting::where('type', 'claim_duration')->where('key', 'claim_duration')->first();
        $data['claim_duration'] = $claim_duration ? $claim_duration->value : null;
        $data['privacy_policy'] = SettingData('privacy_policy', 'privacy_policy');
        $data['privacy_policy_rider'] = SettingData('privacy_policy_rider', 'privacy_policy_rider');
        $data['terms_condition'] = SettingData('terms_condition', 'terms_condition');
        $data['terms_condition_rider'] = SettingData('terms_condition_rider', 'terms_condition_rider');

        return json_custom_response($data);
    }

    public function deleteUserAccount(Request $request)
    {
        $id = auth()->id();
        $user = User::where('id', $id)->first();
        $message = __('message.not_found_entry', ['name' => __('message.account')]);

        if ($user != '') {
            $user->delete();
            $message = __('message.account_deleted');
        }

        return json_custom_response(['message' => $message, 'status' => true]);
    }

    public function multipleDeleteRecords(Request $request)
    {
        $multi_ids = $request->ids;
        $user_type = $request->user_type != null ? $request->user_type : 'client';
        $message = __('message.msg_fail_to_delete', ['item' => __('message.'.$user_type)]);

        foreach ($multi_ids as $id) {
            $user = User::withTrashed()->where('id', $id)->first();
            if ($user) {
                if ($user->deleted_at != null) {
                    $user->forceDelete();
                } else {
                    $user->delete();
                }
                $message = __('message.msg_deleted', ['name' => __('message.'.$user->user_type)]);
            }
        }

        return json_custom_response(['message' => $message, 'status' => true]);
    }

    public function commonUserDetail(Request $request)
    {
        $id = $request->id;

        $user = User::where('id', $id)->withTrashed()->first();
        if (empty($user)) {
            $message = __('message.not_found_entry',['name' =>__('message.user')]);

            return json_message_response($message, 400);
        }

        $user_detail = new UserDetailResource($user);

        $wallet_history = $user->userWalletHistory()->orderBy('id', 'desc')->paginate(10);
        $wallet_history_items = WalletHistoryResource::collection($wallet_history);
        $response = [
            'data' => $user_detail,
            'wallet_history' => [
                'pagination' => json_pagination_response($wallet_history_items),
                'data' => $wallet_history_items,
            ],
        ];
        if ($user->user_type == 'delivery_man') {
            $datas = User::select('id', 'name')->withTrashed()->where('id', $user->id)
                ->with(['userWallet:total_amount,total_withdrawn', 'getPayment:order_id,delivery_man_commission,admin_commission'])
                ->withCount([
                    'deliveryManOrder as total_order',
                    'getPayment as paid_order' => function ($query) {
                        $query->where('payment_status', 'paid');
                    },
                ])
                ->withSum('userWallet as wallet_balance', 'total_amount')
                ->withSum('userWallet as total_withdrawn', 'total_withdrawn')
                ->withSum('getPayment as delivery_man_commission', 'delivery_man_commission')
                ->withSum('getPayment as admin_commission', 'admin_commission')->first();

            $response['earning_detail'] = new DeliveryManEarningResource($datas);

            $earning_list = Payment::with('order')->withTrashed()->where('payment_status', 'paid')
                ->whereHas('order', function ($query) use ($user) {
                    $query->whereIn('status', ['completed', 'cancelled'])->where('delivery_man_id', $user->id);
                })->orderBy('id', 'desc')->paginate(10);

            $earning_list_items = PaymentResource::collection($earning_list);
            $response['earning_list']['pagination'] = json_pagination_response($earning_list_items);
            $response['earning_list']['data'] = $earning_list_items;
        }

        return json_custom_response($response);
    }

    public function newsocialLogin(Request $request)
    {
        if(empty($this->getKeyAndIV())) {
            return response()->json(['error' =>__('message.key_value_set')], 404);
        }

        $decryptedName = $this->decryptData($request->input('name'));
        $decryptedusername = $this->decryptData($request->input('username'));
        $decryptedUserType = $this->decryptData($request->input('user_type'));
        $decryptedContactNumber = $this->decryptData($request->input('contact_number'));
        $decryptedEmail = $this->decryptData($request->input('email'));
        $decryptedAccessToken = $this->decryptData($request->input('accessToken'));
        $decryptedLoginType = $this->decryptData($request->input('login_type'));
        $decryptedAppleIdentifier = $this->decryptData($request->input('apple_user_identifier'));

        $input['contact_number'] = $decryptedContactNumber;
        $input['name'] = $decryptedName;
        $input['email'] = $decryptedEmail;
        $input['username'] = $decryptedusername;
        $is_email_verification = SettingData('email_verification', 'email_verification');
        $is_mobile_verification = SettingData('mobile_verification', 'mobile_verification');
        $is_document_verification = SettingData('document_verification', 'document_verification');


        if(!empty($decryptedAppleIdentifier))
        {
           $user_data = User::where('apple_user_identifier', $request->apple_user_identifier)->where('email', $request->email)->where('user_type',request('user_type'))->first();
        } elseif ($decryptedLoginType === 'mobile') {
            $user_data = User::where('username', $decryptedusername)->where('login_type', 'mobile')->first();
        } else {
            $user_data = User::where('email', $decryptedEmail)->first();
        }

        if( $user_data != null ) {
            if( $user_data->user_type != $decryptedUserType ) {
                if(in_array($decryptedLoginType, [ 'google', 'apple'])) {
                    $message = __('validation.unique',['attribute' => 'email' ]);
                } else {
                    $message = __('validation.unique',['attribute' => 'username' ]);
                }
                return json_message_response($message,400);
            }
            $message = __('message.login_success');
        } else {

            $validator = Validator::make($input,[
                'email' => 'required|email|unique:users,email',
                'username'  => 'required|unique:users,username',
                'phone_number' => 'nullable|max:20|unique:users,phone_number',
            ]);

            if ( $validator->fails() ) {
                $data = [
                    'status' => false,
                    'message' => $validator->errors()->first(),
                    'all_message' =>  $validator->errors()
                ];

                return json_custom_response($data, 422);
            }

            if(in_array($decryptedLoginType, [ 'google', 'apple']))
            {
                $key = 'email';
                $value = $decryptedEmail;
            } else {
                $key = 'username';
                $value = $decryptedusername;
            }

            $trashed_user_data = User::where($key,$value)->whereNotNull('login_type')->withTrashed()->first();

            if ($trashed_user_data != null && $trashed_user_data->trashed())
            {
                if(in_array($decryptedLoginType, [ 'google', 'apple'])){
                    $message = __('validation.unique',['attribute' => 'email' ]);
                } else {
                    $message = __('validation.unique',['attribute' => 'username' ]);
                }
                return json_message_response($message,400);

                if($decryptedLoginType === 'mobile' && $user_data == null ){
                    $otp_response = [
                        'status' => true,
                        'is_user_exist' => false
                    ];
                    return json_custom_response($otp_response);
                }
            }

            $password = !empty($decryptedAccessToken) ? $decryptedAccessToken : $decryptedEmail;
            if (in_array($decryptedLoginType, ['google', 'apple'])) {
                if($is_email_verification == 0){
                    $input['email_verified_at'] = date('Y-m-d H:i:s');
                }else{
                    $input['email_verified_at'] = null;
                }
                if ($is_mobile_verification == 0) {
                    $input['otp_verify_at'] = date('Y-m-d H:i:s');
                }

                if ($decryptedUserType == 'delivery_man' && $is_document_verification == 0) {
                    $input['document_verified_at'] = date('Y-m-d H:i:s');
                }
            }

            $input['password'] = Hash::make($password);
            $input['user_type'] = isset($decryptedUserType) ? $decryptedUserType : 'client';
            $input['login_type'] = $decryptedLoginType;
            $user = User::create($input);

            $user_data = User::where('id',$user->id)->first();
            $message = __('message.save_form',['form' => $input['user_type'] ]);
        }
        $user_data['api_token'] = $user_data->createToken('auth_token')->plainTextToken;
        $user_data['profile_image'] = getSingleMedia($user_data,'profile_image',null);
        $response = [
            'status' => true,
            'message' => $message,
            'data' => $user_data
        ];
        return json_custom_response($response);
    }

    public function deleteUser(Request $request)
    {
        $user = User::find($request->id);

        $message = __('message.no_record_found');

        if ($user != '') {
            $user->delete();
            $message = __('message.msg_deleted', ['name' => __('message.'.$user->user_type)]);
        }

        if (request()->is('api/*')) {
            return json_custom_response(['message' => $message, 'status' => true]);
        }
    }

    public function verifyOTPForEmail(Request $request)
    {
        $user = auth()->user();
        $code = request('code');

        $verification = VerificationCode::where('user_id', $user->id)->where('code', $code)->first();
        $user->notify(new EmailVerification($code));
        if ($verification != null) {
            $diff_minute = calculateDuration($verification->datetime);

            if ($diff_minute >= 10) {
                $message = __('message.otp_expire');
                $status = 400;
            } else {
                $message = __('message.otp_verified');
                $status = 200;
                $user->update(['email_verified_at' => date('Y-m-d H:i:s')]);
            }
            $verification->delete();
        } else {
            $message = __('message.otp_invalid');
            $status = 400;
        }

        return json_message_response($message, $status);
    }

    public function resendOTPForEmail()
    {
        $user = auth()->user();
        if ($user->email_verified_at != null) {
            return json_message_response(__('message.email_is_verified'));
        }
        $user->notify(new EmailVerification($user));

        return json_message_response(__('message.otp_send'));
    }

    public function dashboard(Request $request)
    {
        $dashboard_data = [];
        $dashboard_data['total_country'] = Country::count();
        $dashboard_data['total_city'] = City::count();
        $dashboard_data['total_client'] = User::userCount('client');
        $dashboard_data['total_delivery_man'] = User::userCount('delivery_man');
        $dashboard_data['total_order'] = Order::myOrder()->count();
        $dashboard_data['today_register_user'] = User::where('user_type', 'client')->whereDate('created_at', today())->count();

        $total_compeleted_earning = Order::myOrder()->where('status', 'completed')->sum('total_amount');
        $total_cancelled_earning = Order::myOrder()->where('status', 'cancelled')->sum('total_amount');

        $dashboard_data['total_earning'] = $total_compeleted_earning + $total_cancelled_earning;
        $dashboard_data['total_cancelled_order'] = Order::myOrder()->where('status', 'cancelled')->count();

        $dashboard_data['total_create_order'] = Order::myOrder()->where('status', 'create')->count();
        $dashboard_data['total_active_order'] = Order::myOrder()->where('status', 'active')->count();
        $dashboard_data['total_delayed_order'] = Order::myOrder()->where('status', 'delayed')->count();
        $dashboard_data['total_courier_assigned_order'] = Order::myOrder()->where('status', 'courier_assigned')->count();
        $dashboard_data['total_courier_picked_up_order'] = Order::myOrder()->where('status', 'courier_picked_up')->count();
        $dashboard_data['total_courier_departed_order'] = Order::myOrder()->where('status', 'courier_departed')->count();
        $dashboard_data['total_courier_arrived_order'] = Order::myOrder()->where('status', 'courier_arrived')->count();
        $dashboard_data['total_completed_order'] = Order::myOrder()->where('status', 'completed')->count();
        $dashboard_data['total_failed_order'] = Order::myOrder()->where('status', 'failed')->count();

        $dashboard_data['today_create_order'] = Order::myOrder()->whereDate('created_at', today())->where('status', 'create')->count();
        $dashboard_data['today_active_order'] = Order::myOrder()->whereDate('created_at', today())->where('status', 'active')->count();
        $dashboard_data['today_delayed_order'] = Order::myOrder()->whereDate('created_at', today())->where('status', 'delayed')->count();
        $dashboard_data['today_cancelled_order'] = Order::myOrder()->whereDate('created_at', today())->where('status', 'cancelled')->count();
        $dashboard_data['today_courier_assigned_order'] = Order::myOrder()->whereDate('created_at', today())->where('status', 'courier_assigned')->count();
        $dashboard_data['today_courier_picked_up_order'] = Order::myOrder()->whereDate('created_at', today())->where('status', 'courier_picked_up')->count();
        $dashboard_data['today_courier_departed_order'] = Order::myOrder()->whereDate('created_at', today())->where('status', 'courier_departed')->count();
        $dashboard_data['today_courier_arrived_order'] = Order::myOrder()->whereDate('created_at', today())->where('status', 'courier_arrived')->count();
        $dashboard_data['today_completed_order'] = Order::myOrder()->whereDate('created_at', today())->where('status', 'completed')->count();
        $dashboard_data['today_failed_order'] = Order::myOrder()->whereDate('created_at', today())->where('status', 'failed')->count();

        $dashboard_data['app_setting'] = AppSetting::first();

        $upcoming_order = Order::myOrder()->whereNotIn('status', ['draft', 'cancelled', 'completed'])->whereNotNull('pickup_point->start_time')
                        ->where('pickup_point->start_time', '>=', Carbon::now()->format('Y-m-d H:i:s'))
                        ->orderBy('pickup_point->start_time', 'asc')->paginate(10);
        $dashboard_data['upcoming_order'] = OrderResource::collection($upcoming_order);

        $recent_order = Order::myOrder()->whereDate('date', '<=', Carbon::now()->format('Y-m-d'))->orderBy('date', 'desc')->paginate(10);
        $dashboard_data['recent_order'] = OrderResource::collection($recent_order);

        $client = User::where('user_type', 'client')->orderBy('created_at', 'desc')->paginate(10);
        $dashboard_data['recent_client'] = UserResource::collection($client);

        $delivery_man = User::where('user_type', 'delivery_man')->orderBy('created_at', 'desc')->paginate(10);
        $dashboard_data['recent_delivery_man'] = UserResource::collection($delivery_man);

        $sunday = strtotime('sunday -1 week');
        $sunday = date('w', $sunday) === date('w') ? $sunday + 7 * 86400 : $sunday;
        $saturday = strtotime(date('Y-m-d', $sunday).' +6 days');

        $week_start = date('Y-m-d 00:00:00', $sunday);
        $week_end = date('Y-m-d 23:59:59', $saturday);

        $dashboard_data['week'] = [ 'week_start' => $week_start, 'week_end' => $week_end ];
        $weekly_order_count = Order::selectRaw('DATE_FORMAT(created_at , "%w") as days , DATE_FORMAT(created_at , "%Y-%m-%d") as date')->whereBetween('created_at', [$week_start, $week_end])->get()->toArray();

        $data = [];

        $order_collection = collect($weekly_order_count);
        for ($i = 0; $i < 7; ++$i) {
            $total = $order_collection->filter(function ($value, $key) use ($week_start, $i) {
                return $value['date'] == date('Y-m-d', strtotime($week_start.' + '.$i.'day'));
            })->count();

            $data[] = [
                'day' => date('l', strtotime($week_start.' + '.$i.'day')),
                'total' => $total,
                'date' => date('Y-m-d', strtotime($week_start.' + '.$i.'day')),
            ];
        }

        $dashboard_data['weekly_order_count'] = $data;

        $user_week_report = User::selectRaw('DATE_FORMAT(created_at , "%w") as days , DATE_FORMAT(created_at , "%Y-%m-%d") as date')->where('user_type', 'client')->whereBetween('created_at', [$week_start, $week_end])->get()->toArray();

        $data = [];

        $user_collection = collect($user_week_report);
        for ($i = 0; $i < 7; ++$i) {
            $total = $user_collection->filter(function ($value, $key) use ($week_start, $i) {
                return $value['date'] == date('Y-m-d', strtotime($week_start.' + '.$i.'day'));
            })->count();

            $data[] = [
                'day' => date('l', strtotime($week_start.' + '.$i.'day')),
                'total' => $total,
                'date' => date('Y-m-d', strtotime($week_start.' + '.$i.'day')),
            ];
        }

        $dashboard_data['user_weekly_count'] = $data;

        $user = auth()->user();
        $dashboard_data['all_unread_count'] = isset($user->unreadNotifications) ? $user->unreadNotifications->count() : 0;

        $weekly_payment_report = Payment::myPayment()->selectRaw('DATE_FORMAT(created_at , "%w") as days , DATE_FORMAT(created_at , "%Y-%m-%d") as date, total_amount ')
                        ->where('payment_status', 'paid')->whereBetween('created_at', [$week_start, $week_end])->get()->toArray();
        $data = [];

        $payment_collection = collect($weekly_payment_report);
        for ($i = 0; $i < 7; ++$i) {
            $total_amount = $payment_collection->filter(function ($value, $key) use ($week_start, $i) {
                return $value['date'] == date('Y-m-d', strtotime($week_start.' + '.$i.'day'));
            })->sum('total_amount');

            $data[] = [
                'day' => date('l', strtotime($week_start.' + '.$i.'day')),
                'total_amount' => $total_amount,
                'date' => date('Y-m-d', strtotime($week_start.' + '.$i.'day')),
            ];
        }

        $dashboard_data['weekly_payment_report'] = $data;

        $month_start = Carbon::now()->startOfMonth();
        $today = Carbon::now();
        $diff = $month_start->diffInDays($today) + 1;

        $dashboard_data['month'] = [
            'month_start' => $month_start,
            'month_end' => $today,
            'diff' => $diff,
        ];
        $monthly_order_count = Order::selectRaw('DATE_FORMAT(created_at , "%w") as days , DATE_FORMAT(created_at , "%Y-%m-%d") as date')
                        ->whereBetween('created_at', [$month_start, $today])->get()->toArray();

        $monthly_order_count_data = [];

        $order_collection = collect($monthly_order_count);

        $monthly_payment_report = Payment::myPayment()->selectRaw('DATE_FORMAT(created_at , "%w") as days , DATE_FORMAT(created_at , "%Y-%m-%d") as date, total_amount ')
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$month_start, $today])
            ->whereHas('order', function ($query) {
                $query->where('status', 'completed');
            })->withTrashed()->get()->toArray();

        $monthly_payment_completed_order_data = [];

        $payment_collection = collect($monthly_payment_report);

        $monthly_payment_cancelled_report = Payment::myPayment()->selectRaw('DATE_FORMAT(created_at , "%w") as days , DATE_FORMAT(created_at , "%Y-%m-%d") as date, cancel_charges ')
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$month_start, $today])
            ->whereHas('order', function ($query) {
                $query->where('status', 'cancelled');
            })->withTrashed()->get()->toArray();

        $monthly_payment_cancelled_order_data = [];
        $payment_cancelled_collection = collect($monthly_payment_cancelled_report);

        for ($i = 0; $i < $diff; ++$i) {
            $total = $order_collection->filter(function ($value, $key) use ($month_start, $i) {
                return $value['date'] == date('Y-m-d', strtotime($month_start.' + '.$i.'day'));
            })->count();

            $monthly_order_count_data[] = [
                'total' => $total,
                'date' => date('Y-m-d', strtotime($month_start.' + '.$i.'day')),
            ];

            $total_amount = $payment_collection->filter(function ($value, $key) use ($month_start, $i) {
                return $value['date'] == date('Y-m-d', strtotime($month_start.' + '.$i.'day'));
            })->sum('total_amount');

            $monthly_payment_completed_order_data[] = [
                'total_amount' => $total_amount,
                'date' => date('Y-m-d', strtotime($month_start.' + '.$i.'day')),
            ];

            $cancel_charges = $payment_cancelled_collection->filter(function ($value, $key) use ($month_start, $i) {
                return $value['date'] == date('Y-m-d', strtotime($month_start.' + '.$i.'day'));
            })->sum('cancel_charges');

            $monthly_payment_cancelled_order_data[] = [
                'total_amount' => $cancel_charges,
                'date' => date('Y-m-d', strtotime($month_start.' + '.$i.'day')),
            ];
        }

        $dashboard_data['monthly_order_count'] = $monthly_order_count_data;
        $dashboard_data['monthly_payment_completed_report'] = $monthly_payment_completed_order_data;
        $dashboard_data['monthly_payment_cancelled_report'] = $monthly_payment_cancelled_order_data;

        $dashboard_data['country_city_data'] = Country::with('cities')->get();
        $dashboard_data['deliver_man_version'] = [
            'android_force_update' => SettingData('APP_VERSION', 'APP_VERSION_ANDROID_FORCE_UPDATE'),
            'android_version_code' => SettingData('APP_VERSION', 'APP_VERSION_ANDROID_VERSION_CODE'),
            'appstore_url' => SettingData('APP_VERSION', 'APP_VERSION_APPSTORE_URL'),
            'ios_force_update' => SettingData('APP_VERSION', 'APP_VERSION_IOS_FORCE_UPDATE'),
            'ios_version' => SettingData('APP_VERSION', 'APP_VERSION_IOS_VERSION'),
            'playstore_url' => SettingData('APP_VERSION', 'APP_VERSION_PLAYSTORE_URL'),
        ];

        $dashboard_data['crisp_data'] = [
            'crisp_chat_website_id' => SettingData('CRISP_CHAT_CONFIGURATION', 'CRISP_CHAT_CONFIGURATION_WEBSITE_ID') ?? null,
            'is_crisp_chat_enabled' => SettingData('CRISP_CHAT_CONFIGURATION', 'CRISP_CHAT_CONFIGURATION_ENABLE/DISABLE') ? true : false,
        ];

        return json_custom_response($dashboard_data);
    }

    public function dashboardChartData(Request $request)
    {
        $type = request('type');
        $month_start = Carbon::parse(request('start_at'))->startOfMonth();
        $month_end = Carbon::parse(request('end_at'))->endOfMonth();

        $diff = $month_start->diffInDays($month_end) + 1;
        $dashboard_data['month'] = [
            'month_start' => $month_start,
            'month_end' => $month_end,
            'diff' => $diff,
        ];
        $data = [];
        if ($type == 'monthly_order_count') {
            $monthly_order_count = Order::selectRaw('DATE_FORMAT(created_at , "%w") as days , DATE_FORMAT(created_at , "%Y-%m-%d") as date')
                ->whereBetween('created_at', [$month_start, $month_end])->get()->toArray();

            $order_collection = collect($monthly_order_count);

            for ($i = 0; $i < $diff; ++$i) {
                $total = $order_collection->filter(function ($value, $key) use ($month_start, $i) {
                    return $value['date'] == date('Y-m-d', strtotime($month_start.' + '.$i.'day'));
                })->count();

                $data[] = [ 'total' => $total, 'date' => date('Y-m-d', strtotime($month_start.' + '.$i.'day')) ];
            }
            $dashboard_data['monthly_order_count'] = $data;
        }

        if ($type == 'monthly_payment_completed_report') {
            $monthly_payment_report = Payment::myPayment()->selectRaw('DATE_FORMAT(created_at , "%w") as days , DATE_FORMAT(created_at , "%Y-%m-%d") as date, total_amount ')
                ->where('payment_status', 'paid')
                ->whereBetween('created_at', [$month_start, $month_end])
                ->whereHas('order', function ($query) {
                    $query->where('status', 'completed');
                })->withTrashed()->get()->toArray();

            $payment_collection = collect($monthly_payment_report);

            for ($i = 0; $i < $diff; ++$i) {
                $total_amount = $payment_collection->filter(function ($value, $key) use ($month_start, $i) {
                    return $value['date'] == date('Y-m-d', strtotime($month_start.' + '.$i.'day'));
                })->sum('total_amount');
                $data[] = [ 'total_amount' => $total_amount, 'date' => date('Y-m-d', strtotime($month_start.' + '.$i.'day')) ];
            }
            $dashboard_data['monthly_payment_completed_report'] = $data;
        }

        if ($type == 'monthly_payment_cancelled_report') {
            $monthly_payment_report = Payment::myPayment()->selectRaw('DATE_FORMAT(created_at , "%w") as days , DATE_FORMAT(created_at , "%Y-%m-%d") as date, cancel_charges ')
                ->where('payment_status', 'paid')
                ->whereBetween('created_at', [$month_start, $month_end])
                ->whereHas('order', function ($query) {
                    $query->where('status', 'cancelled');
                })->withTrashed()->get()->toArray();

            $payment_collection = collect($monthly_payment_report);

            for ($i = 0; $i < $diff; ++$i) {
                $cancel_charges = $payment_collection->filter(function ($value, $key) use ($month_start, $i) {
                    return $value['date'] == date('Y-m-d', strtotime($month_start.' + '.$i.'day'));
                })->sum('cancel_charges');

                $data[] = [ 'total_amount' => $cancel_charges, 'date' => date('Y-m-d', strtotime($month_start.' + '.$i.'day')) ];
            }
            $dashboard_data['monthly_payment_cancelled_report'] = $data;
        }

        return json_custom_response($dashboard_data);
    }

    public function permissionGetList(Request $request)
    {
        $permission = Permission::query();

        $per_page = config('constant.PER_PAGE_LIMIT');
        if ($request->has('per_page') && !empty($request->per_page)) {
            if (is_numeric($request->per_page)) {
                $per_page = $request->per_page;
            }
            if ($request->per_page == -1) {
                $per_page = $permission->count();
            }
        }

        $permission = $permission->orderBy('id', 'asc')->paginate($per_page);

        $items = PermissionResource::collection($permission);
        $status = 200;

        $response = [
            'status' => $status,
            'pagination' => json_pagination_response($items),
            'data' => $items,
        ];

        return json_custom_response($response);
    }

    private function normalizeRegisterPhone(?string $number): string
    {
        $number = preg_replace('/\s+/', '', (string) $number);
        if (! str_starts_with($number, '+')) {
            $number = '+'.$number;
        }

        return $number;
    }

    private function findUserByPhone(?string $contactNumber): ?User
    {
        if (empty($contactNumber)) {
            return null;
        }

        $phoneKey = $this->normalizeRegisterPhone($contactNumber);
        $digits = preg_replace('/\D/', '', $phoneKey);

        return User::query()
            ->where('contact_number', $phoneKey)
            ->orWhere('contact_number', $digits)
            ->orWhere('username', $phoneKey)
            ->orWhere('username', $digits)
            ->first();
    }

    private function buildLoginPayload(User $user): array
    {
        $success = $user->toArray();
        $success['api_token'] = $user->createToken('auth_token')->plainTextToken;
        $success['profile_image'] = getSingleMedia($user, 'profile_image', null);
        $success['document_verified_at'] = $user->document_verified_at;
        $is_verified_delivery_man = false;
        if ($user->user_type == 'delivery_man') {
            $is_verified_delivery_man = DeliveryManDocument::verifyDeliveryManDocument($user->id);
        }
        $success['is_verified_delivery_man'] = (int) $is_verified_delivery_man;
        unset($success['media']);

        $is_email_verification = SettingData('email_verification', 'email_verification');
        $is_mobile_verification = SettingData('mobile_verification', 'mobile_verification');
        $is_document_verification = SettingData('document_verification', 'document_verification');

        $success['is_email_verification'] = ($is_email_verification == 0) ? true : false;
        $success['is_mobile_verification'] = ($is_mobile_verification == 0) ? true : false;
        $success['is_document_verification'] = ($is_document_verification == 0) ? true : false;

        $success['daily_contact_number'] = $user->daily_contact_number;
        $success['daily_contact_date'] = $user->daily_contact_date
            ? \Carbon\Carbon::parse($user->daily_contact_date)->toDateString()
            : null;
        $success['needs_daily_contact'] = false;
        $success['assigned_phone'] = $user->riderAssignedPhone();

        return $success;
    }

    private function isRegisterOtpRequired(?string $userType): bool
    {
        $regGroup = ($userType ?? 'client') === 'delivery_man'
            ? 'driver_registration_setting'
            : 'user_registration_setting';
        $isMobileVerification = registrationSettingValue($regGroup, 'mobile_verification');
        if ($isMobileVerification === null || $isMobileVerification === '') {
            $isMobileVerification = SettingData('mobile_verification', 'mobile_verification');
        }

        return (string) $isMobileVerification !== '0';
    }

    private function issueRegisterOtpToken(string $phoneKey): string
    {
        $token = Str::random(64);
        Cache::put('register_otp_verified:'.hash('sha256', $token), [
            'phone' => $phoneKey,
        ], now()->addMinutes(30));

        return $token;
    }

    public function sendRegisterOtp(Request $request)
    {
        if (empty($this->getKeyAndIV())) {
            return response()->json([
                'errors' => ['message' => __('message.key_value_set')],
            ], 404);
        }

        $contactNumber = $this->decryptData($request->input('contact_number'));
        $userType = $this->decryptData($request->input('user_type')) ?? 'client';

        if (empty($contactNumber)) {
            return json_custom_response(['message' => __('message.please_enter_contact_number'), 'success' => false], 422);
        }

        if (! $this->isRegisterOtpRequired($userType)) {
            return json_custom_response([
                'status' => true,
                'skip_otp' => true,
                'message' => __('message.register_otp_not_required'),
            ]);
        }

        if (User::where('contact_number', $contactNumber)->exists()) {
            return json_custom_response(['message' => __('message.contact_number_already_taken'), 'success' => false], 409);
        }

        $clientOtp = $request->has('otp_code') ? $this->decryptData($request->input('otp_code')) : null;
        $otp = $clientOtp ?: '654321';
        $phoneKey = $this->normalizeRegisterPhone($contactNumber);

        Cache::put('register_otp:'.hash('sha256', $phoneKey), [
            'otp' => $otp,
            'user_type' => $userType,
        ], now()->addMinutes(10));

        $smsSent = false;
        $isTwilioActive = SMSSetting::where('type', 'twilio')->where('status', 1)->first();

        if ($isTwilioActive && empty($clientOtp)) {
            try {
                $data = $isTwilioActive->values;
                $client = new Client($data['sid'] ?? null, $data['token'] ?? null);
                $client->messages->create(
                    $phoneKey,
                    [
                        'from' => $data['from'] ?? null,
                        'body' => __('message.verification_code', ['code' => $otp]),
                    ]
                );
                $smsSent = true;
            } catch (\Exception $e) {
                return json_custom_response(['status' => false, 'message' => $e->getMessage()], 500);
            }
        }

        return json_custom_response([
            'status' => true,
            'skip_otp' => false,
            'sms_sent' => $smsSent || ! $isTwilioActive,
            'message' => $smsSent ? __('message.sms_sent_successfully') : __('message.sms_sent_successfully'),
        ]);
    }

    public function sendLoginOtp(Request $request)
    {
        if (empty($this->getKeyAndIV())) {
            return response()->json([
                'errors' => ['message' => __('message.key_value_set')],
            ], 404);
        }

        $contactNumber = $this->decryptData($request->input('contact_number'));
        $decryptedPassword = $this->decryptData($request->input('password'));
        $userType = $this->decryptData($request->input('user_type')) ?? 'client';

        if (empty($contactNumber)) {
            return json_custom_response(['message' => __('message.please_enter_contact_number'), 'success' => false], 422);
        }

        if (empty($decryptedPassword)) {
            return json_custom_response(['message' => __('message.please_enter_password'), 'success' => false], 422);
        }

        $user = $this->findUserByPhone($contactNumber);
        if (! $user) {
            return json_custom_response(['message' => __('message.not_found_entry', ['name' => __('message.user')]), 'success' => false], 404);
        }

        if ($userType && $user->user_type !== $userType) {
            return json_custom_response(['message' => __('auth.failed'), 'success' => false], 402);
        }

        if (! \Hash::check($decryptedPassword, $user->password)) {
            return json_custom_response(['message' => __('auth.failed'), 'success' => false], 402);
        }

        $phoneKey = $this->normalizeRegisterPhone($contactNumber);
        Cache::put('login_otp:'.hash('sha256', $phoneKey), [
            'otp' => '123456',
            'user_id' => $user->id,
        ], now()->addMinutes(10));

        return json_custom_response([
            'status' => true,
            'sms_sent' => true,
            'message' => __('message.sms_sent_successfully'),
        ]);
    }

    public function verifyLoginOtp(Request $request)
    {
        if (empty($this->getKeyAndIV())) {
            return response()->json([
                'errors' => ['message' => __('message.key_value_set')],
            ], 404);
        }

        $contactNumber = $this->decryptData($request->input('contact_number'));
        $otpCode = $this->decryptData($request->input('otp_code'));
        $decryptedPlayerId = $this->decryptData($request->input('player_id'));
        $decryptedfcmToken = $this->decryptData($request->input('fcm_token'));
        $userType = $this->decryptData($request->input('user_type')) ?? 'client';

        if (empty($contactNumber) || empty($otpCode)) {
            return json_custom_response(['message' => __('message.otp_verification_failed'), 'success' => false], 422);
        }

        $phoneKey = $this->normalizeRegisterPhone($contactNumber);
        $cacheKey = 'login_otp:'.hash('sha256', $phoneKey);
        $cached = Cache::get($cacheKey);

        if (! $cached || ($cached['otp'] ?? '') !== $otpCode) {
            return json_custom_response([
                'message' => __('message.otp_verification_failed'),
                'success' => false,
            ], 422);
        }

        $user = User::find($cached['user_id'] ?? 0);
        if (! $user) {
            return json_custom_response(['message' => __('message.not_found_entry', ['name' => __('message.user')]), 'success' => false], 404);
        }

        if ($userType && $user->user_type !== $userType) {
            return json_custom_response(['message' => __('auth.failed'), 'success' => false], 402);
        }

        if ($user->user_type === 'client' && ! $user->isClientApprovalApproved()) {
            $approval = $user->approval_status ?? User::APPROVAL_PENDING;
            $message = $approval === User::APPROVAL_REJECTED
                ? __('message.os_account_rejected')
                : __('message.os_account_pending_approval');

            return json_message_response($message, 403);
        }

        Auth::login($user);
        if ($decryptedPlayerId != null) {
            $user->player_id = $decryptedPlayerId;
        }
        if ($decryptedfcmToken != null) {
            $user->fcm_token = $decryptedfcmToken;
        }
        $user->otp_verify_at = $user->otp_verify_at ?? now();
        $user->last_actived_at = now();
        $user->save();

        Cache::forget($cacheKey);

        return json_custom_response(['data' => $this->buildLoginPayload($user)], 200);
    }

    public function verifyRegisterOtp(Request $request)
    {
        if (empty($this->getKeyAndIV())) {
            return response()->json([
                'errors' => ['message' => __('message.key_value_set')],
            ], 404);
        }

        $contactNumber = $this->decryptData($request->input('contact_number'));
        $otpCode = $this->decryptData($request->input('otp_code'));
        $phoneKey = $this->normalizeRegisterPhone($contactNumber);
        $cacheKey = 'register_otp:'.hash('sha256', $phoneKey);
        $cached = Cache::get($cacheKey);

        if (! $cached || ($cached['otp'] ?? '') !== $otpCode) {
            return json_custom_response([
                'message' => __('message.otp_verification_failed'),
                'success' => false,
            ], 422);
        }

        $token = $this->issueRegisterOtpToken($phoneKey);

        return json_custom_response([
            'status' => true,
            'message' => __('message.otp_verified_successfully'),
            'otp_verification_token' => $this->encryptData($token),
        ]);
    }

    public function confirmRegisterPhoneFirebase(Request $request)
    {
        if (empty($this->getKeyAndIV())) {
            return response()->json([
                'errors' => ['message' => __('message.key_value_set')],
            ], 404);
        }

        $contactNumber = $this->decryptData($request->input('contact_number'));
        $userType = $this->decryptData($request->input('user_type')) ?? 'client';

        if (! $this->isRegisterOtpRequired($userType)) {
            return json_custom_response([
                'status' => true,
                'skip_otp' => true,
                'message' => __('message.register_otp_not_required'),
            ]);
        }

        $phoneKey = $this->normalizeRegisterPhone($contactNumber);
        $token = $this->issueRegisterOtpToken($phoneKey);
        Cache::forget('register_otp:'.hash('sha256', $phoneKey));

        return json_custom_response([
            'status' => true,
            'message' => __('message.otp_verified_successfully'),
            'otp_verification_token' => $this->encryptData($token),
        ]);
    }

    public function sendSms(Request $request)
    {
        $request->validate([ 'number' => 'required', 'code' => 'required' ]);

        $number = $request->number;
        if(!str_starts_with($number, '+')){
            $number = '+' . $number;
        }

        // Check Twilio status
        $isTwilioActive = SMSSetting::where('type','twilio')->where('status',1)->first();

        if(!$isTwilioActive){
            return response()->json([ 'status' => false, 'message' => __('message.twilio_sms_disabled') ]);
        }

        try{

            $data = $isTwilioActive->values;

            $sid   = $data['sid'] ?? null;
            $token = $data['token'] ?? null;
            $from  = $data['from'] ?? null;

            $client = new Client($sid, $token);

            $client->messages->create(
                $number,
                [
                    'from' => $from,
                    'body' => __('message.verification_code', ['code' => $request->code])
                ]
            );

            return response()->json([ 'status' => true, 'message' => __('message.sms_sent_successfully') ]);

        }catch(\Exception $e){
            return response()->json([ 'status' => false, 'message' => $e->getMessage() ]);
        }
    }

    /**
     * Admin creates Point Account for new user with temporary password
     */
    public function adminCreateAccount(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'contact_number' => 'required|string',
            'password' => 'required|string|min:6',
            'city_id' => 'nullable|integer',
            'country_id' => 'nullable|integer',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'contact_number' => $request->contact_number,
            'password' => bcrypt($request->password),
            'username' => stristr($request->email, '@', true) . rand(100, 1000),
            'display_name' => $request->name,
            'user_type' => 'client',
            'city_id' => $request->city_id,
            'country_id' => $request->country_id,
            'referral_code' => generateRandomCode(),
            'email_verified_at' => now(),
            'otp_verify_at' => now(),
            'created_by_admin' => 1,
            'is_temp_password' => 1,
            'status' => 1,
            'approval_status' => User::APPROVAL_APPROVED,
        ]);
        $user->assignRole('client');

        // Create wallet for point account
        Wallet::firstOrCreate(['user_id' => $user->id], ['total_amount' => 0]);

        return json_custom_response([
            'message' => __('message.save_form', ['form' => __('message.point_account')]),
            'data' => new UserResource($user),
        ]);
    }
}
