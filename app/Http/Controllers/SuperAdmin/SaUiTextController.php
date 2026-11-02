<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\SaUiTextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaUiTextController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $locale = SaUiTextService::normalizeLocale($request->get('locale'));
        $pathname = (string) $request->get('pathname', '/super-admin');

        return response()->json([
            'ok' => true,
            'locale' => $locale,
            'pathname' => $pathname,
            'data' => SaUiTextService::forPage($pathname, $locale),
        ]);
    }

    public function save(Request $request): JsonResponse
    {
        $data = $request->validate([
            'locale' => ['nullable', 'string', 'max:12'],
            'pathname' => ['required', 'string', 'max:255'],
            'shell' => ['nullable', 'array'],
            'page' => ['nullable', 'array'],
        ]);

        $locale = SaUiTextService::normalizeLocale($data['locale'] ?? null);
        $pathname = (string) $data['pathname'];
        $shell = is_array($data['shell'] ?? null) ? $data['shell'] : [];
        $page = is_array($data['page'] ?? null) ? $data['page'] : [];

        // Values must be strings
        foreach ([&$shell, &$page] as &$map) {
            $clean = [];
            foreach ($map as $k => $v) {
                if (! is_string($k) && ! is_numeric($k)) {
                    continue;
                }
                $clean[(string) $k] = is_scalar($v) ? (string) $v : '';
            }
            $map = $clean;
        }
        unset($map);

        $saved = SaUiTextService::save($locale, $shell, $pathname, $page, false);

        return response()->json([
            'ok' => true,
            'message' => __('message.save'),
            'locale' => $locale,
            'pathname' => $pathname,
            'data' => $saved,
        ]);
    }

    public function reset(Request $request): JsonResponse
    {
        $data = $request->validate([
            'locale' => ['nullable', 'string', 'max:12'],
            'pathname' => ['nullable', 'string', 'max:255'],
            'all' => ['nullable', 'boolean'],
        ]);

        $locale = SaUiTextService::normalizeLocale($data['locale'] ?? null);
        $pathname = ! empty($data['all']) ? null : (string) ($data['pathname'] ?? '');
        $saved = SaUiTextService::reset($locale, $pathname !== '' ? $pathname : null);

        return response()->json([
            'ok' => true,
            'message' => __('message.reset'),
            'locale' => $locale,
            'data' => $saved,
        ]);
    }
}
