<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SaUiTextService
{
    public const TYPE = 'sa_ui_text';

    public static function normalizeLocale(?string $locale = null): string
    {
        $locale = strtolower(trim((string) ($locale ?: app()->getLocale())));
        if (str_starts_with($locale, 'my') || str_starts_with($locale, 'mm')) {
            return 'my';
        }

        return 'en';
    }

    public static function cacheKey(string $locale): string
    {
        return 'sa_ui_text_bundle_'.$locale;
    }

    public static function forgetCache(?string $locale = null): void
    {
        $locale = self::normalizeLocale($locale);
        Cache::forget(self::cacheKey($locale));
    }

    public static function bundle(?string $locale = null): array
    {
        $locale = self::normalizeLocale($locale);

        return Cache::remember(self::cacheKey($locale), 60, function () use ($locale) {
            $raw = Setting::query()
                ->where('type', self::TYPE)
                ->where('key', 'bundle_'.$locale)
                ->value('value');

            $decoded = [];
            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, true) ?: [];
            }

            return [
                'shell' => self::arrayMap($decoded['shell'] ?? null),
                'pages' => self::pagesArray($decoded['pages'] ?? null),
            ];
        });
    }

    public static function text(string $key, string $default, string $scope = 'shell', ?string $pathname = null, ?string $locale = null): string
    {
        $key = trim($key);
        if ($key === '') {
            return $default;
        }

        $bundle = self::bundle($locale);
        if ($scope === 'page') {
            $pathname = $pathname ?: (string) (request()?->getPathInfo() ?: '/');
            $page = $bundle['pages'][$pathname] ?? [];
            if (is_array($page) && array_key_exists($key, $page) && trim((string) $page[$key]) !== '') {
                return (string) $page[$key];
            }
        }

        $shell = $bundle['shell'] ?? [];
        if (is_array($shell) && array_key_exists($key, $shell) && trim((string) $shell[$key]) !== '') {
            return (string) $shell[$key];
        }

        return $default;
    }

    public static function forPage(string $pathname, ?string $locale = null): array
    {
        $bundle = self::bundle($locale);
        $page = $bundle['pages'][$pathname] ?? [];

        return [
            'shell' => self::jsonObject($bundle['shell'] ?? []),
            'page' => self::jsonObject(is_array($page) ? $page : []),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function arrayMap(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $k => $v) {
            if (! is_string($k) && ! is_int($k)) {
                continue;
            }
            $key = trim((string) $k);
            if ($key === '') {
                continue;
            }
            $out[$key] = is_scalar($v) ? (string) $v : '';
        }

        return $out;
    }

    /**
     * @return array<string, array<string, string>>
     */
    protected static function pagesArray(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $path => $map) {
            $out[(string) $path] = self::arrayMap($map);
        }

        return $out;
    }

    /**
     * @param  array<string, string>  $map
     */
    protected static function jsonObject(array $map): object
    {
        return (object) $map;
    }

    /**
     * @param  array<string, string>  $shell
     * @param  array<string, string>  $page
     */
    public static function save(string $locale, array $shell, string $pathname, array $page, bool $replacePage = false): array
    {
        $locale = self::normalizeLocale($locale);
        $bundle = self::bundle($locale);

        // Merge so one page save cannot wipe other labels
        $bundle['shell'] = array_merge(
            is_array($bundle['shell'] ?? null) ? $bundle['shell'] : [],
            self::sanitizeMap($shell)
        );

        $pathname = trim($pathname) !== '' ? $pathname : '/';
        $existingPage = is_array($bundle['pages'][$pathname] ?? null) ? $bundle['pages'][$pathname] : [];
        $bundle['pages'][$pathname] = $replacePage
            ? self::sanitizeMap($page)
            : array_merge($existingPage, self::sanitizeMap($page));

        $bundle['pages'] = array_filter(
            $bundle['pages'],
            static fn ($map) => is_array($map) && $map !== []
        );

        Setting::updateOrCreate(
            ['type' => self::TYPE, 'key' => 'bundle_'.$locale],
            ['value' => json_encode($bundle, JSON_UNESCAPED_UNICODE)]
        );

        self::forgetCache($locale);

        return self::forPage($pathname, $locale);
    }

    public static function reset(string $locale, ?string $pathname = null): array
    {
        $locale = self::normalizeLocale($locale);

        if ($pathname === null || $pathname === '') {
            $bundle = ['shell' => [], 'pages' => []];
        } else {
            $bundle = self::bundle($locale);
            $bundle['shell'] = [];
            unset($bundle['pages'][$pathname]);
        }

        Setting::updateOrCreate(
            ['type' => self::TYPE, 'key' => 'bundle_'.$locale],
            ['value' => json_encode($bundle, JSON_UNESCAPED_UNICODE)]
        );

        self::forgetCache($locale);

        return self::forPage($pathname ?: '/', $locale);
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
            $out[$key] = $value;
        }

        return $out;
    }
}
