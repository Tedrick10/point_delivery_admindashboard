<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DefaultKeyword;
use App\Models\LanguageList;
use App\Models\LanguageWithKeyword;
use App\Services\AppCopyService;
use App\Services\AppTextService;
use App\Services\AppUiLabelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppUiTextController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        if (! isSuperAdmin($request->user())) {
            return json_message_response(__('message.action_is_unauthorized'), 403);
        }

        $app = $this->resolveApp($request->get('app'));
        $locale = appCopyNormalizeLocale($request->get('locale') ?: app()->getLocale());
        AppTextService::ensureMobileLocales();

        $language = LanguageList::query()->where('language_code', $locale)->where('status', 1)->first()
            ?: LanguageList::query()->where('language_code', 'en')->first();

        $rows = [];
        if ($language) {
            $keywords = LanguageWithKeyword::query()
                ->where('language_id', (int) $language->id)
                ->orderBy('keyword_id')
                ->get(['keyword_id', 'keyword_value', 'screen_id']);
            $defaults = DefaultKeyword::query()->pluck('keyword_value', 'keyword_id');
            $overrides = AppTextService::overrideMap($app);

            foreach ($keywords as $row) {
                $id = (int) $row->keyword_id;
                $value = (string) $row->keyword_value;
                if (isset($overrides[$id][$locale]) && trim((string) $overrides[$id][$locale]) !== '') {
                    $value = (string) $overrides[$id][$locale];
                }
                $rows[] = [
                    'keyword_id' => $id,
                    'keyword_value' => $value,
                    'default_value' => (string) ($defaults[$id] ?? $row->keyword_value),
                    'screen_id' => $row->screen_id,
                    'overridden' => isset($overrides[$id][$locale]) && trim((string) $overrides[$id][$locale]) !== '',
                ];
            }
        }

        return json_custom_response([
            'ok' => true,
            'app' => $app,
            'locale' => $locale,
            'branding' => AppCopyService::bundle($app),
            'labels' => AppUiLabelService::bundle($app, $locale),
            'data' => $rows,
        ]);
    }

    public function save(Request $request): JsonResponse
    {
        if (! isSuperAdmin($request->user())) {
            return json_message_response(__('message.action_is_unauthorized'), 403);
        }

        $data = $request->validate([
            'app' => ['required', 'string', 'in:user,rider'],
            'locale' => ['nullable', 'string', 'max:12'],
            'texts' => ['nullable', 'array'],
            'labels' => ['nullable', 'array'],
            'copy' => ['nullable', 'array'],
        ]);

        $app = $this->resolveApp($data['app']);
        $locale = appCopyNormalizeLocale($data['locale'] ?? null);
        $textsIn = is_array($data['texts'] ?? null) ? $data['texts'] : [];
        $texts = [];

        foreach ($textsIn as $keywordId => $value) {
            $id = (int) $keywordId;
            if ($id <= 0) {
                continue;
            }
            if (is_array($value)) {
                $texts[$id] = $value;
                continue;
            }
            if (! is_scalar($value)) {
                continue;
            }
            $texts[$id] = [$locale => (string) $value];
        }

        if ($texts !== [] || ! empty($data['copy'])) {
            AppTextService::save([
                'app' => $app,
                'texts' => $texts,
                'copy' => is_array($data['copy'] ?? null) ? $data['copy'] : [],
            ]);
        }

        $labelsSaved = 0;
        $labelsBundle = [];
        if (! empty($data['labels']) && is_array($data['labels'])) {
            $labelsBundle = AppUiLabelService::save($app, $locale, $data['labels']);
            $labelsSaved = count($data['labels']);
        }

        try {
            if (function_exists('updateLanguageVersion')) {
                updateLanguageVersion();
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return json_custom_response([
            'ok' => true,
            'message' => __('message.save'),
            'app' => $app,
            'locale' => $locale,
            'saved' => count($texts),
            'labels_saved' => $labelsSaved,
            'labels' => $labelsBundle !== [] ? $labelsBundle : AppUiLabelService::bundle($app, $locale),
        ]);
    }

    public function reset(Request $request): JsonResponse
    {
        if (! isSuperAdmin($request->user())) {
            return json_message_response(__('message.action_is_unauthorized'), 403);
        }

        $data = $request->validate([
            'app' => ['required', 'string', 'in:user,rider'],
        ]);

        $app = $this->resolveApp($data['app']);
        $deleted = AppTextService::resetMobileOverrides($app);
        $labelsDeleted = AppUiLabelService::reset($app);

        try {
            if (function_exists('updateLanguageVersion')) {
                updateLanguageVersion();
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return json_custom_response([
            'ok' => true,
            'message' => __('message.reset'),
            'app' => $app,
            'deleted' => $deleted,
            'labels_deleted' => $labelsDeleted,
        ]);
    }

    protected function resolveApp(?string $app): string
    {
        $app = strtolower(trim((string) $app));

        return in_array($app, ['user', 'rider'], true) ? $app : 'user';
    }
}
