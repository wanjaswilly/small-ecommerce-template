<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Order;
use Carbon\Carbon;

class CouponService
{
    public function validate(string $code, float $cartTotal, ?int $userId = null): array
    {
        $coupon = Coupon::where('code', $code)->first();

        if (!$coupon) {
            return ['valid' => false, 'discount' => 0, 'error' => 'Coupon not found'];
        }

        if (!$coupon->is_active) {
            return ['valid' => false, 'discount' => 0, 'error' => 'Coupon is not active'];
        }

        if ($coupon->start_date && Carbon::now()->lt(Carbon::parse($coupon->start_date))) {
            return ['valid' => false, 'discount' => 0, 'error' => 'Coupon not yet valid'];
        }

        if ($coupon->end_date && Carbon::now()->gt(Carbon::parse($coupon->end_date))) {
            return ['valid' => false, 'discount' => 0, 'error' => 'Coupon has expired'];
        }

        if ($coupon->min_spend && $cartTotal < $coupon->min_spend) {
            return ['valid' => false, 'discount' => 0, 'error' => 'Minimum spend not reached'];
        }

        if ($coupon->max_uses_total && $coupon->used_total >= $coupon->max_uses_total) {
            return ['valid' => false, 'discount' => 0, 'error' => 'Coupon usage limit reached'];
        }

        if ($userId && $coupon->max_uses_per_user) {
            $userUses = Order::where('user_id', $userId)
                ->whereHas('coupon', function ($q) use ($code) {
                    $q->where('code', $code);
                })
                ->count();
            if ($userUses >= $coupon->max_uses_per_user) {
                return ['valid' => false, 'discount' => 0, 'error' => 'Your usage limit reached'];
            }
        }

        $discount = $coupon->type === 'percentage' 
            ? ($cartTotal * $coupon->value / 100) 
            : min($coupon->value, $cartTotal);

        return ['valid' => true, 'discount' => $discount];
    }

    public function applyCoupon(Order $order, string $code): bool
    {
        $coupon = Coupon::where('code', $code)->first();

        if (!$coupon || !$coupon->is_active) {
            return false;
        }

        $order->coupon_id = $coupon->id;
        $saved = $order->save();

        if ($saved) {
            $coupon->increment('used_total');
        }

        return $saved;
    }

    public function getAll(): array
    {
        return Coupon::orderBy('created_at', 'desc')->get()->toArray();
    }

    public function create(array $data): Coupon
    {
        return Coupon::create($data);
    }

    public function update(Coupon $coupon, array $data): bool
    {
        return $coupon->update($data);
    }

    public function delete(Coupon $coupon): bool
    {
        return $coupon->delete();
    }
}