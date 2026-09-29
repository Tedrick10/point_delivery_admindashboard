<?php

namespace App\Helpers;

use App\Models\User;
use App\Models\WelcomePromotion;

class WelcomePromotionHelper
{
    public static function getDiscountForUser(?User $user, float $amount): array
    {
        if (! $user) {
            return ['discount' => 0, 'eligible' => false, 'remaining' => 0];
        }

        $promo = WelcomePromotion::getActive();
        if (! $promo) {
            return ['discount' => 0, 'eligible' => false, 'remaining' => 0];
        }

        // Super Admin per–Online Shop gate
        if (! (bool) ($user->welcome_promo_enabled ?? true)) {
            return ['discount' => 0, 'eligible' => false, 'remaining' => 0];
        }

        $used = (int) ($user->welcome_orders_used ?? 0);
        $remaining = max(0, (int) $promo->max_orders - $used);

        if ($remaining <= 0) {
            return ['discount' => 0, 'eligible' => false, 'remaining' => 0];
        }

        $hasShopPercent = $user->welcome_discount_percent !== null && $user->welcome_discount_percent !== '';
        $discountType = $hasShopPercent ? 'percentage' : $promo->discount_type;
        $discountValue = $hasShopPercent
            ? (float) $user->welcome_discount_percent
            : (float) $promo->discount_value;

        $discount = $promo->calculateDiscount($amount, $hasShopPercent ? $discountValue : null);

        return [
            'discount' => $discount,
            'eligible' => true,
            'remaining' => $remaining,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'max_orders' => $promo->max_orders,
            'title' => $promo->title,
        ];
    }

    public static function applyAfterOrder(User $user): void
    {
        $promo = WelcomePromotion::getActive();
        if (! $promo) {
            return;
        }
        if (! (bool) ($user->welcome_promo_enabled ?? true)) {
            return;
        }
        if (($user->welcome_orders_used ?? 0) < $promo->max_orders) {
            $user->increment('welcome_orders_used');
        }
    }
}
