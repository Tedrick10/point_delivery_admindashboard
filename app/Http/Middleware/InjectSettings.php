<?php

namespace App\Http\Middleware;

use App\Models\AppSetting;
use Closure;
use App\Models\Setting;

class InjectSettings
{
    public function handle($request, Closure $next)
    {
        $themeColor = brandColorHex();
        view()->share('themeColor', $themeColor);
        view()->share('brandColorRgb', brandColorRgb());
        view()->share('brandFontFamily', brandFontCssFamily());
        view()->share('brandFontId', brandFontId());
        view()->share('brandFontPack', brandFontPack());
        view()->share('brandColorId', brandColorId());

        return $next($request);
    }
}
