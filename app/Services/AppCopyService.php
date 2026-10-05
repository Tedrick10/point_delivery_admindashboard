<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\LanguageVersionDetail;
use App\Models\Setting;
use Illuminate\Support\Collection;

class AppCopyService
{
    public const TYPE = 'app_copy';

    public const APPS = ['admin', 'user', 'rider'];

    public const FIELDS = ['title', 'text', 'description'];

    public const LOCALES = ['en', 'my'];

    public static function defaults(): array
    {
        $site = AppSetting::query()->first();
        $legacyName = trim((string) (SettingData('app_content', 'app_name') ?: ($site->site_name ?? '')));
        $legacyDesc = trim((string) ($site->site_description ?? ''));
        $brand = $legacyName !== '' ? $legacyName : 'Point Delivery';

        return [
            'admin' => [
                'en' => [
                    'title' => $brand,
                    'text' => 'Admin Panel',
                    'description' => $legacyDesc !== '' ? $legacyDesc : 'Manage orders, riders and branch operations.',
                ],
                'my' => [
                    'title' => $brand,
                    'text' => 'အက်မင် ပန်နယ်',
                    'description' => 'အော်ဒါ၊ စီးနင်းသူနှင့် ဘဏ်ခွဲလုပ်ငန်းများကို စီမံပါ။',
                ],
            ],
            'user' => [
                'en' => [
                    'title' => 'Point User',
                    'text' => 'Fast & reliable parcel delivery',
                    'description' => 'Send and track parcels with Point Delivery.',
                ],
                'my' => [
                    'title' => 'Point User',
                    'text' => 'မြန်ဆန်ယုံကြည်ရသော ပစ္စည်းပို့ဆောင်မှု',
                    'description' => 'Point Delivery ဖြင့် ပစ္စည်းပို့ပြီး လိုက်ကြည့်ပါ။',
                ],
            ],
            'rider' => [
                'en' => [
                    'title' => 'Point Delivery Partner',
                    'text' => 'Earn by delivering with confidence',
                    'description' => 'Accept and complete deliveries with Point Delivery Partner.',
                ],
                'my' => [
                    'title' => 'Point Delivery Partner',
                    'text' => 'ယုံကြည်စိတ်ချစွာ ပို့ဆောင်ပြီး ဝင်ငွေရှာပါ',
                    'description' => 'Point Delivery Partner ဖြင့် ပို့ဆောင်မှုများကို လက်ခံပြီး ပြီးစီးအောင် လုပ်ပါ။',
                ],
            ],
        ];
    }

    public static function key(string $app, string $field, string $locale): string
    {
        return $app.'_'.$field.'_'.$locale;
    }

    public static function get(string $app, string $field, ?string $locale = null): string
    {
        $app = in_array($app, self::APPS, true) ? $app : 'user';
        $field = in_array($field, self::FIELDS, true) ? $field : 'title';
        $locale = appCopyNormalizeLocale($locale);

        $stored = SettingData(self::TYPE, self::key($app, $field, $locale));
        if (is_string($stored) && trim($stored) !== '') {
            return $stored;
        }

        $fallback = SettingData(self::TYPE, self::key($app, $field, 'en'));
        if (is_string($fallback) && trim($fallback) !== '') {
            return $fallback;
        }

        $defaults = self::defaults();

        return (string) ($defaults[$app][$locale][$field] ?? $defaults[$app]['en'][$field] ?? '');
    }

    public static function all(): array
    {
        $out = [];
        foreach (self::APPS as $app) {
            foreach (self::LOCALES as $locale) {
                foreach (self::FIELDS as $field) {
                    $out[$app][$locale][$field] = self::get($app, $field, $locale);
                }
            }
        }

        return $out;
    }

    public static function bundle(string $app): array
    {
        $app = in_array($app, self::APPS, true) ? $app : 'user';
        $bundle = ['app' => $app];
        foreach (self::LOCALES as $locale) {
            foreach (self::FIELDS as $field) {
                $bundle[$locale][$field] = self::get($app, $field, $locale);
            }
        }

        return $bundle;
    }

    public static function screenPayload(): array
    {
        return [
            'apps' => [
                'admin' => [
                    'label' => __('message.sa_copy_app_admin'),
                    'hint' => __('message.sa_copy_app_admin_hint'),
                    'icon' => 'fa-desktop',
                ],
                'user' => [
                    'label' => __('message.sa_copy_app_user'),
                    'hint' => __('message.sa_copy_app_user_hint'),
                    'icon' => 'fa-mobile-alt',
                ],
                'rider' => [
                    'label' => __('message.sa_copy_app_rider'),
                    'hint' => __('message.sa_copy_app_rider_hint'),
                    'icon' => 'fa-motorcycle',
                ],
            ],
            'values' => self::all(),
            'saveRoute' => route('super-admin.system-settings.app-copy'),
        ];
    }

    public static function save(array $input): void
    {
        $copy = is_array($input['copy'] ?? null) ? $input['copy'] : $input;

        foreach (self::APPS as $app) {
            foreach (self::LOCALES as $locale) {
                foreach (self::FIELDS as $field) {
                    if (! isset($copy[$app][$locale][$field])) {
                        continue;
                    }
                    Setting::updateOrCreate(
                        ['type' => self::TYPE, 'key' => self::key($app, $field, $locale)],
                        ['value' => trim((string) $copy[$app][$locale][$field])]
                    );
                }
            }
        }

        $adminTitle = self::get('admin', 'title', 'en');
        $adminDesc = self::get('admin', 'description', 'en');
        if ($adminTitle !== '') {
            Setting::updateOrCreate(
                ['type' => 'app_content', 'key' => 'app_name'],
                ['value' => $adminTitle]
            );
            $settings = AppSetting::query()->first();
            if ($settings) {
                $settings->site_name = $adminTitle;
                if ($adminDesc !== '') {
                    $settings->site_description = $adminDesc;
                }
                $settings->save();
            }
        }

        $version = LanguageVersionDetail::query()->find(1);
        if ($version) {
            $version->version_no = (int) $version->version_no + 1;
            $version->save();
        }
    }

    public static function overlayKeywords(string $app, string $locale, $content): array
    {
        $map = [
            9 => self::get($app, 'title', $locale),
            3 => self::get($app, 'title', $locale),
            6 => self::get($app, 'text', $locale),
            40 => self::get($app, 'description', $locale),
        ];

        $rows = $content instanceof Collection ? $content : collect($content);

        return $rows->map(function ($row) use ($map) {
            $id = (int) (is_array($row) ? ($row['keyword_id'] ?? 0) : 0);
            if (isset($map[$id]) && trim((string) $map[$id]) !== '') {
                $row['keyword_value'] = $map[$id];
            }

            return $row;
        })->values()->all();
    }
}
