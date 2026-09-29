<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Legacy branch-panel route — Welcome Promotion is Super Admin only.
 */
class WelcomePromotionController extends Controller
{
    public function index()
    {
        if (! isSuperAdmin(auth()->user())) {
            return redirect()->back()->withErrors(__('message.permission_denied_for_account'));
        }

        return redirect()->route('super-admin.screens.show', 'welcome-promotion');
    }

    public function update(Request $request, $id)
    {
        if (! isSuperAdmin(auth()->user())) {
            return redirect()->back()->withErrors(__('message.permission_denied_for_account'));
        }

        return redirect()->route('super-admin.screens.show', 'welcome-promotion');
    }
}
