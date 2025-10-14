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
            return [false, 'Invalid discount code', 0.0, null, 0];
        }
        if (!$discount->is_active) {
            return [false, 'Inactive discount code', 0.0, $discount, 0];
        }
        if (!$discount->isWithinDateRange()) {
            // Not yet active or expired
            $now = now();
            if ($discount->valid_from && $now->lt($discount->valid_from)) {
                return [false, 'Discount code not yet active', 0.0, $discount, 0];
            }
            return [false, 'Discount code expired', 0.0, $discount, 0];
        }
        if (!$discount->hasRemainingUses()) {
            return [false, 'Discount usage limit reached', 0.0, $discount, 0];
        }
        if (!$discount->appliesToCategoryIds($cartCategoryIds)) {
            return [false, 'This code is not applicable to your cart items', 0.0, $discount, 0];
        }
        if (!empty($discount->minimum_order_amount) && $subtotal < (float)$discount->minimum_order_amount) {
            return [false, 'Minimum order amount not met for this code', 0.0, $discount, 0];
        }

        // Eligible subtotal: only items in allowed categories
        $allowedCategoryIds = $discount->categories()->pluck('categories.id')->all();
        if (empty($allowedCategoryIds) && $discount->category_id) {
            $allowedCategoryIds = [$discount->category_id];
        }
        $eligibleSubtotal = $subtotal;
        $affectedItemCount = 0;
        if (!empty($allowedCategoryIds)) {
            $eligibleSubtotal = 0.0;
            foreach ($cartItems as $item) {
                if (in_array($item['category_id'] ?? null, $allowedCategoryIds)) {
                    $affectedItemCount++; // Count unique items, not sum of quantities
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

        return [true, 'Discount code applied successfully', $amount, $discount, $affectedItemCount];
    }

    public function incrementUsage(DiscountCode $discount): void
    {
        $discount->increment('usage_count');
    }

    /**
     * Allocate discount amount proportionally across eligible items.
     * Returns map of product_id => discount_amount applied to that product line.
     */
    public function allocatePerItem(DiscountCode $discount, array $cartItems): array
    {
        $allowedCategoryIds = $discount->categories()->pluck('categories.id')->all();
        if (empty($allowedCategoryIds) && $discount->category_id) {
            $allowedCategoryIds = [$discount->category_id];
        }

        // Build eligible lines
        $eligibleLines = [];
        $eligibleSubtotal = 0.0;
        foreach ($cartItems as $item) {
            $lineTotal = (float)($item['price'] * $item['quantity']);
            $isEligible = empty($allowedCategoryIds) || in_array(($item['category_id'] ?? null), $allowedCategoryIds);
            if ($isEligible) {
                $eligibleSubtotal += $lineTotal;
                $eligibleLines[] = [
                    'product_id' => $item['product_id'],
                    'line_total' => $lineTotal,
                ];
            }
        }

        if ($eligibleSubtotal <= 0.0 || empty($eligibleLines)) {
            return [];
        }

        // Compute total discount amount
        $totalDiscount = 0.0;
        if ($discount->discount_type === 'fixed') {
            $totalDiscount = min($eligibleSubtotal, (float)$discount->discount_value);
        } else {
            $totalDiscount = round($eligibleSubtotal * ((float)$discount->discount_value / 100), 2);
        }

        if ($totalDiscount <= 0.0) {
            return [];
        }

        // Proportional allocation
        $allocation = [];
        $allocatedSoFar = 0.0;
        $lastIndex = count($eligibleLines) - 1;
        foreach ($eligibleLines as $idx => $line) {
            if ($idx === $lastIndex) {
                $share = round($totalDiscount - $allocatedSoFar, 2);
            } else {
                $ratio = $line['line_total'] / $eligibleSubtotal;
                $share = round($totalDiscount * $ratio, 2);
                $allocatedSoFar += $share;
            }
            $pid = $line['product_id'];
            $allocation[$pid] = ($allocation[$pid] ?? 0.0) + $share;
        }

        return $allocation;
    }
}


