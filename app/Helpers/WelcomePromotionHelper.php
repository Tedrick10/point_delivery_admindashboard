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

        $maxOrders = self::maxOrdersForUser($user, $promo);
        $used = (int) ($user->welcome_orders_used ?? 0);
        $remaining = max(0, $maxOrders - $used);

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
            'max_orders' => $maxOrders,
            'title' => $promo->title,
        ];
    }

    public static function maxOrdersForUser(User $user, WelcomePromotion $promo): int
    {
        $shopMax = $user->welcome_max_orders;
        if ($shopMax !== null && (int) $shopMax > 0) {
            return (int) $shopMax;
        }

        return max(1, (int) $promo->max_orders);
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
        if (($user->welcome_orders_used ?? 0) < self::maxOrdersForUser($user, $promo)) {
            $user->increment('welcome_orders_used');
        }
    }
}
