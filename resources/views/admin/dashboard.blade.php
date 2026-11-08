@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="page-header">
        <div>
            <h1>Dashboard</h1>
            <p class="text-muted mb-0">Welcome back, {{ auth()->user()->name }}. Here's how your store is doing.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" class="btn btn-soft-primary"><i class="bi bi-hourglass-split me-1"></i> Pending orders ({{ $counts['pending'] ?? 0 }})</a>
            <a href="{{ route('admin.reports') }}" class="btn btn-primary"><i class="bi bi-graph-up-arrow me-1"></i> Reports</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card">
                <span class="icon bg-soft-primary"><i class="bi bi-bag-check"></i></span>
                <div>
                    <div class="label">Today's orders</div>
                    <div class="value">{{ number_format($data['today']['orders']) }}</div>
                    <div class="sub">Revenue {{ money($data['today']['revenue']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card">
                <span class="icon bg-soft-success"><i class="bi bi-cash-stack"></i></span>
                <div>
                    <div class="label">Today's profit</div>
                    <div class="value text-success">{{ money($data['today']['profit']) }}</div>
                    <div class="sub">After vendor & courier cost</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card">
                <span class="icon bg-soft-info"><i class="bi bi-calendar3"></i></span>
                <div>
                    <div class="label">This month</div>
                    <div class="value">{{ money($data['month']['revenue']) }}</div>
                    <div class="sub">{{ number_format($data['month']['orders']) }} orders · profit {{ money($data['month']['profit']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card">
                <span class="icon bg-soft-danger"><i class="bi bi-shield-exclamation"></i></span>
                <div>
                    <div class="label">OTP not verified</div>
                    <div class="value text-danger">{{ number_format($counts['unverified'] ?? 0) }}</div>
                    <div class="sub"><a href="{{ route('admin.orders.index', ['otp' => 'unverified']) }}">Review these orders →</a></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Last 14 days</h5>
                    <span class="small text-muted">Revenue vs profit</span>
                </div>
                <div class="card-body"><canvas id="salesChart" height="110"></canvas></div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header"><h5>Order pipeline</h5></div>
                <div class="card-body">
                    @foreach(config('shop.order_statuses') as $key => $s)
                        <a href="{{ route('admin.orders.index', ['status' => $key]) }}" class="d-flex justify-content-between align-items-center py-2 border-bottom text-dark">
                            <span>{!! order_status_badge($key) !!}</span>
                            <span class="fw-bold">{{ number_format($counts[$key] ?? 0) }}</span>
                        </a>
                    @endforeach
                    <div class="row text-center mt-3 g-2">
                        <div class="col-6"><div class="p-2 rounded-3 bg-light"><div class="small text-muted">Products</div><div class="fw-bold">{{ $productStats['active'] }} / {{ $productStats['total'] }}</div></div></div>
                        <div class="col-6"><div class="p-2 rounded-3 bg-light"><div class="small text-muted">Avg. margin</div><div class="fw-bold text-success">{{ money($productStats['avg_profit']) }}</div></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Recent orders</h5>
                    <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-soft-primary">View all</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Profit</th><th>Status</th><th>OTP</th></tr></thead>
                        <tbody>
                            @forelse($data['recent'] as $order)
                                <tr>
                                    <td><a href="{{ route('admin.orders.show', $order->unique_id) }}" class="title">#{{ $order->order_number }}</a><div class="small text-muted">{{ $order->created_at->diffForHumans() }}</div></td>
                                    <td>{{ $order->customer_name }}<div class="small text-muted">{{ $order->mobile }} · {{ optional($order->district)->name }}</div></td>
                                    <td class="fw-semibold">{{ money($order->total) }}</td>
                                    <td class="fw-semibold {{ $order->profit >= 0 ? 'text-success' : 'text-danger' }}">{{ money($order->profit) }}</td>
                                    <td>{!! order_status_badge($order->status) !!}</td>
                                    <td>{!! $order->otp_verified ? '<i class="bi bi-patch-check-fill text-success" title="Verified"></i>' : '<i class="bi bi-x-octagon-fill text-danger" title="Not verified"></i>' !!}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6"><div class="empty"><i class="bi bi-inbox"></i>No orders yet</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header"><h5>Top selling products</h5></div>
                <div class="card-body">
                    @forelse($data['top_products'] as $i => $p)
                        <div class="d-flex align-items-center gap-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                            <span class="fw-bold text-muted" style="width: 18px">{{ $i + 1 }}</span>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold text-truncate">{{ $p->product_name }}</div>
                                <div class="small text-muted">{{ $p->qty }} sold</div>
                            </div>
                            <span class="fw-bold text-success text-nowrap">{{ money($p->profit) }}</span>
                        </div>
                    @empty
                        <div class="empty"><i class="bi bi-trophy"></i>No sales yet</div>
                    @endforelse
                    <div class="mt-3 p-3 rounded-3" style="background: var(--brand-50)">
                        <div class="small text-muted">Lifetime</div>
                        <div class="d-flex justify-content-between"><span>Revenue</span><strong>{{ money($data['lifetime']['revenue']) }}</strong></div>
                        <div class="d-flex justify-content-between"><span>Profit</span><strong class="text-success">{{ money($data['lifetime']['profit']) }}</strong></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
@endpush

@push('page-scripts')
    <script>
        (function () {
            const c = @json($data['chart']);
            new Chart(document.getElementById('salesChart'), {
                data: {
                    labels: c.labels,
                    datasets: [
                        { type: 'bar', label: 'Revenue', data: c.revenue, backgroundColor: 'rgba(79,70,229,.18)', borderColor: '#4f46e5', borderWidth: 0, borderRadius: 6, yAxisID: 'y' },
                        { type: 'line', label: 'Profit', data: c.profit, borderColor: '#16a34a', backgroundColor: '#16a34a', tension: .35, pointRadius: 3, yAxisID: 'y' },
                        { type: 'line', label: 'Orders', data: c.orders, borderColor: '#f97316', borderDash: [4, 4], pointRadius: 0, tension: .35, yAxisID: 'y1' },
                    ],
                },
                options: {
                    responsive: true,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } } },
                    scales: {
                        x: { grid: { display: false } },
                        y: { beginAtZero: true, grid: { color: '#eef2f7' }, ticks: { callback: (v) => '৳' + v.toLocaleString() } },
                        y1: { beginAtZero: true, position: 'right', grid: { display: false }, ticks: { precision: 0 } },
                    },
                },
            });
        })();
    </script>
@endpush
