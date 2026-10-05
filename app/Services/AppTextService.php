<?php

namespace App\Services;

use App\Models\DefaultKeyword;
use App\Models\LanguageDefaultList;
use App\Models\LanguageList;
use App\Models\LanguageWithKeyword;
use App\Models\Screen;
use App\Models\Setting;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\File;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

class AppTextService
{
    public const KEYWORD_TYPE = 'app_keyword';

    public const APPS = ['admin', 'user', 'rider'];

    public const LOCALES = ['en', 'my'];

    public static function ensureMobileLocales(): void
    {
        foreach ([
            'en' => ['default_id' => 78, 'name' => 'English', 'country' => 'en-US', 'default' => 1],
            'my' => ['default_id' => 38, 'name' => 'မြန်မာ', 'country' => 'my-MM', 'default' => 0],
        ] as $code => $meta) {
            $list = LanguageList::query()->where('language_code', $code)->first();
            if (! $list) {
                $default = LanguageDefaultList::query()->find($meta['default_id']);
                $list = LanguageList::query()->create([
                    'language_id' => $default?->id ?? $meta['default_id'],
                    'language_name' => $default?->languageName ?? $meta['name'],
                    'language_code' => $code,
                    'country_code' => $default?->countryCode ?? $meta['country'],
                    'is_rtl' => 0,
                    'status' => 1,
                    'is_default' => $meta['default'],
                ]);
            } elseif ((int) $list->status !== 1) {
                $list->status = 1;
                $list->save();
            }

            self::syncKeywordsForLanguage((int) $list->id);
        }
    }

    public static function syncKeywordsForLanguage(int $languageId): void
    {
        $existing = LanguageWithKeyword::query()
            ->where('language_id', $languageId)
            ->pluck('keyword_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $existingMap = array_fill_keys($existing, true);

        $defaults = DefaultKeyword::query()->orderBy('keyword_id')->get(['keyword_id', 'screen_id', 'keyword_value']);
        $rows = [];
        foreach ($defaults as $default) {
            $kid = (int) $default->keyword_id;
            if (isset($existingMap[$kid])) {
                continue;
            }
            $rows[] = [
                'language_id' => $languageId,
                'keyword_id' => $kid,
                'screen_id' => $default->screen_id,
                'keyword_value' => (string) $default->keyword_value,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if (count($rows) >= 200) {
                LanguageWithKeyword::query()->insert($rows);
                $rows = [];
            }
        }
        if ($rows !== []) {
            LanguageWithKeyword::query()->insert($rows);
        }
    }

    public static function screenPayload(string $app = 'admin', ?string $q = null, $screenId = null, int $page = 1, int $perPage = 40): array
    {
        $app = in_array($app, self::APPS, true) ? $app : 'admin';
        if ($app !== 'admin') {
            self::ensureMobileLocales();
        }

        $branding = AppCopyService::all();
        $screens = Screen::query()->orderBy('screenName')->get(['screenId', 'screenName']);

        if ($app === 'admin') {
            $paginator = self::paginateAdminTexts($q, $page, $perPage);
        } else {
            $paginator = self::paginateMobileTexts($app, $q, $screenId, $page, $perPage);
        }

        return [
            'app' => $app,
            'q' => (string) $q,
            'screenId' => $screenId !== null && $screenId !== '' ? (int) $screenId : null,
            'screens' => $screens,
            'branding' => $branding,
            'rows' => $paginator,
            'apps' => [
                'admin' => ['label' => __('message.sa_copy_app_admin'), 'icon' => 'fa-desktop', 'count' => self::adminKeyCount()],
                'user' => ['label' => __('message.sa_copy_app_user'), 'icon' => 'fa-mobile-alt', 'count' => DefaultKeyword::query()->count()],
                'rider' => ['label' => __('message.sa_copy_app_rider'), 'icon' => 'fa-motorcycle', 'count' => DefaultKeyword::query()->count()],
            ],
            'saveRoute' => route('super-admin.system-settings.app-copy'),
            'brandingSaveRoute' => route('super-admin.system-settings.app-copy'),
        ];
    }

    public static function adminKeyCount(): int
    {
        return count(self::flattenLang(self::loadMessageFile('en')));
    }

    public static function loadMessageFile(string $locale): array
    {
        $path = resource_path('lang/'.$locale.'/message.php');
        if (! is_file($path)) {
            return [];
        }
        $data = include $path;

        return is_array($data) ? $data : [];
    }

    public static function flattenLang(array $data): array
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($data), RecursiveIteratorIterator::SELF_FIRST);
        $path = [];
        $flat = [];
        foreach ($iterator as $key => $value) {
            $path[$iterator->getDepth()] = $key;
            if (! is_array($value)) {
                $flat[implode('|', array_slice($path, 0, $iterator->getDepth() + 1))] = (string) $value;
            }
        }

        return $flat;
    }

