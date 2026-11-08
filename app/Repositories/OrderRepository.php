<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Support\ModelCache;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OrderRepository
{
    public function adminPaginate(array $filters)
    {
        $key = 'admin:list:'.ModelCache::hash($filters + ['page' => request('page', 1)]);

        return ModelCache::rememberList([ModelCache::ORDERS, ModelCache::LOCATIONS], $key, fn () => $this->filtered($filters)
            ->with(['district:id,name', 'thana:id,name'])
            ->withCount('items')
            ->latest('id')
            ->paginate(config('shop.per_page.admin')));
    }

    public function find(string $uid): ?Order
    {
        return ModelCache::remember([ModelCache::ORDERS, ModelCache::LOCATIONS], "uid:$uid", fn () => Order::with([
            'items.vendor:id,name,phone,contact_person', 'items.product:id,unique_id,slug,vendor_product_url,deleted_at',
            'histories.user:id,name', 'district', 'thana',
        ])->where('unique_id', $uid)->first());
    }

    /** Public tracking: both the order number and the mobile must match. */
    public function track(string $orderNumber, string $mobile): ?Order
    {
        $number = Order::normalizeNumber($orderNumber);

        return ModelCache::remember([ModelCache::ORDERS, ModelCache::LOCATIONS], 'track:'.md5($number.'|'.$mobile),
            fn () => Order::with(['items', 'district', 'thana'])
                ->where('order_number', $number)->where('mobile', $mobile)->first());
    }

    public function findByNumber(string $orderNumber): ?Order
    {
        return ModelCache::remember([ModelCache::ORDERS, ModelCache::LOCATIONS], "number:$orderNumber",
            fn () => Order::with(['items', 'district', 'thana'])->where('order_number', $orderNumber)->first());
    }

    /** Orders per status for the tabs; with filters, the counts follow them (status itself is ignored). */
    public function statusCounts(array $filters = []): array
    {
        unset($filters['status']);

        return ModelCache::remember(ModelCache::ORDERS, 'status-counts:'.ModelCache::hash($filters), fn () => $this->filtered($filters)
            ->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status')->all()
            + ['unverified' => $this->filtered($filters)->where('otp_verified', false)->whereNotIn('status', ['cancelled'])->count()]);
    }

    /** Dashboard KPIs, last-14-day chart and recent orders. */
    public function dashboard(): array
    {
        return ModelCache::remember([ModelCache::ORDERS, ModelCache::LOCATIONS], 'dashboard:'.now()->toDateString(), function () {
            $today = now()->startOfDay();
            $month = now()->startOfMonth();
            $valid = fn ($q) => $q->whereNotIn('status', ['cancelled', 'returned']);

            // toBase(): plain rows, so the "profit" alias isn't shadowed by Order::getProfitAttribute().
            $sum = fn (Carbon $from) => (array) Order::query()->where('created_at', '>=', $from)->tap($valid)
                ->selectRaw('COUNT(*) as orders, COALESCE(SUM(total),0) as revenue, COALESCE(SUM(total - cost_total - courier_cost),0) as profit')
                ->toBase()->first();

            $days = collect(range(13, 0))->map(fn ($i) => now()->subDays($i)->toDateString());
            $daily = Order::query()->where('created_at', '>=', now()->subDays(13)->startOfDay())->tap($valid)
                ->selectRaw('DATE(created_at) as d, COUNT(*) as orders, SUM(total) as revenue, SUM(total - cost_total - courier_cost) as profit')
                ->groupBy('d')->toBase()->get()->keyBy('d');

            return [
                'today' => $sum($today),
                'month' => $sum($month),
                'lifetime' => $sum(Carbon::create(2000)),
                'chart' => [
                    'labels' => $days->map(fn ($d) => Carbon::parse($d)->format('d M'))->all(),
                    'orders' => $days->map(fn ($d) => (int) optional($daily->get($d))->orders)->all(),
                    'revenue' => $days->map(fn ($d) => round((float) optional($daily->get($d))->revenue, 2))->all(),
                    'profit' => $days->map(fn ($d) => round((float) optional($daily->get($d))->profit, 2))->all(),
                ],
                'recent' => Order::with('district:id,name')->latest('id')->take(8)->get(),
                'top_products' => OrderItem::query()
                    ->whereHas('order', $valid)
                    ->selectRaw('product_id, product_name, SUM(quantity) as qty, SUM(line_total - line_cost) as profit')
                    ->groupBy('product_id', 'product_name')->orderByDesc('qty')->take(5)->get(),
            ];
        });
    }

    /** Profit report for a date range, with per-vendor breakdown. */
    public function report(string $from, string $to): array
    {
        return ModelCache::remember(ModelCache::ORDERS, "report:$from:$to", function () use ($from, $to) {
            $range = [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()];
            $valid = fn ($q) => $q->whereBetween('created_at', $range)->whereNotIn('status', ['cancelled', 'returned']);

            $summary = (array) Order::query()->tap($valid)->selectRaw(
                'COUNT(*) as orders, COALESCE(SUM(subtotal),0) as subtotal, COALESCE(SUM(delivery_charge),0) as delivery,
                 COALESCE(SUM(total),0) as revenue, COALESCE(SUM(cost_total),0) as cost, COALESCE(SUM(courier_cost),0) as courier,
                 COALESCE(SUM(total - cost_total - courier_cost),0) as profit'
            )->toBase()->first();

            $vendors = DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->whereNull('orders.deleted_at')->whereNull('order_items.deleted_at')
                ->leftJoin('vendors', 'vendors.id', '=', 'order_items.vendor_id')
                ->whereBetween('orders.created_at', $range)->whereNotIn('orders.status', ['cancelled', 'returned'])
                ->selectRaw("COALESCE(vendors.name, 'No vendor') as vendor, COUNT(DISTINCT orders.id) as orders, SUM(order_items.quantity) as qty,
                    SUM(order_items.line_total) as sales, SUM(order_items.line_cost) as cost, SUM(order_items.line_total - order_items.line_cost) as profit")
                ->groupBy('vendors.name')->orderByDesc('profit')->get();

            $statuses = Order::query()->whereBetween('created_at', $range)
                ->selectRaw('status, COUNT(*) as c, SUM(total) as total')->groupBy('status')->get();

            return compact('summary', 'vendors', 'statuses');
        });
    }

    protected function filtered(array $filters)
    {
        return Order::query()
            // Order no, mobile, name, tracking code, unique id, or a product name / code in the order.
            ->when($filters['q'] ?? null, function ($q, $s) {
                $like = '%'.like_escape($s).'%';
                $q->where(fn ($w) => $w->where('order_number', 'like', $like)->orWhere('mobile', 'like', $like)
                    ->orWhere('customer_name', 'like', $like)->orWhere('tracking_code', 'like', $like)->orWhere('unique_id', $s)
                    ->orWhereHas('items', fn ($i) => $i->where('product_name', 'like', $like)->orWhere('product_code', 'like', $like)));
            })
            ->when($filters['district_id'] ?? null, fn ($q, $id) => $q->where('district_id', $id))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when(($filters['otp'] ?? '') !== '', fn ($q) => $q->where('otp_verified', $filters['otp'] === 'verified'))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d));
    }
}
