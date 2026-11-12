<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Repositories\LocationRepository;
use App\Repositories\OrderRepository;
use App\Services\CartService;
use App\Services\CouponService;
use App\Services\FacebookPixel;
use App\Services\OrderService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function __construct(
        protected CartService $cart,
        protected LocationRepository $locations,
        protected OrderService $orders,
        protected OrderRepository $orderRepo,
    ) {
    }

    /** "Apply" button: checks a coupon for the mobile number and the cart as it is now (subtotal from the server, never the browser). */
    public function coupon(Request $request, CouponService $coupons)
    {
        $data = $request->validate([
            'code' => 'required|string|max:30',
            'mobile' => 'nullable|string|max:20',
        ], ['code.required' => 'Please enter a coupon code.']);

        $subtotal = (float) $this->cart->subtotal();
        if ($subtotal <= 0) {
            return response()->json(['ok' => false, 'message' => 'Your cart is empty.'], 422);
        }

        $check = $coupons->check($data['code'], $data['mobile'] ?? null, $subtotal);

        return response()->json([
            'ok' => $check['ok'],
            'message' => $check['message'],
            'code' => $check['coupon']?->code,
            'discount' => $check['discount'],
            'discount_text' => money($check['discount']),
            'subtotal' => $subtotal,
        ], $check['ok'] ? 200 : 422);
    }

    public function index(FacebookPixel $pixel)
    {
        $items = $this->cart->items();
        if ($items->isNotEmpty()) {
            $pixel->track('InitiateCheckout', $pixel->cartData($items));
        }

        return view('frontend.checkout.index', [
            'items' => $items,
            'districts' => $this->locations->districts(),
        ]);
    }

    public function store(Request $request, SmsService $sms)
    {
        $data = $request->validate([
            'customer_name' => 'required|string|min:3|max:120',
            'mobile' => ['required', 'regex:/^01[3-9]\d{8}$/'],
            'district_id' => 'required|integer',
            'thana_id' => 'required|integer',
            'address' => 'required|string|min:10|max:500',
            'note' => 'nullable|string|max:500',
            'coupon_code' => 'nullable|string|max:30',
        ], [
            'address.min' => 'Please write your full address (house, road, area).',
            'mobile.regex' => 'Please enter a valid 11 digit mobile number (01XXXXXXXXX).',
        ]);

        if (! $this->locations->district($data['district_id'])) {
            throw ValidationException::withMessages(['district_id' => 'Please select your district.']);
        }
        if (! $this->locations->thana($data['district_id'], $data['thana_id'])) {
            throw ValidationException::withMessages(['thana_id' => 'Please select your thana / upazila.']);
        }

        $lines = $this->cart->items();
        if ($lines->isEmpty()) {
            return redirect()->route('checkout')->with('error', 'Your cart is empty.');
        }

        $order = $this->orders->place($data, $lines);
        $code = $this->orders->sendOtp($order);
        $sms->notifyAdminsOfOrder($order); // "New order placed - NT-00012" to the admin numbers (Settings → SMS), after the response

        $this->cart->clear();
        $request->session()->push('placed_orders', $order->order_number);

        return redirect()->route('checkout.verify', $order->order_number)
            ->with('dev_otp', $sms->isLogDriver() && config('app.debug') ? $code : null);
    }

    public function verifyForm(string $number)
    {
        $order = $this->ownedOrder($number);

        if ($order->otp_verified) {
            return redirect()->route('checkout.success', $number);
        }

        return view('frontend.checkout.verify', compact('order'));
    }

    public function verify(Request $request, string $number, FacebookPixel $pixel)
    {
        $request->validate(['otp' => 'required|digits:'.config('shop.otp.length')]);
        $this->ownedOrder($number);

        $order = Order::where('order_number', $number)->firstOrFail();
        $wasVerified = (bool) $order->otp_verified;
        $this->orders->verifyOtp($order, $request->input('otp'));

        // The order is real now: Purchase, once per order (same event ID in browser and server, so never doubled).
        // The browser part fires on the success page; reloading that page does not send it again.
        if (! $wasVerified && $order->otp_verified) {
            $order->load('items', 'district');
            $pixel->track('Purchase', $pixel->orderData($order), $pixel->orderCustomer($order), nextPage: true, eventId: 'purchase.'.$order->order_number);
        }

        return redirect()->route('checkout.success', $number);
    }

    public function resend(string $number, SmsService $sms)
    {
        $this->ownedOrder($number);
        $code = $this->orders->sendOtp(Order::where('order_number', $number)->firstOrFail(), true);

        return back()->with('success', 'A new code has been sent to your mobile.')
            ->with('dev_otp', $sms->isLogDriver() && config('app.debug') ? $code : null);
    }

    public function success(string $number)
    {
        $order = $this->ownedOrder($number);

        if (! $order->otp_verified) {
            return redirect()->route('checkout.verify', $number);
        }

        return view('frontend.checkout.success', compact('order'));
    }

    /** Only the browser session that placed the order may verify / view it here. */
    protected function ownedOrder(string $number): Order
    {
        abort_unless(in_array($number, session('placed_orders', []), true), 404);

        return $this->orderRepo->findByNumber($number) ?? abort(404);
    }
}
