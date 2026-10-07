<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SettingRequest;
use App\Models\AppSetting;
use App\Models\Setting;
use App\Services\AppCopyService;
use App\Services\AppTextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SystemSettingsController extends Controller
{
    public static function generalPayload(): array
    {
        $settings = AppSetting::first() ?? new AppSetting;

        return [
            'settings' => $settings,
            'page' => 'general-setting',
            'envSettting' => [],
            'saveRoute' => route('super-admin.system-settings.general'),
            'hideThemeColor' => true,
            'brandColors' => array_values(brandColorPacks()),
            'brandFonts' => array_values(brandFontPacks()),
            'activeBrandColor' => brandColorId(),
            'activeBrandFont' => brandFontId(),
            'themePacks' => array_values(uiThemePacks()),
            'activeTheme' => uiThemePackId(),
        ];
    }

    public static function apiServerPayload(): array
    {
        $apiBaseUrl = SettingData('API_SERVER', 'API_SERVER_BASE_URL') ?: '';
        $browserUrl = rtrim((string) request()->getSchemeAndHttpHost(), '/');
        $wifiIp = detectMachineLanIp();
        $wifiUrl = detectMachineLanBaseUrl();
        $detectedUrl = $wifiUrl ?: $browserUrl;
        if ($apiBaseUrl === '') {
            $apiBaseUrl = $detectedUrl;
        }

        return [
            'page' => 'api-server-setting',
            'apiBaseUrl' => $apiBaseUrl,
            'detectedUrl' => $detectedUrl,
            'browserUrl' => $browserUrl,
            'wifiIp' => $wifiIp,
            'wifiUrl' => $wifiUrl,
            'saveRoute' => route('super-admin.system-settings.api-server'),
        ];
    }

    public static function companyContactPayload(): array
    {
        $keys = [
            'company_name',
            'company_contact_number',
            'company_hotline',
            'company_address',
            'company_email',
            'express_phone',
        ];
        $defaults = [
            'company_name' => 'Point Delivery',
            'company_contact_number' => '09400080670, 09402578059',
            'company_hotline' => '09400080670, 09402578059',
            'company_address' => '62A, 104A*105.',
            'company_email' => 'point@gmail.com',
            'express_phone' => '09765650634',
        ];
        $fields = [];
        foreach ($keys as $key) {
            $stored = trim((string) (SettingData('order_invoice', $key) ?: ''));
            $fields[$key] = $stored !== '' ? $stored : ($defaults[$key] ?? '');
        }

        return [
            'page' => 'company-contact',
            'fields' => $fields,
            'saveRoute' => route('super-admin.system-settings.company-contact'),
        ];
    }

    public static function appStorePayload(): array
    {
        $apps = [
            'user' => [
                'type' => 'USER_APP_VERSION',
                'title_key' => 'sa_app_store_user_title',
                'subtitle_key' => 'sa_app_store_user_sub',
                'package_android' => 'com.pointuser.com',
                'package_ios' => 'com.pointdelivery.userapp.com',
                'icon' => 'fa-mobile-alt',
            ],
            'rider' => [
                'type' => 'RIDER_APP_VERSION',
                'title_key' => 'sa_app_store_rider_title',
                'subtitle_key' => 'sa_app_store_rider_sub',
                'package_android' => 'com.pointdelivery.com',
                'package_ios' => 'com.pointdelivery.riderapp.com',
                'icon' => 'fa-motorcycle',
            ],
        ];

        $fields = [
            'ANDROID_VERSION_CODE',
            'ANDROID_FORCE_UPDATE',
            'PLAYSTORE_URL',
            'IOS_VERSION',
            'IOS_FORCE_UPDATE',
            'APPSTORE_URL',
        ];

        $sections = [];
        foreach ($apps as $appKey => $meta) {
            $type = $meta['type'];
            $values = [];
            foreach ($fields as $suffix) {
                $key = $type.'_'.$suffix;
                $val = SettingData($type, $key);
                if ($val === null || $val === '') {
                    $val = SettingData('APP_VERSION', 'APP_VERSION_'.$suffix);
                }
                if (($val === null || $val === '') && $suffix === 'PLAYSTORE_URL') {
                    $val = 'https://play.google.com/store/apps/details?id='.$meta['package_android'];
                }
                if (($val === null || $val === '') && in_array($suffix, ['ANDROID_FORCE_UPDATE', 'IOS_FORCE_UPDATE'], true)) {
                    $val = '0';
                }
                $values[$suffix] = $val ?? '';
            }
            $sections[$appKey] = array_merge($meta, ['values' => $values]);
        }

        return [
            'page' => 'app-store-update',
            'sections' => $sections,
            'saveRoute' => route('super-admin.system-settings.app-store-update'),
        ];
    }

    public function updateAppStore(Request $request): RedirectResponse
    {
        $allowedTypes = ['USER_APP_VERSION', 'RIDER_APP_VERSION'];
        $allowedSuffixes = [
            'ANDROID_VERSION_CODE',
            'ANDROID_FORCE_UPDATE',
            'PLAYSTORE_URL',
            'IOS_VERSION',
            'IOS_FORCE_UPDATE',
            'APPSTORE_URL',
        ];

        $payload = $request->input('apps', []);
        if (! is_array($payload)) {
            return redirect()
                ->route('super-admin.screens.show', 'app-store-update')
                ->with('error', __('message.something_wrong') ?? 'Invalid request.');
        }

        foreach ($payload as $type => $fields) {
            if (! in_array($type, $allowedTypes, true) || ! is_array($fields)) {
                continue;
            }
            foreach ($allowedSuffixes as $suffix) {
                if (! array_key_exists($suffix, $fields)) {
                    continue;
                }
                $value = $fields[$suffix];
                if (is_string($value)) {
                    $value = trim($value);
                }
                if (in_array($suffix, ['ANDROID_FORCE_UPDATE', 'IOS_FORCE_UPDATE'], true)) {
                    $value = ((string) $value === '1') ? '1' : '0';
                }
                Setting::updateOrCreate(
                    ['type' => $type, 'key' => $type.'_'.$suffix],
                    ['value' => ($value !== null && $value !== '') ? $value : null]
                );
            }
        }

        return redirect()
            ->route('super-admin.screens.show', 'app-store-update')
            ->with('success', __('message.updated'));
    }

    public function updateCompanyContact(Request $request): RedirectResponse
    {
        $keys = [
            'company_name',
            'company_contact_number',
            'company_hotline',
            'company_address',
            'company_email',
            'express_phone',
        ];

        foreach ($keys as $key) {
            $value = trim((string) $request->input($key, ''));
            Setting::updateOrCreate(
                ['type' => 'order_invoice', 'key' => $key],
                ['value' => $value !== '' ? $value : null]
            );
        }

        return redirect()
            ->route('super-admin.screens.show', 'company-contact')
            ->with('success', __('message.updated'));
    }

    public function updateGeneral(SettingRequest $request): RedirectResponse
    {
        $request->validate([
            'brand_color' => 'nullable|in:point,amber,delivery_job',
            'brand_font' => 'nullable|in:outfit,z17_strength,rubik',
            'site_name' => 'nullable|string|max:191',
        ]);

        $brandColorId = (string) ($request->input('brand_color') ?: brandColorId());
        $brandFontId = (string) ($request->input('brand_font') ?: brandFontId());
        $brandHex = brandColorHex($brandColorId);

        Setting::updateOrCreate(
            ['type' => 'APP_THEME', 'key' => 'BRAND_COLOR'],
            ['value' => $brandColorId]
        );
        Setting::updateOrCreate(
            ['type' => 'APP_THEME', 'key' => 'BRAND_FONT'],
            ['value' => $brandFontId]
        );
        if ($request->filled('ui_theme')) {
            Setting::updateOrCreate(
                ['type' => 'APP_THEME', 'key' => 'UI_THEME_PACK'],
                ['value' => (string) $request->input('ui_theme')]
            );
        }

        $payload = [
            'color' => $brandHex,
        ];
        if ($request->filled('site_name')) {
            $payload['site_name'] = str_replace("'", '', str_replace('"', '', (string) $request->site_name));
        }

        $existing = AppSetting::first();
        $res = AppSetting::updateOrCreate(
            ['id' => $request->id ?: optional($existing)->id],
            $payload
        );

        if (! empty($payload['site_name'])) {
            envChanges('APP_NAME', $payload['site_name']);
        }

        uploadMediaFile($res, $request->site_logo, 'site_logo');
        uploadMediaFile($res, $request->site_favicon, 'site_favicon');

        appSettingData('set');

        return redirect()
            ->route('super-admin.screens.show', 'general-setting')
            ->with('success', __('message.updated'));
    }

    public function updateApiServer(Request $request): RedirectResponse
    {
        $data = $request->all();
        $keys = $data['key'] ?? [];
        $values = $data['value'] ?? [];
        $types = $data['type'] ?? [];

        if (! is_array($keys) || count($keys) === 0) {
            return redirect()
                ->route('super-admin.screens.show', 'api-server-setting')
                ->with('error', __('message.something_wrong') ?? 'Invalid request.');
        }

        foreach ($keys as $key => $val) {
            $value = $values[$key] ?? null;
            if (is_string($value)) {
                $value = trim($value);
            }
            $input = [
                'type' => $types[$key] ?? 'API_SERVER',
                'key' => $val,
                'value' => ($value !== null && $value !== '') ? $value : null,
            ];
            Setting::updateOrCreate(['type' => $input['type'], 'key' => $input['key']], $input);
        }

        return redirect()
            ->route('super-admin.screens.show', 'api-server-setting')
            ->with('success', __('message.updated'));
    }

    public function updateAppCopy(Request $request): RedirectResponse
    {
        $app = (string) $request->input('app', 'admin');
        if (! in_array($app, AppTextService::APPS, true)) {
            $app = 'admin';
        }

        $request->validate([
            'app' => 'required|in:admin,user,rider',
            'texts' => 'nullable|array',
            'copy' => 'nullable|array',
        ]);

        AppTextService::save($request->all());

        return redirect()
            ->route('super-admin.screens.show', [
                'screen' => 'app-copy',
                'app' => $app,
                'q' => $request->input('q'),
                'screen_id' => $request->input('screen_id'),
                'page' => $request->input('page'),
            ])
            ->with('success', __('message.sa_copy_saved'));
    }
}