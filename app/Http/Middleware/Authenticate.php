<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\AdminLoginDevice;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    public function handle($request, Closure $next, ...$guards)
    {
        $this->authenticate($request, $guards);

        // Admin single-session device lock is web-panel only. API (Sanctum /
        // Rider app) has no StartSession middleware — touching Session here
        // can confuse token auth and must never redirect HTML for JSON clients.
        if ($request->is('api/*') || $request->expectsJson() || in_array('sanctum', $guards, true)) {
            return $next($request);
        }

        $device = AdminLoginDevice::where('session_id', Session::getId())->first();

        if ($device && !$device->is_active) {
            if (!$device->logout_at) {
                $device->update(['logout_at' => now()]);
            }

            Auth::guard('web')->logout();
            Session::invalidate();
            Session::regenerateToken();

            return redirect()->route('login');
        }

        return $next($request);
    }

    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            return route('login');
        }
    }
}
