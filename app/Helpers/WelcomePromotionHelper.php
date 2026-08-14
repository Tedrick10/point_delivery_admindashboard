<?php

namespace App\Helpers;

use App\Models\User;
use App\Models\WelcomePromotion;

class WelcomePromotionHelper
{
    public static function getDiscountForUser(?User $user, float $amount): array
    {
        if (!$user) {
            return ['discount' => 0, 'eligible' => false, 'remaining' => 0];
        }

        $promo = WelcomePromotion::getActive();
        if (!$promo) {
            return ['discount' => 0, 'eligible' => false, 'remaining' => 0];
        }

        $used = $user->welcome_orders_used ?? 0;
        $remaining = max(0, $promo->max_orders - $used);

        if ($remaining <= 0) {
            return ['discount' => 0, 'eligible' => false, 'remaining' => 0];
        }

        $discount = $promo->calculateDiscount($amount);
        return [
            'discount' => $discount,
            'eligible' => true,
            'remaining' => $remaining,
            'discount_type' => $promo->discount_type,
            'discount_value' => $promo->discount_value,
            'max_orders' => $promo->max_orders,
        ];
    }

    public static function applyAfterOrder(User $user): void
    {
        $promo = WelcomePromotion::getActive();
        if (!$promo) {
            return;
        }
        if (($user->welcome_orders_used ?? 0) < $promo->max_orders) {
            $user->increment('welcome_orders_used');
        }
    }
}
