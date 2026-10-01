<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UiThemeController extends Controller
{
    public static function screenPayload(): array
    {
        return [
            'packs' => array_values(uiThemePacks()),
            'active' => uiThemePackId(),
        ];
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'ui_theme' => 'required|in:classic,liquid_glass,aurora',
        ]);

        $packId = (string) $request->input('ui_theme');
        $pack = uiThemePack($packId);

        Setting::updateOrCreate(
            ['type' => 'APP_THEME', 'key' => 'UI_THEME_PACK'],
            ['value' => $packId]
        );

        // Keep legacy theme_color in sync with Super Admin brand color.
        $app = AppSetting::first();
        if ($app) {
            $app->color = brandColorHex();
            $app->save();
            appSettingData('set');
        }

        return redirect()
            ->route('super-admin.screens.show', 'ui-theme')
            ->with('success', __('message.updated'));
    }
}