    public static function paginateAdminTexts(?string $q, int $page, int $perPage): LengthAwarePaginator
    {
        $en = self::flattenLang(self::loadMessageFile('en'));
        $my = self::flattenLang(self::loadMessageFile('my'));
        $keys = array_values(array_unique(array_merge(array_keys($en), array_keys($my))));
        sort($keys, SORT_NATURAL | SORT_FLAG_CASE);

        $q = trim((string) $q);
        if ($q !== '') {
            $needle = mb_strtolower($q);
            $keys = array_values(array_filter($keys, function ($key) use ($en, $my, $needle) {
                $hay = mb_strtolower($key.' '.($en[$key] ?? '').' '.($my[$key] ?? ''));

                return str_contains($hay, $needle);
            }));
        }

        $total = count($keys);
        $page = max(1, $page);
        $perPage = max(10, min(100, $perPage));
        $slice = array_slice($keys, ($page - 1) * $perPage, $perPage);
        $items = [];
        foreach ($slice as $key) {
            $items[] = [
                'id' => $key,
                'label' => $key,
                'group' => 'message',
                'en' => self::normalizeMyanmarText((string) ($en[$key] ?? '')),
                'my' => self::normalizeMyanmarText((string) ($my[$key] ?? '')),
            ];
        }

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => route('super-admin.screens.show', 'app-copy'),
                'query' => array_filter([
                    'app' => 'admin',
                    'q' => $q !== '' ? $q : null,
                ]),
            ]
        );
    }

    public static function paginateMobileTexts(string $app, ?string $q, $screenId, int $page, int $perPage): LengthAwarePaginator
    {
        $enList = LanguageList::query()->where('language_code', 'en')->first();
        $myList = LanguageList::query()->where('language_code', 'my')->first();
        $enMap = self::keywordMapForLanguage((int) optional($enList)->id);
        $myMap = self::keywordMapForLanguage((int) optional($myList)->id);
        $userOverrides = self::overrideMap($app === 'user' ? 'user' : 'rider');

        $query = DefaultKeyword::query()->with('screen')->orderBy('screen_id')->orderBy('keyword_id');
        if ($screenId !== null && $screenId !== '') {
            $query->where('screen_id', (int) $screenId);
        }
        $q = trim((string) $q);
        if ($q !== '') {
            $query->where(function ($builder) use ($q) {
                $builder->where('keyword_name', 'like', '%'.$q.'%')
                    ->orWhere('keyword_value', 'like', '%'.$q.'%')
                    ->orWhere('keyword_id', $q);
            });
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', max(1, $page))
            ->appends(array_filter([
                'app' => $app,
                'q' => $q !== '' ? $q : null,
                'screen_id' => $screenId !== null && $screenId !== '' ? (int) $screenId : null,
            ]));

        $paginator->setCollection($paginator->getCollection()->map(function (DefaultKeyword $row) use ($enMap, $myMap, $userOverrides, $app) {
            $kid = (int) $row->keyword_id;
            $baseEn = $enMap[$kid] ?? (string) $row->keyword_value;
            $baseMy = $myMap[$kid] ?? (string) $row->keyword_value;

            return [
                'id' => (string) $kid,
                'label' => $row->keyword_name ?: ('keyword_'.$kid),
                'group' => optional($row->screen)->screenName ?: ('Screen '.$row->screen_id),
                'en' => $userOverrides[$kid]['en'] ?? $baseEn,
                'my' => $userOverrides[$kid]['my'] ?? $baseMy,
                'keyword_id' => $kid,
                'screen_id' => $row->screen_id,
            ];
        }));

        $paginator->withPath(route('super-admin.screens.show', 'app-copy'));

        return $paginator;
    }

    public static function keywordMapForLanguage(int $languageId): array
    {
        if ($languageId <= 0) {
            return [];
        }

        return LanguageWithKeyword::query()
            ->where('language_id', $languageId)
            ->pluck('keyword_value', 'keyword_id')
            ->mapWithKeys(fn ($value, $key) => [(int) $key => (string) $value])
            ->all();
    }

    public static function overrideMap(string $app): array
    {
        $prefix = $app.'_';
        $rows = Setting::query()
            ->where('type', self::KEYWORD_TYPE)
            ->where('key', 'like', $prefix.'%')
            ->get(['key', 'value']);
        $map = [];
        foreach ($rows as $row) {
            $parts = explode('_', (string) $row->key);
            // user_123_en
            if (count($parts) < 3) {
                continue;
            }
            $locale = array_pop($parts);
            array_shift($parts); // app
            $keywordId = (int) implode('_', $parts);
            if ($keywordId <= 0 || ! in_array($locale, self::LOCALES, true)) {
                continue;
            }
            if (! is_string($row->value) || trim($row->value) === '') {
                continue;
            }
            $map[$keywordId][$locale] = $row->value;
        }

        return $map;
    }

    public static function overrideKey(string $app, int $keywordId, string $locale): string
    {
        return $app.'_'.$keywordId.'_'.$locale;
    }

    public static function save(array $input): void
    {
        $app = (string) ($input['app'] ?? 'admin');
        $app = in_array($app, self::APPS, true) ? $app : 'admin';

        if (! empty($input['copy']) && is_array($input['copy'])) {
            AppCopyService::save(['copy' => $input['copy']]);
        }

        $texts = is_array($input['texts'] ?? null) ? $input['texts'] : [];
        if ($texts === []) {
            return;
        }

        if ($app === 'admin') {
            self::saveAdminTexts($texts);
        } else {
            self::ensureMobileLocales();
            self::saveMobileTexts($app, $texts);
            if (function_exists('updateLanguageVersion')) {
                updateLanguageVersion();
            }
        }
    }

    public static function saveAdminTexts(array $texts): void
    {
        $en = self::flattenLang(self::loadMessageFile('en'));
        $my = self::flattenLang(self::loadMessageFile('my'));

        foreach ($texts as $key => $values) {
            $key = (string) $key;
            if ($key === '') {
                continue;
            }
            if (array_key_exists('en', (array) $values)) {
                $en[$key] = self::normalizeMyanmarText((string) $values['en']);
            }
            if (array_key_exists('my', (array) $values)) {
                $my[$key] = self::normalizeMyanmarText((string) $values['my']);
            }
        }

        self::writeMessageFile('en', $en);
        self::writeMessageFile('my', $my);
    }

    public static function writeMessageFile(string $locale, array $flat): void
    {
        $nested = flattenToMultiDimensional($flat, '|');
        $path = resource_path('lang/'.$locale.'/message.php');
        $export = var_export($nested, true);
        File::put($path, "<?php\n\nreturn ".$export.";\n");
    }

    public static function saveMobileTexts(string $app, array $texts): void
    {
        $enList = LanguageList::query()->where('language_code', 'en')->first();
        $myList = LanguageList::query()->where('language_code', 'my')->first();
        if (! $enList || ! $myList) {
            return;
        }

        foreach ($texts as $keywordId => $values) {
            $keywordId = (int) $keywordId;
            if ($keywordId <= 0 || ! is_array($values)) {
                continue;
            }

            foreach (self::LOCALES as $locale) {
                if (! array_key_exists($locale, $values)) {
                    continue;
                }
                $value = self::normalizeMyanmarText(trim((string) $values[$locale]));
                Setting::updateOrCreate(
                    ['type' => self::KEYWORD_TYPE, 'key' => self::overrideKey($app, $keywordId, $locale)],
                    ['value' => $value]
                );

                // Keep shared base in sync for the primary "user" catalog so offline/default stays useful.
                if ($app === 'user') {
                    $languageId = $locale === 'my' ? (int) $myList->id : (int) $enList->id;
                    $default = DefaultKeyword::query()->where('keyword_id', $keywordId)->first();
                    LanguageWithKeyword::updateOrCreate(
                        ['language_id' => $languageId, 'keyword_id' => $keywordId],
                        [
                            'screen_id' => $default?->screen_id,
                            'keyword_value' => $value,
                        ]
                    );
                }
            }

            // Keep branding shortcuts in sync when core keywords change.
            if (in_array($keywordId, [3, 9], true) && isset($values['en'])) {
                Setting::updateOrCreate(
                    ['type' => AppCopyService::TYPE, 'key' => AppCopyService::key($app, 'title', 'en')],
                    ['value' => trim((string) $values['en'])]
                );
            }
            if (in_array($keywordId, [3, 9], true) && isset($values['my'])) {
                Setting::updateOrCreate(
                    ['type' => AppCopyService::TYPE, 'key' => AppCopyService::key($app, 'title', 'my')],
                    ['value' => trim((string) $values['my'])]
                );
            }
            if ($keywordId === 6) {
                foreach (self::LOCALES as $locale) {
                    if (isset($values[$locale])) {
                        Setting::updateOrCreate(
                            ['type' => AppCopyService::TYPE, 'key' => AppCopyService::key($app, 'text', $locale)],
                            ['value' => trim((string) $values[$locale])]
                        );
                    }
                }
            }
            if ($keywordId === 40) {
                foreach (self::LOCALES as $locale) {
                    if (isset($values[$locale])) {
                        Setting::updateOrCreate(
                            ['type' => AppCopyService::TYPE, 'key' => AppCopyService::key($app, 'description', $locale)],
                            ['value' => trim((string) $values[$locale])]
                        );
                    }
                }
            }
        }
    }

    public static function overlayAllKeywords(string $app, string $locale, $content): array
    {
        $locale = appCopyNormalizeLocale($locale);
        $overrides = self::overrideMap($app);
        $branding = AppCopyService::overlayKeywords($app, $locale, $content);
        $rows = collect($branding);

        return $rows->map(function ($row) use ($overrides, $locale) {
            $id = (int) (is_array($row) ? ($row['keyword_id'] ?? 0) : 0);
            if ($id > 0 && isset($overrides[$id][$locale]) && trim((string) $overrides[$id][$locale]) !== '') {
                $row['keyword_value'] = $overrides[$id][$locale];
            }

            return $row;
        })->values()->all();
    }

    /**
     * Repair known Zawgyi-as-Unicode corruptions that appear in older Myanmar strings.
     */
    public static function normalizeMyanmarText(string $value): string
    {
        $map = [
            'ကြှနျုပျတို့အကွောငျး' => 'ကျွန်ုပ်တို့အကြောင်း',
            'ကြှနျုပျတို့ကိုဆကျသှယျရနျ' => 'ကျွန်ုပ်တို့ကို ဆက်သွယ်ရန်',
        ];

        return strtr($value, $map);
    }
}
