<?php

namespace App\Http\Controllers;

use App\Models\WelcomePromotion;
use Illuminate\Http\Request;

class WelcomePromotionController extends Controller
{
    public function index()
    {
        if (!auth()->user()->can('welcome-promotion-list')) {
            return redirect()->back()->withErrors(__('message.permission_denied_for_account'));
        }
        $pageTitle = __('message.welcome_promotion');
        $data = WelcomePromotion::first();
        return view('welcome_promotion.form', compact('pageTitle', 'data'));
    }

    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('welcome-promotion-edit')) {
            return redirect()->back()->withErrors(__('message.permission_denied_for_account'));
        }
        $request->validate([
            'max_orders' => 'required|integer|min:1',
            'discount_value' => 'required|numeric|min:0',
        ]);

        $promo = WelcomePromotion::findOrFail($id);
        $promo->update($request->only(['title', 'max_orders', 'discount_type', 'discount_value', 'status']));

        if (request()->is('api/*')) {
            return json_message_response(__('message.update_form', ['form' => __('message.welcome_promotion')]));
        }
        return redirect()->route('welcome-promotion.index')->withSuccess(__('message.update_form', ['form' => __('message.welcome_promotion')]));
    }
}
