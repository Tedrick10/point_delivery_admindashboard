<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check() && isSuperAdmin(Auth::user())) {
            return redirect()->route('super-admin.dashboard');
        }

        // Unified Admin Login — Super Admin uses the same Admin Login page.
        return redirect()->route('admin-login');
    }

    public function login(Request $request)
    {
        // POST /super-admin/login kept for old bookmarks; send to unified login.
        return redirect()->route('admin-login');
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin-login');
    }
}
