<?php

namespace App\Services;

use App\Models\DiscountCode;
use Illuminate\Support\Arr;

class DiscountService
{
    public function validateAndCalculate(string $code, array $cartItems, array $cartCategoryIds, float $subtotal): array
    {
        // Fetch by code first to provide precise error messages
        $discount = DiscountCode::whereRaw('upper(code) = ?', [strtoupper($code)])->first();
        if (!$discount) {
            return [false, 'Invalid discount code', 0.0, null];
        }
        if (!$discount->is_active) {
            return [false, 'Inactive discount code', 0.0, $discount];
        }
        if (!$discount->isWithinDateRange()) {
            // Not yet active or expired
            $now = now();
            if ($discount->valid_from && $now->lt($discount->valid_from)) {
                return [false, 'Discount code not yet active', 0.0, $discount];
            }
            return [false, 'Discount code expired', 0.0, $discount];
        }
        if (!$discount->hasRemainingUses()) {
            return [false, 'Discount usage limit reached', 0.0, $discount];
        }
        if (!$discount->appliesToCategoryIds($cartCategoryIds)) {
            return [false, 'This code is not applicable to your cart items', 0.0, $discount];
        }
        if (!empty($discount->minimum_order_amount) && $subtotal < (float)$discount->minimum_order_amount) {
            return [false, 'Minimum order amount not met for this code', 0.0, $discount];
        }

        // Eligible subtotal: only items in allowed categories
        $allowedCategoryIds = $discount->categories()->pluck('categories.id')->all();
        if (empty($allowedCategoryIds) && $discount->category_id) {
            $allowedCategoryIds = [$discount->category_id];
        }
        $eligibleSubtotal = $subtotal;
        if (!empty($allowedCategoryIds)) {
            $eligibleSubtotal = 0.0;
            foreach ($cartItems as $item) {
                if (in_array($item['category_id'] ?? null, $allowedCategoryIds)) {
                    $eligibleSubtotal += ($item['price'] * $item['quantity']);
                }
            }
        }

        $amount = 0.0;
        if ($discount->discount_type === 'fixed') {
            $amount = min($eligibleSubtotal, (float)$discount->discount_value);
        } else {
            $amount = round($eligibleSubtotal * ((float)$discount->discount_value / 100), 2);
        }

        return [true, 'Discount code applied successfully', $amount, $discount];
    }

    public function incrementUsage(DiscountCode $discount): void
    {
        $discount->increment('usage_count');
    }
}


