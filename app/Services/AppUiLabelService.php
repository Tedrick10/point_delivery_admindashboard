<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Super Admin inline labels for User/Rider Flutter apps (string keys, like SA panel).
 */
class AppUiLabelService
{
    public const TYPE = 'app_ui_label';

    public static function normalizeLocale(?string $locale = null): string
    {
        $locale = strtolower(trim((string) ($locale ?: app()->getLocale())));
        if (str_starts_with($locale, 'my') || str_starts_with($locale, 'mm')) {
            return 'my';
        }

        return 'en';
    }

    public static function normalizeApp(?string $app): string
    {
        $app = strtolower(trim((string) $app));

        return in_array($app, ['user', 'rider'], true) ? $app : 'user';
    }

    public static function cacheKey(string $app, string $locale): string
    {
        return 'app_ui_label_'.$app.'_'.$locale;
    }

    public static function settingKey(string $app, string $locale): string
    {
        return 'bundle_'.$app.'_'.$locale;
    }

    public static function forgetCache(string $app, ?string $locale = null): void
    {
        $app = self::normalizeApp($app);
        if ($locale !== null) {
            Cache::forget(self::cacheKey($app, self::normalizeLocale($locale)));

            return;
        }
        foreach (['en', 'my'] as $loc) {
            Cache::forget(self::cacheKey($app, $loc));
        }
    }

    /**
     * @return array<string, string>
     */
    public static function bundle(string $app, ?string $locale = null): array
    {
        $app = self::normalizeApp($app);
        $locale = self::normalizeLocale($locale);

        return Cache::remember(self::cacheKey($app, $locale), 60, function () use ($app, $locale) {
            $raw = Setting::query()
                ->where('type', self::TYPE)
                ->where('key', self::settingKey($app, $locale))
                ->value('value');

            if (! is_string($raw) || $raw === '') {
                return [];
            }

            $decoded = json_decode($raw, true);

            return self::sanitizeMap(is_array($decoded) ? $decoded : []);
        });
    }

    public static function text(string $app, string $key, string $default, ?string $locale = null): string
    {
        $key = trim($key);
        if ($key === '') {
            return $default;
        }
        $bundle = self::bundle($app, $locale);
        if (isset($bundle[$key]) && trim($bundle[$key]) !== '') {
            return $bundle[$key];
        }

        return $default;
    }

    /**
     * @param  array<mixed>  $labels
     * @return array<string, string>
     */
    public static function save(string $app, ?string $locale, array $labels): array
    {
        $app = self::normalizeApp($app);
        $locale = self::normalizeLocale($locale);
        $bundle = array_merge(self::bundle($app, $locale), self::sanitizeMap($labels));

        Setting::updateOrCreate(
            ['type' => self::TYPE, 'key' => self::settingKey($app, $locale)],
            ['value' => json_encode($bundle, JSON_UNESCAPED_UNICODE)]
        );

        self::forgetCache($app, $locale);

        return $bundle;
    }

    public static function reset(string $app): int
    {
        $app = self::normalizeApp($app);
        $deleted = (int) Setting::query()
            ->where('type', self::TYPE)
            ->where('key', 'like', 'bundle_'.$app.'_%')
            ->delete();
        self::forgetCache($app);

        return $deleted;
    }

    /**
     * @param  array<mixed>  $map
     * @return array<string, string>
     */
    protected static function sanitizeMap(array $map): array
    {
        $out = [];
        foreach ($map as $key => $value) {
            $key = trim((string) $key);
            $value = trim((string) $value);
            if ($key === '' || $value === '') {
                continue;
            }
            $out[$key] = AppTextService::normalizeMyanmarText($value);
        }

        return $out;
    }
}
