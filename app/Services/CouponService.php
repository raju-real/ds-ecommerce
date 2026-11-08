<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * Checks a coupon for a mobile number and a cart subtotal. Used by the checkout "Apply" button
 * and again when the order is saved (then with the coupon row locked, so limits can't be overrun).
 * Usage always comes from live order data, never from cache.
 */
class CouponService
{
    public const MOBILE_PATTERN = '/^01[3-9]\d{8}$/';

    /** Orders in these states do not count as "used" (and don't make someone an existing customer). */
    protected const NOT_COUNTED = ['cancelled', 'returned'];

    /**
     * @return array{ok: bool, message: string, discount: float, coupon: ?Coupon}
     */
    public function check(?string $code, ?string $mobile, float $subtotal, bool $lock = false): array
    {
        $code = strtoupper(trim((string) $code));
        if ($code === '') {
            return $this->fail('Please enter a coupon code.');
        }
        if (! preg_match(self::MOBILE_PATTERN, (string) $mobile)) {
            return $this->fail('Enter your 11 digit mobile number first — coupons are checked against it.');
        }

        $query = Coupon::where('code', $code);
        $coupon = $lock ? $query->lockForUpdate()->first() : $query->first();

        if (! $coupon || ! $coupon->is_active) {
            return $this->fail('This coupon code is not valid.');
        }
        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            return $this->fail('This coupon is not active yet. It starts on '.$coupon->starts_at->format('d M Y').'.');
        }
        if ($coupon->ends_at && $coupon->ends_at->isPast()) {
            return $this->fail('This coupon has expired.');
        }
        if ($coupon->min_order && $subtotal < $coupon->min_order) {
            return $this->fail('Add '.money($coupon->min_order - $subtotal).' more to use this coupon (minimum order '.money($coupon->min_order).').');
        }

        $used = fn () => Order::where('coupon_id', $coupon->id)->whereNotIn('status', self::NOT_COUNTED);
        if ($coupon->usage_limit !== null && $used()->count() >= $coupon->usage_limit) {
            return $this->fail('This coupon has reached its usage limit.');
        }
        if ($coupon->per_mobile_limit && $used()->where('mobile', $mobile)->count() >= $coupon->per_mobile_limit) {
            return $this->fail('You have already used this coupon.');
        }

        if ($coupon->type === 'new_user' && Order::where('mobile', $mobile)->whereNotIn('status', ['cancelled'])->exists()) {
            return $this->fail('This coupon is only for your first order.');
        }
        if ($coupon->type === 'fixed_user' && ! in_array($mobile, $coupon->mobiles ?? [], true)) {
            return $this->fail('This coupon is not available for this mobile number.');
        }

        $discount = $coupon->discountFor($subtotal);
        if ($discount <= 0) {
            return $this->fail('This coupon gives no discount on this order.');
        }

        return ['ok' => true, 'message' => "Coupon {$coupon->code} applied — you save ".money($discount).'.', 'discount' => $discount, 'coupon' => $coupon];
    }

    /** Times a coupon was used (orders that were not cancelled/returned). */
    public function usageCount(Coupon $coupon): int
    {
        return (int) DB::table('orders')->whereNull('deleted_at')->where('coupon_id', $coupon->id)->whereNotIn('status', self::NOT_COUNTED)->count();
    }

    protected function fail(string $message): array
    {
        return ['ok' => false, 'message' => $message, 'discount' => 0.0, 'coupon' => null];
    }
}
