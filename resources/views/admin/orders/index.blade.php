@extends('admin.layouts.app')

@section('title', 'Orders')

@php($totalCount = collect($counts)->except('unverified')->sum())

@section('content')
    <div class="page-header">
        <div>
            <h1>Orders</h1>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li><li class="breadcrumb-item active">Orders</li></ol></nav>
        </div>
        <a href="{{ route('admin.orders.index', ['otp' => 'unverified']) }}" class="btn btn-soft-danger"><i class="bi bi-shield-exclamation me-1"></i> OTP not verified ({{ $unverified }})</a>
    </div>

    @if($ignored)
        <div class="alert alert-warning py-2 small"><i class="bi bi-funnel me-1"></i>Ignored invalid filter value(s): {{ implode(', ', $ignored) }}.</div>
    @endif

    <div class="status-tabs mb-3">
        <a href="{{ route('admin.orders.index', request()->except('status', 'page')) }}" class="{{ !request('status') ? 'active' : '' }}">All<span class="n">{{ $totalCount }}</span></a>
        @foreach(config('shop.order_statuses') as $key => $s)
            <a href="{{ route('admin.orders.index', ['status' => $key] + request()->except('status', 'page')) }}" class="{{ request('status') === $key ? 'active' : '' }}">{{ $s['label'] }}<span class="n">{{ $counts[$key] ?? 0 }}</span></a>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header">
            <form class="filter-bar" data-validate novalidate>
                @isset($filters['status'])<input type="hidden" name="status" value="{{ $filters['status'] }}">@endisset
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control search" placeholder="Order no, mobile, name, product, tracking…" maxlength="100">
                <div style="min-width: 170px">
                    <select name="district_id" class="form-select select2" data-placeholder="All districts">
                        <option></option>
                        @foreach($districts as $d)<option value="{{ $d->id }}" @selected(($filters['district_id'] ?? null) === $d->id)>{{ $d->name }}</option>@endforeach
                    </select>
                </div>
                <select name="otp" class="form-select">
                    <option value="">OTP: any</option>
                    <option value="verified" @selected(($filters['otp'] ?? null) === 'verified')>Verified</option>
                    <option value="unverified" @selected(($filters['otp'] ?? null) === 'unverified')>Not verified</option>
                </select>
                <div class="input-group input-daterange date-range">
                    <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                    <input type="text" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control" placeholder="From date" autocomplete="off" maxlength="10" pattern="\d{4}-\d{2}-\d{2}" data-msg-pattern="Use YYYY-MM-DD." aria-label="From date">
                    <span class="input-group-text">to</span>
                    <input type="text" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control" placeholder="To date" autocomplete="off" maxlength="10" pattern="\d{4}-\d{2}-\d{2}" data-msg-pattern="Use YYYY-MM-DD." aria-label="To date">
                </div>
                <button class="btn btn-soft-primary"><i class="bi bi-funnel me-1"></i> Filter</button>
                @if($filters || $ignored)<a href="{{ route('admin.orders.index') }}" class="btn btn-soft-secondary">Clear</a>@endif
            </form>
            <div class="date-presets mt-2">
                <span class="small text-muted me-1">Quick range:</span>
                @foreach($ranges as $label => [$from, $to])
                    @php($on = ($filters['from'] ?? null) === $from && ($filters['to'] ?? null) === $to)
                    <a href="{{ route('admin.orders.index', array_merge($filters, ['from' => $from, 'to' => $to])) }}" class="{{ $on ? 'active' : '' }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr><th>Order</th><th>Customer</th><th>Location</th><th class="text-center">Items</th><th class="text-end">Total</th><th class="text-end">Profit</th><th>OTP</th><th>Status</th><th class="text-end"></th></tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td>
                                <a href="{{ route('admin.orders.show', $order->unique_id) }}" class="title">#{{ $order->order_number }}</a>
                                <div class="small text-muted">{{ $order->created_at->format('d M, h:i A') }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $order->customer_name }}</div>
                                <a href="tel:{{ $order->mobile }}" class="small text-muted">{{ $order->mobile }}</a>
                            </td>
                            <td class="small">{{ optional($order->district)->name }}<div class="text-muted">{{ optional($order->thana)->name }}</div></td>
                            <td class="text-center">{{ $order->items_count }}</td>
                            <td class="text-end fw-semibold text-nowrap">{{ money($order->total) }}</td>
                            <td class="text-end fw-semibold text-nowrap {{ $order->profit >= 0 ? 'text-success' : 'text-danger' }}">{{ money($order->profit) }}</td>
                            <td>
                                @if($order->otp_verified)
                                    <span class="badge badge-soft-success"><i class="bi bi-patch-check-fill me-1"></i>Verified</span>
                                @else
                                    <span class="badge badge-soft-danger"><i class="bi bi-x-octagon me-1"></i>Not verified</span>
                                @endif
                            </td>
                            <td>{!! order_status_badge($order->status) !!}</td>
                            <td class="text-end">
                                <div class="row-actions">
                                    <a href="{{ route('admin.orders.invoice', $order->unique_id) }}" target="_blank" class="btn btn-soft-secondary btn-icon" title="Invoice"><i class="bi bi-printer"></i></a>
                                    <a href="{{ route('admin.orders.show', $order->unique_id) }}" class="btn btn-soft-primary btn-icon" title="Manage"><i class="bi bi-arrow-right"></i></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9"><div class="empty"><i class="bi bi-inbox"></i>No orders found</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())<div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2"><span class="small text-muted">{{ $orders->firstItem() }}–{{ $orders->lastItem() }} of {{ $orders->total() }}</span>{{ $orders->links() }}</div>@endif
    </div>
@endsection
