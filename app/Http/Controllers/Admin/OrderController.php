<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Repositories\OrderRepository;
use App\Services\OrderService;
use App\Repositories\LocationRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct(protected OrderRepository $orders, protected OrderService $service)
    {
    }

    public function index(Request $request, LocationRepository $locations)
    {
        // A filter page must never bounce: invalid values are dropped and reported instead of redirecting back.
        [$filters, $ignored] = $this->orderFilters($request);

        return view('admin.orders.index', [
            'orders' => $this->orders->adminPaginate($filters)->withQueryString(),
            'counts' => $this->orders->statusCounts($filters),
            'unverified' => $this->orders->statusCounts()['unverified'] ?? 0,
            'filters' => $filters,
            'ignored' => $ignored,
            'districts' => $locations->allDistricts(),
            'ranges' => $this->dateRanges(),
        ]);
    }

    /** @return array{0: array, 1: string[]} clean filters and the names of the ones that were dropped */
    protected function orderFilters(Request $request): array
    {
        // Non-string values (e.g. ?q[]=x) become '!' so they are reported as ignored rather than silently dropped.
        $in = array_map(fn ($v) => is_string($v) ? trim($v) : '!', $request->only('q', 'status', 'otp', 'from', 'to', 'district_id'));
        $in = array_filter($in, fn ($v) => $v !== '');
        if (($in['q'] ?? null) === '!') {
            unset($in['q']);
            $badQuery = true;
        }
        $date = fn ($v) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && Carbon::canBeCreatedFromFormat($v, 'Y-m-d') ? $v : null;

        $clean = array_filter([
            'q' => isset($in['q']) ? mb_substr($in['q'], 0, 100) : null,
            'status' => isset($in['status']) && array_key_exists($in['status'], config('shop.order_statuses')) ? $in['status'] : null,
            'otp' => in_array($in['otp'] ?? null, ['verified', 'unverified'], true) ? $in['otp'] : null,
            'from' => isset($in['from']) ? $date($in['from']) : null,
            'to' => isset($in['to']) ? $date($in['to']) : null,
            'district_id' => ctype_digit($in['district_id'] ?? '') ? (int) $in['district_id'] : null,
        ]);
        if (isset($clean['from'], $clean['to']) && $clean['from'] > $clean['to']) {
            [$clean['from'], $clean['to']] = [$clean['to'], $clean['from']]; // swapped dates are obviously meant as a range
        }

        return [$clean, array_merge(isset($badQuery) ? ['q'] : [], array_keys(array_diff_key($in, $clean)))];
    }

    /** Quick date presets for the filter bar. */
    protected function dateRanges(): array
    {
        $today = now()->toDateString();

        return [
            'Today' => [$today, $today],
            'Yesterday' => [now()->subDay()->toDateString(), now()->subDay()->toDateString()],
            'Last 7 days' => [now()->subDays(6)->toDateString(), $today],
            'This month' => [now()->startOfMonth()->toDateString(), $today],
        ];
    }

    public function show(string $order)
    {
        return view('admin.orders.show', ['order' => $this->orders->find($order) ?? abort(404)]);
    }

    public function invoice(string $order)
    {
        return view('admin.orders.invoice', ['order' => $this->orders->find($order) ?? abort(404)]);
    }

    public function status(Request $request, string $order)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(config('shop.order_statuses')))],
            'note' => 'nullable|string|max:1000',
        ]);

        $this->service->changeStatus(Order::findByUidOrFail($order), $data['status'], $data['note'] ?? null, auth()->id());

        return back()->with('success', 'Order status updated.');
    }

    /** Record that the order was placed with the vendor / handed to a courier. */
    public function forward(Request $request, string $order)
    {
        $data = $request->validate([
            'vendor_order_ref' => 'nullable|string|max:100',
            'courier_name' => 'nullable|string|max:60',
            'tracking_code' => 'nullable|string|max:100',
            'courier_cost' => 'nullable|numeric|min:0|max:99999',
            'status' => ['required', Rule::in(['forwarded', 'shipped'])],
            'note' => 'nullable|string|max:1000',
        ]);

        $model = Order::findByUidOrFail($order);
        $model->update([
            'vendor_order_ref' => $data['vendor_order_ref'] ?? $model->vendor_order_ref,
            'courier_name' => $data['courier_name'] ?? $model->courier_name,
            'tracking_code' => $data['tracking_code'] ?? $model->tracking_code,
            'courier_cost' => $data['courier_cost'] ?? $model->courier_cost,
            'forwarded_at' => $model->forwarded_at ?? now(),
        ]);

        $note = $data['note'] ?? collect([
            $data['vendor_order_ref'] ?? null ? 'Vendor ref: '.$data['vendor_order_ref'] : null,
            $data['courier_name'] ?? null ? 'Courier: '.$data['courier_name'] : null,
            $data['tracking_code'] ?? null ? 'Tracking: '.$data['tracking_code'] : null,
        ])->filter()->implode(' · ');

        $this->service->changeStatus($model, $data['status'], $note ?: null, auth()->id());

        return back()->with('success', $data['status'] === 'shipped' ? 'Order handed to courier.' : 'Order forwarded to vendor.');
    }

    /** Edit customer / shipping info, discount and admin note. */
    public function details(Request $request, string $order)
    {
        $data = $request->validate([
            'customer_name' => 'required|string|min:3|max:120',
            'mobile' => ['required', 'regex:/^01[3-9]\d{8}$/'],
            'address' => 'required|string|min:10|max:500',
            'delivery_charge' => 'required|numeric|min:0|max:9999',
            'discount' => 'required|numeric|min:0|max:9999999',
            'vat_amount' => 'nullable|numeric|min:0|max:9999999', // VAT we pay — an amount, not a percentage
            'admin_note' => 'nullable|string|max:1000',
        ], [
            'vat_amount.numeric' => 'VAT must be a number, e.g. 25 or 25.50.',
            'vat_amount.min' => 'VAT cannot be negative.',
        ]);

        $model = Order::findByUidOrFail($order);
        // The customer's coupon discount stays; "discount" is the extra manual discount given here.
        $data['total'] = max(0, $model->subtotal + $data['delivery_charge'] - $model->coupon_discount - $data['discount']);
        // VAT is our cost (deducted from profit, like the courier cost); the customer's total does not change.
        $data['vat_amount'] = round((float) ($data['vat_amount'] ?? 0), 2);
        if (! \Illuminate\Support\Facades\Schema::hasColumn('orders', 'vat_amount')) {
            unset($data['vat_amount']); // migration not run yet
        }
        $model->update($data);

        return back()->with('success', 'Order details saved.');
    }

    public function markVerified(string $order)
    {
        $model = Order::findByUidOrFail($order);
        $model->update(['otp_verified' => true, 'otp_verified_at' => now(), 'otp_code' => null]);
        OrderStatusHistory::create(['order_id' => $model->id, 'user_id' => auth()->id(), 'status' => $model->status, 'note' => 'Mobile manually verified by admin (phone call)']);

        return back()->with('success', 'Order marked as verified.');
    }

    public function destroy(string $order)
    {
        Order::findByUidOrFail($order)->delete();

        return redirect()->route('admin.orders.index')->with('success', 'Order deleted.');
    }
}
