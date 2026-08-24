<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class SuperAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && isSuperAdmin(Auth::user())) {
            $response = $next($request);

            if ($response instanceof View) {
                $response = response($response);
            }

            if ($response instanceof Response) {
                $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
                $response->headers->set('Pragma', 'no-cache');
                $response->headers->set('Expires', '0');
                $response->headers->set('Vary', 'Cookie');
            }

            return $response;
        }

        Auth::logout();

        return redirect()
            ->route('super-admin.login')
            ->withErrors(__('message.access_denied'));
    }
}
