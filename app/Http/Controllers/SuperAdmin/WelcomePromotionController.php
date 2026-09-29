<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WelcomePromotion;
use Illuminate\Http\Request;

class WelcomePromotionController extends Controller
{
    public static function screenPayload(Request $request): array
    {
        $promo = WelcomePromotion::query()->first();
        if (! $promo) {
            $promo = WelcomePromotion::query()->create([
                'title' => 'Welcome Promotion - First 10 Orders',
                'max_orders' => 10,
                'discount_type' => 'percentage',
                'discount_value' => 5,
                'status' => 1,
            ]);
        }

        $q = trim((string) $request->get('q', ''));
        $shops = User::query()
            ->where('user_type', 'client')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('username', 'like', "%{$q}%")
                        ->orWhere('contact_number', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'username',
                'contact_number',
                'welcome_promo_enabled',
                'welcome_discount_percent',
                'welcome_orders_used',
                'status',
            ]);

        $enabledCount = User::query()
            ->where('user_type', 'client')
            ->where('welcome_promo_enabled', 1)
            ->count();

        return [
            'promo' => $promo,
            'shops' => $shops,
            'search' => $q,
            'enabledCount' => $enabledCount,
            'shopCount' => User::query()->where('user_type', 'client')->count(),
        ];
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'max_orders' => 'required|integer|min:1|max:1000',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'status' => 'required|in:0,1',
        ]);

        $promo = WelcomePromotion::query()->firstOrFail();
        $promo->update($data);

        return redirect()
            ->route('super-admin.screens.show', 'welcome-promotion')
            ->withSuccess(__('message.sa_welcome_promo_saved'));
    }

    public function updateShop(Request $request, $id)
    {
        $shop = User::query()
            ->where('user_type', 'client')
            ->findOrFail($id);

        $data = $request->validate([
            'welcome_promo_enabled' => 'nullable|boolean',
            'welcome_discount_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $shop->welcome_promo_enabled = $request->boolean('welcome_promo_enabled');
        $percent = $request->input('welcome_discount_percent');
        $shop->welcome_discount_percent = ($percent === null || $percent === '')
            ? null
            : round((float) $percent, 2);
        $shop->save();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => __('message.sa_welcome_promo_shop_saved'),
                'shop_id' => $shop->id,
                'welcome_promo_enabled' => (bool) $shop->welcome_promo_enabled,
                'welcome_discount_percent' => $shop->welcome_discount_percent,
            ]);
        }

        return redirect()
            ->route('super-admin.screens.show', array_filter([
                'screen' => 'welcome-promotion',
                'q' => $request->get('q'),
            ]))
            ->withSuccess(__('message.sa_welcome_promo_shop_saved'));
    }
}
