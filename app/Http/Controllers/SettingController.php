<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingRequest;
use Illuminate\Http\Request;
use App\Models\AppSetting;
use App\Models\Setting;
use App\Models\User;
use App\Models\PaymentGateway;
use App\Models\SMSSetting;
use App\Http\Requests\UserRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;

class SettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function settings(Request $request)
    {
        $auth_user = auth()->user();
        $assets = ['phone'];
        $pageTitle = __('message.setting');
        $page = $request->page;

        if ($page == '') {
            if ($auth_user->hasAnyRole(['admin', 'demo_admin'])) {
                $page = 'general-setting';
            } else {
                $page = 'profile_form';
            }
        }

        return view('setting.index', compact('page', 'pageTitle', 'auth_user', 'assets'));
    }

    public function layoutPage(Request $request)
    {
        $page = $request->page;
        if ($page == 'payment-setting') {
            $type = isset($request->type) ? $request->type : 'stripe';
        }
        if ($page == 'sms-settings') {
            $type = isset($request->type) ? $request->type : 'twilio';
        }
        $auth_user = auth()->user();
        $user_id = $auth_user->id;
        $settings = AppSetting::first();
        $user_data = User::find($user_id);
        $envSettting = $envSettting_value = [];

        if (count($envSettting) > 0) {
            $envSettting_value = Setting::whereIn('key', array_keys($envSettting))->get();
        }
        if ($settings == null) {
            $settings = new AppSetting;
        } elseif ($user_data == null) {
            $user_data = new User;
        }
        switch ($page) {
            case 'password_form':
                $data = view('setting.' . $page, compact('settings', 'user_data', 'page'))->render();
                break;
            case 'verify-old-password':
                $data = view('setting.' . $page, compact('settings', 'user_data', 'page'))->render();
                break;
            case 'change_email_form':
                $data = view('setting.' . $page, compact('settings', 'user_data', 'page'))->render();
                break;
            case 'otp_form':
                $data = view('setting.' . $page, compact('settings', 'user_data', 'page'))->render();
                break;
            case 'profile_form':
                $assets = ['phone'];
                $data = view('setting.' . $page, compact('settings', 'user_data', 'page', 'assets'))->render();
                break;
            case 'mail-setting':
                $data = view('setting.' . $page, compact('settings', 'page'))->render();
                break;
            case 'mobile-config':
                $setting = Config::get('mobile-config');
                $getSetting = [];
                foreach ($setting as $k => $s) {
                    foreach ($s as $sk => $ss) {
                        $getSetting[] = $k . '_' . $sk;
                    }
                }

                $setting_value = Setting::whereIn('key', $getSetting)->get();

                $data = view('setting.' . $page, compact('setting', 'setting_value', 'page'))->render();
                break;
            case 'api-server-setting':
                $apiBaseUrl = SettingData('API_SERVER', 'API_SERVER_BASE_URL') ?: '';
                $browserUrl = rtrim((string) request()->getSchemeAndHttpHost(), '/');
                $wifiIp = detectMachineLanIp();
                $wifiUrl = detectMachineLanBaseUrl();
                // Prefer WiFi URL for mobile apps when admin opened via localhost.
                $detectedUrl = $wifiUrl ?: $browserUrl;
                if ($apiBaseUrl === '') {
                    $apiBaseUrl = $detectedUrl;
                }
                $data = view('setting.' . $page, compact(
                    'page',
                    'apiBaseUrl',
                    'detectedUrl',
                    'browserUrl',
                    'wifiIp',
                    'wifiUrl'
                ))->render();
                break;
            case 'reference-setting':
                $setting = Config::get('reference');
                $getSetting = [];
                foreach ($setting as $k => $s) {
                    foreach ($s as $sk => $ss) {
                        $getSetting[] = $sk;
                    }
                }

                $setting_value = Setting::whereIn('key', $getSetting)->get();

                $data = view('setting.' . $page, compact('setting', 'setting_value', 'page'))->render();
                break;
            case 'insurance-setting':
                $setting = Config::get('insurance');
                $getSetting = [];
                foreach ($setting as $k => $s) {
                    foreach ($s as $sk => $ss) {
                        $getSetting[] = $sk;
                    }
                }

                $setting_value = Setting::whereIn('key', $getSetting)->get();

                $data = view('setting.' . $page, compact('setting', 'setting_value', 'page'))->render();
                break;

            case 'notification-setting':
                $notification_setting = config('constant.notification');
                $notification_setting_data = AppSetting::first();
                $data = view('setting.' . $page, compact('notification_setting', 'notification_setting_data'))->render();
                break;
            case 'payment-setting':
                $payment_setting_data = PaymentGateway::where('type', $type)->first();
                $data = view('setting.' . $page, compact('settings', 'page', 'type', 'payment_setting_data'))->render();
                break;
            case 'invoice-setting':
                $pageTitle = __('message.invoice');
                $invoice = config('constant.order_invoice');
                foreach ($invoice as $key => $val) {
                    if (in_array($key, ['company_name', 'company_contact_number', 'company_address'])) {
                        $invoice[$key] = Setting::where('type', 'order_invoice')->where('key', $key)->pluck('value')->first();
                    } else {
                        $invoice[$key] = Setting::where('type', 'order_invoice')->where('key', $key)->first();
                    }
                }
                $data = view('setting.' . $page, compact('invoice', 'pageTitle'))->render();
                break;
            case 'order-setting':
                $setting = Config::get('order-config');
                $getSetting = [];
                foreach ($setting as $k => $s) {
                    foreach ($s as $hd => $ss) {
                        $getSetting[] = $hd;
                    }
                }
                $setting_value = AppSetting::get();
                $pageTitle = __('message.add_form_title', ['form' => __('message.order-setting')]);
                $data = view('setting.' . $page, compact('setting', 'page', 'setting_value', 'pageTitle'))->render();
                break;
            case 'register-setting':
                $pageTitle = __('message.register_setting');
                $setting = Config::get('register-config');
                $getSetting = [];
                foreach ($setting as $k => $s) {
                    foreach ($s as $sk => $ss) {
                        $getSetting[] = ['type' => $k, 'key' => $sk];
                    }
                }

                $setting_value = Setting::where(function ($query) use ($getSetting) {
                    foreach ($getSetting as $item) {
                        $query->orWhere(function ($q) use ($item) {
                            $q->where('type', $item['type'])->where('key', $item['key']);
                        });
                    }
                })->get();

                $data = view('setting.' . $page, compact('setting', 'setting_value', 'page', 'pageTitle'))->render();
                break;
            case 'ordermail-setting':
                $setting = Config::get('order-mail');
                $getSetting = [];
                foreach ($setting as $k => $s) {
                    foreach ($s as $sk => $ss) {
                        $getSetting[] = $sk;
                    }
                }
                $setting_value = Setting::whereIn('key', $getSetting)->get();
                $pageTitle = __('message.mail_template_setting');
                $data = view('setting.' . $page, compact('page', 'setting_value', 'setting', 'pageTitle'))->render();
                break;

            case 'database-backup':
                $setting_value = AppSetting::get();
                $pageTitle = __('message.database_backup');
                $data = view('setting.' . $page, compact('page', 'pageTitle', 'setting_value', 'settings'))->render();
                break;

            case 'print-label-mobail-number':
                $setting = Config::get('printlabel');
                $pageTitle = __('message.print_label_setting');
                $getSetting = [];
                foreach ($setting as $k => $s) {
                    foreach ($s as $sk => $ss) {
                        $getSetting[] = $sk;
                    }
                }

                $setting_value = Setting::whereIn('key', $getSetting)->get();
                $pageTitle = __('message.print_label_setting');
                $data = view('setting.' . $page, compact('setting', 'setting_value', 'page', 'pageTitle'))->render();
                break;

            case 'sms-settings':
                $sms_setting = SMSSetting::where('type', $type)->first();
                $data = view('setting.' . $page, compact('settings', 'page', 'type', 'sms_setting'))->render();
                break;

            case 'sms-template-setting':
                $pageTitle = __('message.sms_template_setting');
                $sms_template_setting = config('constant.SMS_TEMPLATE_SETTING');
                foreach ($sms_template_setting as $key => $val) {
                    $sms_template_setting[$key] = Setting::where('type', 'SMS_TEMPLATE')->where('key', $key)->value('value');
                }
                $data = view('setting.' . $page, compact('sms_template_setting', 'pageTitle', 'page'))->render();
                break;
            case 'emergency-setting':
                $setting = Config::get('emergency-setting');
                $getSetting = [];
                foreach ($setting as $k => $s) {
                    foreach ($s as $sk => $ss) {
                        $getSetting[] = $sk;
                    }
                }
                $setting_value = Setting::whereIn('key', $getSetting)->get();
                $data = view('setting.' . $page, compact('page', 'setting_value', 'setting'))->render();
                break;

            case 'crisp-setting':
                $setting = Config::get('crisp-setting');
                $getSetting = [];
                foreach ($setting as $k => $s) {
                    foreach ($s as $sk => $ss) {
                        $getSetting[] = $k . '_' . $sk;
                    }
                }
                $setting_value = Setting::whereIn('key', $getSetting)->get();
                $data = view('setting.' . $page, compact('page', 'setting_value', 'setting'))->render();
                break;
            case 'onesingal-setting':
                $setting = Config::get('onesingal-config');
                $getSetting = [];
                foreach ($setting as $k => $s) {
                    foreach ($s as $sk => $ss) {
                        $getSetting[] = $k . '_' . $sk;
                    }
                }

                $setting_value = Setting::whereIn('key', $getSetting)->get();

                $data = view('setting.' . $page, compact('setting', 'setting_value', 'page'))->render();
                break;

            case 'highdemand-settings':
                $setting = Config::get('highdemand-settings');
                $getSetting = [];
                foreach ($setting as $k => $s) {
                    foreach ($s as $sk => $ss) {
                        $getSetting[] = $k . '_' . $sk;
                    }
                }

                $setting_value = Setting::whereIn('key', $getSetting)->get();
                $data = view('setting.' . $page, compact('setting', 'setting_value', 'page'))->render();
                break;
            case 'mail-alert-setting':
                $page = 'mail-alert-setting';
                $mail_alert_setting = config('constant.mail_alert');

                foreach ($mail_alert_setting as $key => $val) {
                    $mail_alert_setting[$key] = Setting::where('key',$key)->pluck('value')->first();
                }

                $data  = view('setting.'.$page, compact('mail_alert_setting', 'page'))->render();
                break;
            default:
                $data = view('setting.' . $page, compact('settings', 'page', 'envSettting'))->render();
                break;
        }
        return response()->json($data);
    }

    public function settingUpdate(Request $request)
    {
        $data = $request->all();
            if (env('APP_DEMO') && isset($data['key']) && in_array('allow_deliveryman', $data['key']))
            {
                $message = __('message.demo_permission_denied');

                if ($request->is('api/*')) {
                    return response()->json(['status' => false, 'message' => $message]);
                }

                if ($request->ajax()) {
                    return response()->json([ 'status' => false, 'message' => $message, 'event' => 'validation' ]);
                }

                return redirect()->route('setting.index', ['page' => $request->page])->withErrors($message);
            }
        $page = $request->page;
        $currentValue = registrationSettingValue('driver_registration_setting', 'allow_deliveryman');

        foreach ($data['key'] as $key => $val) {
            $value = ($data['value'][$key] != null) ? $data['value'][$key] : null;
            if (is_string($value)) {
                $value = trim($value);
            }
            $input = [
                'type' => $data['type'][$key],
                'key' => $data['key'][$key],
                'value' => ($value !== null && $value !== '') ? $value : null,
            ];
            if ($data['key'][$key] == 'allow_deliveryman') {
                $newValue = $data['value'][$key];

                if ($newValue != $currentValue) {
                    updateLanguageVersion();
                }
            }
            Setting::updateOrCreate(['type' => $input['type'], 'key' => $input['key']], $input);

            // Mobile API base URL lives in DB only — never write it into .env
            // (envChanges + stale APP_URL redirects break local admin after Save).
            $settingType = (string) ($data['type'][$key] ?? '');
            $settingKey = (string) ($data['key'][$key] ?? '');
            if ($settingType !== 'API_SERVER' && !str_starts_with($settingKey, 'API_SERVER')) {
                envChanges($settingKey, $value);
            }
        }

        // Stay on the host the admin is already using (e.g. 127.0.0.1), instead of
        // jumping to config('app.url') which may be an old WiFi IP.
        if ($page === 'api-server-setting') {
            return redirect()
                ->to('/setting/api-server-setting')
                ->withSuccess(__('message.updated'));
        }

        return redirect()->route('setting.index', ['page' => $page])->withSuccess(__('message.updated'));
    }

    public function settingsUpdates(SettingRequest $request)
    {
        $currency = currencyArray($request->currency_code);
        $request->merge(['currency' => $currency['symbol'] ?? '$']);

        $page = $request->page;

        $language_option = $request->language_option;

        if (!is_array($language_option)) {
            $language_option = (array) $language_option;
        }

        array_push($language_option, $request->env['DEFAULT_LANGUAGE']);

        $request->merge(['language_option' => $language_option]);

        $request->merge(['site_name' => str_replace("'", "", str_replace('"', '', $request->site_name))]);

        $res = AppSetting::updateOrCreate(['id' => $request->id], $request->all());

        $type = 'APP_NAME';
        $env = $request->env;

        $env['APP_NAME'] = $res->site_name;
        foreach ($env as $key => $value) {
            envChanges($key, $value);
        }

        App::setLocale($env['DEFAULT_LANGUAGE']);
        session()->put('locale', $env['DEFAULT_LANGUAGE']);

        if ($request->timezone != '') {
            $user = auth()->user();
            $user->timezone = $request->timezone;
            $user->save();
        }
        uploadMediaFile($res, $request->site_logo, 'site_logo');
        uploadMediaFile($res, $request->site_dark_logo, 'site_dark_logo');
        uploadMediaFile($res, $request->site_favicon, 'site_favicon');

        appSettingData('set');

        createLangFile($env['DEFAULT_LANGUAGE']);

        return redirect()->route('setting.index', ['page' => $page])->withSuccess(__('message.updated'));
    }

    public function envChanges(Request $request)
    {
        $page = $request->page;
        if (!auth()->user()->hasRole('admin')) {
            abort(403, __('message.action_is_unauthorized'));
        }
        $env = $request->ENV;
        $envtype = $request->type;

        foreach ($env as $key => $value) {
            envChanges($key, str_replace('#', '', $value));
        }
        return redirect()->route('setting.index', ['page' => $page])->withSuccess(ucfirst($envtype) . ' ' . __('message.updated'));
    }

    public function updateProfile(UserRequest $request)
    {
        $user = Auth::user();

        $user->fill($request->all())->update();
        uploadMediaFile($user, $request->profile_image, 'profile_image');

        return redirect()->route('setting.index', ['page' => 'profile_form'])->withSuccess(__('message.profile') . ' ' . __('message.updated'));
    }

    public function changePassword(Request $request)
    {
        $user = User::where('id', Auth::user()->id)->first();

        if ($user == "") {
            $message = __('message.not_found_entry', ['name' => __('message.user')]);
            return json_message_response($message, 400);
        }

        $validator = Validator::make($request->all(), [
            'old' => 'required|min:8|max:255',
            'password' => 'required|min:8|confirmed|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->route('setting.index', ['page' => 'password_form'])->with('errors', $validator->errors());
        }

        $hashedPassword = $user->password;

        $match = Hash::check($request->old, $hashedPassword);

        $same_exits = Hash::check($request->password, $hashedPassword);
        if ($match) {
            if ($same_exits) {
                $message = __('message.old_new_pass_same');
                return redirect()->route('setting.index', ['page' => 'password_form'])->with('error', $message);
            }

            $user->fill([
                'password' => Hash::make($request->password)
            ])->save();
            Auth::logout();
            $message = __('message.password_change');
            return redirect()->route('setting.index', ['page' => 'password_form'])->withSuccess($message);
        } else {
            $message = __('message.valid_password');
            return redirect()->route('setting.index', ['page' => 'password_form'])->with('error', $message);
        }
    }
    public function verifyOldPassword(Request $request)
    {
        $request->validate([ 'old_password' => 'required' ]);
        $user = Auth::user();
        if (Hash::check($request->old_password, $user->password)) {
            return redirect()->route('setting.index', ['page' => 'change_email_form']);
        } else {
            $message = __('message.invalid_old_password');
            return redirect()->route('setting.index', ['page' => 'verify-old-password'])->with('error', $message);
        }
    }
    public function changeEmail(Request $request)
    {
        $user = Auth::user();
        $newEmail = $request->email;
        $otp_verification_status = config('constant.MAIL_SETTING.EMAIL_OTP_VERIFICATION');

        if (!$user) {
            $message = __('message.not_found_entry', ['name' => __('message.user')]);
            return json_message_response($message, 400);
        }
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email,' . $user->id,
        ]);
        if ($validator->fails()) {
            return redirect()->route('setting.index', ['page' => 'change_email_form'])->with('error', $validator->errors());
        }

        if ($newEmail == $user->email) {
            $message = __('message.old_new_email_same');
            return redirect()->route('setting.index', ['page' => 'change_email_form'])->with('error', $message);
        }
        session(['new_email' => $newEmail]);
        // Send Mail
        sentOTP_mail($user,$otp_verification_status);
        return redirect()->route('setting.index', parameters: ['page' => 'otp_form'])->with('success', __('message.mail_sent'));

        // Update email
        $user->otp = null;
        $user->email = $newEmail;
        $user->save();

        return redirect()->route('setting.index', ['page' => 'change_email_form'])->with('success', __('message.email_change_success'));
    }

    public function changeEmail_otpVerify(Request $request)
    {
        $user = Auth::user();
        $newEmail = $request->newEmail;

        // Check if new email is provided
        if (empty($newEmail)) {
            return redirect()->route('setting.index', ['page' => 'change_email_form'])->with('error', __('message.otp_verification_failed'));
        }
        // Check OTP match
        if ($request->otp != $user->otp) {
            return redirect()->route('setting.index', ['page' => 'otp_form'])->with('error', __('message.otp_wrong'));
        }

        // Update email and reset OTP
        $user->update([ 'email' => $newEmail, 'otp' => null ]);

        return redirect()->route('setting.index', ['page' => 'profile_form'])->with('success', __('message.email_change_success'));
    }


    public function termAndCondition(Request $request)
    {
        $user_setting = Setting::where('type', 'terms_condition')->where('key', 'terms_condition')->first();
        $rider_setting = Setting::where('type', 'terms_condition_rider')->where('key', 'terms_condition_rider')->first();
        $pageTitle = __('message.terms_condition');
        $assets = ['textarea'];

        return view('setting.term_condition_form', compact('user_setting', 'rider_setting', 'pageTitle', 'assets'));
    }

    public function saveTermAndCondition(Request $request)
    {
        if (env('APP_DEMO')) {
            $message = __('message.demo_permission_denied');
            if (request()->is('api/*')) {
                return response()->json(['status' => true, 'message' => $message]);
            }
            if (request()->ajax()) {
                return response()->json(['status' => false, 'message' => $message, 'event' => 'validation']);
            }
            return redirect()->route('term-condition')->withErrors($message);
        }

        if (!auth()->user()->hasRole('admin')) {
            abort(403, __('message.action_is_unauthorized'));
        }

        Setting::updateOrCreate(
            ['type' => 'terms_condition', 'key' => 'terms_condition'],
            ['value' => $request->user_value]
        );

        Setting::updateOrCreate(
            ['type' => 'terms_condition_rider', 'key' => 'terms_condition_rider'],
            ['value' => $request->rider_value]
        );

        $message = __('message.update_form', ['form' => __('message.terms_condition')]);

        return redirect()->route('term-condition')->withsuccess($message);
    }

    public function billingStatement(Request $request)
    {
        $setting_data = Setting::where('type', 'billing_statement')->where('key', 'billing_statement')->first();
        $pageTitle = __('message.billing_statement');
        $assets = ['textarea'];
        return view('setting.billing_statement_form', compact('setting_data', 'pageTitle', 'assets'));
    }
    
    public function saveBillingStatement(Request $request)
    {
        if (env('APP_DEMO')) {
            return redirect()->route('billing-statement')->withErrors(__('message.demo_permission_denied'));
        }
        if (!auth()->user()->hasRole('admin')) {
            abort(403, __('message.action_is_unauthorized'));
        }
        $setting_data = [
            'type' => 'billing_statement',
            'key' => 'billing_statement',
            'value' => $request->value
        ];
        $result = Setting::updateOrCreate(['id' => $request->id], $setting_data);
        if ($result->wasRecentlyCreated) {
            $message = __('message.save_form', ['form' => __('message.billing_statement')]);
        } else {
            $message = __('message.update_form', ['form' => __('message.billing_statement')]);
        }

        return redirect()->route('billing-statement')->withsuccess($message);
    }

    public function privacyPolicy(Request $request)
    {
        $user_setting = Setting::where('type', 'privacy_policy')->where('key', 'privacy_policy')->first();
        $rider_setting = Setting::where('type', 'privacy_policy_rider')->where('key', 'privacy_policy_rider')->first();
        $pageTitle = __('message.privacy_policy');
        $assets = ['textarea'];

        return view('setting.privacy_policy_form', compact('user_setting', 'rider_setting', 'pageTitle', 'assets'));
    }

    public function savePrivacyPolicy(Request $request)
    {
        if (env('APP_DEMO')) {
            $message = __('message.demo_permission_denied');
            if (request()->is('api/*')) {
                return response()->json(['status' => true, 'message' => $message]);
            }
            if (request()->ajax()) {
                return response()->json(['status' => false, 'message' => $message, 'event' => 'validation']);
            }
            return redirect()->route('privacy-policy')->withErrors($message);
        }
        if (!auth()->user()->hasRole('admin')) {
            abort(403, __('message.action_is_unauthorized'));
        }

        Setting::updateOrCreate(
            ['type' => 'privacy_policy', 'key' => 'privacy_policy'],
            ['value' => $request->user_value]
        );

        Setting::updateOrCreate(
            ['type' => 'privacy_policy_rider', 'key' => 'privacy_policy_rider'],
            ['value' => $request->rider_value]
        );

        $message = __('message.update_form', ['form' => __('message.privacy_policy')]);

        return redirect()->route('privacy-policy')->withsuccess($message);
    }

    public function paymentSettingsUpdate(Request $request)
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, __('message.action_is_unauthorized'));
        }
        $data = $request->all();
        $result = PaymentGateway::updateOrCreate(['type' => request('type')], $data);
        uploadMediaFile($result, $request->gateway_image, 'gateway_image');
        return redirect()->route('setting.index', ['page' => 'payment-setting'])->withSuccess(__('message.updated'));
    }

    public function notificationSettingsUpdate(Request $request)
    {
        $app_setting = AppSetting::getData();

        AppSetting::updateOrCreate(['id' => $app_setting->id], ['notification_settings' => $request->notification_settings]);

        return redirect()->route('setting.index', ['page' => 'notification-setting'])->withSuccess(__('message.updated'));
    }

    public function saveInvoiceSetting(Request $request)
    {
        $data = $request->all();
        if ($request->is('api/*')) {
            foreach ($data as $req) {
                $input = [
                    'type' => $req['type'],
                    'key' => $req['key'],
                    'value' => $req['value'],
                ];
                Setting::updateOrCreate(['key' => $req['key'], 'type' => $req['type']], $input);
            }
        } else {
            if (isset($data['key']) && is_array($data['key'])) {
                foreach ($data['key'] as $key => $val) {
                    $value = isset($data['value'][$key]) ? $data['value'][$key] : null;
                    $input = [
                        'type' => isset($data['type'][$key]) ? $data['type'][$key] : null,
                        'key' => $val,
                        'value' => $value,
                    ];
                    $invoice = Setting::updateOrCreate(['key' => $val, 'type' => $data['type']], $input);
                }

                if ($request->hasFile('company_logo')) {
                    $invoice->clearMediaCollection('company_logo');
                    $mediaItem = $invoice->addMediaFromRequest('company_logo')->toMediaCollection('company_logo');
                    Setting::updateOrCreate(['key' => 'company_logo'], ['type' => 'order_invoice', 'value' => $mediaItem->getUrl()]);
                }
            }
        }
        $message = __('message.save_form', ['form' => __('message.setting')]);
        if (request()->is('api/*')) {
            return json_message_response($message);
        }
        if (isset($data['invoice_settings'])) {
            return redirect()->route('setting.index', ['page' => 'invoice-setting'])->withSuccess(__('message.updated'));
        } elseif (isset($data['register_settings'])) {
            return redirect()->route('setting.index', ['page' => 'register-setting'])->withSuccess(__('message.updated'));
        } else {
            return redirect()->back();
        }
    }
    public function updateAppSetting(Request $request)
    {
        $data = $request->all();

        $appSetting = AppSetting::updateOrCreate(['id' => $request->id], $data);
        $message = __('message.update_form', ['form' => __('message.setting')]);
        if ($appSetting->wasRecentlyCreated) {
            $message = __('message.save_form', ['form' => __('message.setting')]);
        }
        if (request()->is('api/*')) {
            return json_message_response($message);
        }
        if (isset($data['notification_settings'])) {
            return redirect()->route('setting.index', ['page' => 'notification-setting'])->withSuccess(__('message.updated'));
        } else if (isset($data['database_backup'])) {
            return redirect()->route('setting.index', ['page' => 'database-backup'])->withSuccess(__('message.updated'));
        } else {
            return redirect()->route('setting.index', ['page' => 'order-setting'])->withSuccess(__('message.updated'));
        }
    }

    public function settingUploadInvoiceImage(Request $request)
    {
        $data = $request->all();

        $result = Setting::updateOrCreate(['key' => request('key'), 'type' => request('type')], $data);
        $collection_name = request('key');

        if (isset($request->$collection_name) && $request->$collection_name != null) {
            $result->clearMediaCollection($collection_name);
            $result->addMediaFromRequest($collection_name)->toMediaCollection($collection_name);
        }

        $result->update([ 'value' => getSingleMedia($result, $collection_name, null) ]);

        if (request()->is('api/*')) {
            return json_message_response(__('message.save_form', ['form' => __('message.setting')]));
        } else {
            return redirect()->back();
        }
    }
    public function previousInvoice()
    {
        $today = Carbon::now()->format('d/m/Y');
        $companyName = Setting::where('type', 'order_invoice')->where('key', 'company_name')->first();
        $companyNumber = Setting::where('type', 'order_invoice')->where('key', 'company_contact_number')->first();
        $companyAddress = Setting::where('type', 'order_invoice')->where('key', 'company_address')->first();
        $invoice = Setting::where('type', 'order_invoice')->where('key', 'company_logo')->first();
        $pdf = Pdf::loadView('order.previousinvoice', compact('invoice', 'companyName', 'companyAddress', 'companyNumber', 'today'));
        return $pdf->stream('previous_invoice.pdf');
    }

    public function smsSettingsUpdate(Request $request)
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, __('message.action_is_unauthorized'));
        }

        $request['title'] = __('message.' . $request->type);
        $smsSetting = SMSSetting::where('type', $request->type)->first();

        if ($smsSetting) {
            $smsSetting->update($request->all());
        } else {
            SMSSetting::create($request->all());
        }

        return redirect()->route('setting.index', ['page' => 'sms-settings'])->withSuccess(__('message.updated'));
    }

    public function mailAlertSettingsUpdate(Request $request)
    {
        $data = $request->all();

        foreach(config('constant.mail_alert') as $key => $val){
            $input = [
                'type'  => 'mail_alert',
                'key'   => $key,
                'value' => $data[$key] ?? null,
            ];
            Setting::updateOrCreate(['key' => $key],$input);
        }

        return redirect()->route('setting.index', ['page' => 'mail-alert-setting'])->withSuccess( __('message.updated'));
    }
}
