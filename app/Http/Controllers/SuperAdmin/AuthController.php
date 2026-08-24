<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Traits\LoginHistoryTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use LoginHistoryTrait;

    public function showLogin()
    {
        if (Auth::check() && isSuperAdmin(Auth::user())) {
            return redirect()->route('super-admin.dashboard');
        }

        if (Auth::check()) {
            Auth::logout();
        }

        return view('super-admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $user = Auth::user();
        if (! isSuperAdmin($user)) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => ['Super Admin account only. Use Branch Admin login for branch accounts.'],
            ]);
        }

        $request->session()->regenerate();
        $this->saveLoginHistory($user);

        return redirect()->intended(route('super-admin.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('super-admin.login');
    }
}
