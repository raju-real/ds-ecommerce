<?php

namespace App\Services;

use App\Models\AdminNotification;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Repositories\LocationRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        protected LocationRepository $locations,
        protected SmsService $sms,
        protected CouponService $coupons,
    ) {
    }

    /**
     * Create the order from cart lines. Prices and costs are snapshotted on
     * the order items so later product edits never change past orders.
     */
    public function place(array $data, Collection $lines): Order
    {
        $district = $this->locations->district((int) $data['district_id']);
        $deliveryCharge = $district ? $district->delivery_charge : (float) setting('default_delivery_charge', 130);

        // Up to 5 attempts: if simultaneous checkouts ever collide on a lock, MySQL aborts one and it is simply retried.
        return DB::transaction(function () use ($data, $lines, $deliveryCharge, $district) {
            $subtotal = $lines->sum('total');
            $cost = $lines->sum(fn ($l) => $l->product->drop_shipping_price * $l->qty);

            // Coupon first (row locked so usage limits can't be overrun by simultaneous orders), then the number:
            // every checkout takes its locks in the same order (coupon, counter, new order).
            $coupon = null;
            $couponDiscount = 0.0;
            if (! empty($data['coupon_code'])) {
                $check = $this->coupons->check($data['coupon_code'], $data['mobile'], $subtotal, true);
                if (! $check['ok']) {
                    throw ValidationException::withMessages(['coupon_code' => $check['message']]);
                }
                $coupon = $check['coupon'];
                $couponDiscount = $check['discount'];
            }

            $order = Order::create([
                'order_number' => Order::formatNumber($this->nextSequence('order')),
                'customer_name' => $data['customer_name'],
                'mobile' => $data['mobile'],
                'district_id' => $data['district_id'],
                'thana_id' => $data['thana_id'],
                'address' => $data['address'],
                'note' => $data['note'] ?? null,
                'subtotal' => $subtotal,
                'delivery_charge' => $deliveryCharge,
                'coupon_id' => $coupon?->id,
                'coupon_code' => $coupon?->code,
                'coupon_discount' => $couponDiscount,
                'total' => $subtotal + $deliveryCharge - $couponDiscount,
                'cost_total' => $cost,
                'status' => 'pending',
                'ip' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 500),
            ]);

            foreach ($lines as $line) {
                $p = $line->product;
                $order->items()->create([
                    'product_id' => $p->id,
                    'vendor_id' => $p->vendor_id,
                    'product_name' => $p->name,
                    'product_code' => $p->code,
                    'thumbnail' => $p->thumbnail,
                    'unit_price' => $line->price,
                    'drop_shipping_price' => $p->drop_shipping_price,
                    'quantity' => $line->qty,
                    'line_total' => $line->total,
                    'line_cost' => $p->drop_shipping_price * $line->qty,
                ]);
            }

            OrderStatusHistory::create(['order_id' => $order->id, 'status' => 'pending', 'note' => 'Order placed by customer']);

            // Admin bell: one notification per order placed (same transaction, so never one without the other).
            AdminNotification::create([
                'type' => 'order',
                'title' => "New order {$order->order_number}",
                'body' => Str::limit($order->customer_name, 40).' · '.money($order->total).' · '.$lines->sum('qty').' item(s)'.($district ? ' · '.$district->name : ''),
                'order_id' => $order->id,
            ]);

            return $order;
        }, 5);
    }

    /**
     * Next value of a counter. The row lock (inside the caller's transaction) keeps it unique,
     * and a rolled-back checkout also rolls back its number, so there are no gaps.
     */
    protected function nextSequence(string $name): int
    {
        $current = DB::table('counters')->where('name', $name)->lockForUpdate()->value('value');
        if ($current === null) {
            // Only on a fresh install without the migration's row.
            DB::table('counters')->insertOrIgnore(['name' => $name, 'value' => 0, 'created_at' => now(), 'updated_at' => now()]);
            $current = DB::table('counters')->where('name', $name)->lockForUpdate()->value('value');
        }
        $value = (int) $current + 1;
        DB::table('counters')->where('name', $name)->update(['value' => $value, 'updated_at' => now()]);

        return $value;
    }

    /** Generates a fresh code, stores its hash and sends it. Returns the plain code (for dev display). */
    public function sendOtp(Order $order, bool $isResend = false): string
    {
        $cfg = config('shop.otp');

        if ($isResend) {
            if ($order->otp_resends >= $cfg['max_resends']) {
                throw ValidationException::withMessages(['otp' => 'Maximum resend limit reached. Our team will call you to confirm.']);
            }
            if ($order->otp_sent_at && $order->otp_sent_at->diffInSeconds(now()) < $cfg['resend_after_seconds']) {
                throw ValidationException::withMessages(['otp' => 'Please wait a moment before requesting a new code.']);
            }
        }

        $code = (string) random_int(10 ** ($cfg['length'] - 1), 10 ** $cfg['length'] - 1);

        $order->forceFill([
            'otp_code' => Hash::make($code),
            'otp_expires_at' => now()->addMinutes($cfg['expires_minutes']),
            'otp_sent_at' => now(),
            'otp_attempts' => 0,
            'otp_resends' => $order->otp_resends + ($isResend ? 1 : 0),
        ])->save();

        // The provider's approved OTP format (Settings → SMS), e.g. "Your Neatly OTP is 123456".
        $this->sms->send($order->mobile, $this->sms->otpMessage($code));

        return $code;
    }

    public function verifyOtp(Order $order, string $code): void
    {
        $cfg = config('shop.otp');

        if ($order->otp_verified) {
            return;
        }
        if ($order->otp_attempts >= $cfg['max_attempts']) {
            throw ValidationException::withMessages(['otp' => 'Too many wrong attempts. Please request a new code.']);
        }
        if (! $order->otp_expires_at || $order->otp_expires_at->isPast()) {
            throw ValidationException::withMessages(['otp' => 'This code has expired. Please request a new code.']);
        }
        if (! Hash::check($code, (string) $order->otp_code)) {
            $order->increment('otp_attempts');
            $left = $cfg['max_attempts'] - $order->otp_attempts;
            throw ValidationException::withMessages(['otp' => "Invalid code. $left attempt(s) left."]);
        }

        $order->forceFill(['otp_verified' => true, 'otp_verified_at' => now(), 'otp_code' => null])->save();
        OrderStatusHistory::create(['order_id' => $order->id, 'status' => $order->status, 'note' => 'Mobile number verified by OTP']);
    }

    public function changeStatus(Order $order, string $status, ?string $note, ?int $userId): void
    {
        DB::transaction(function () use ($order, $status, $note, $userId) {
            $order->update(['status' => $status]);
            OrderStatusHistory::create(['order_id' => $order->id, 'user_id' => $userId, 'status' => $status, 'note' => $note]);
        });
    }
}
