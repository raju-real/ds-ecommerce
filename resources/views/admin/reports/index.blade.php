@extends('admin.layouts.app')

@section('title', 'Profit report')

@php($s = $report['summary'])

@section('content')
    <div class="page-header">
        <div>
            <h1>Profit report</h1>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li><li class="breadcrumb-item active">Reports</li></ol></nav>
        </div>
        <form class="filter-bar no-print" data-validate novalidate>
            <div class="input-group input-daterange date-range">
                    <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                    <input type="text" name="from" value="{{ $from }}" class="form-control" placeholder="From date" autocomplete="off" maxlength="10" pattern="\d{4}-\d{2}-\d{2}" data-msg-pattern="Use YYYY-MM-DD." aria-label="From date">
                    <span class="input-group-text">to</span>
                    <input type="text" name="to" value="{{ $to }}" class="form-control" placeholder="To date" autocomplete="off" maxlength="10" pattern="\d{4}-\d{2}-\d{2}" data-msg-pattern="Use YYYY-MM-DD." aria-label="To date">
                </div>
            <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i> Apply</button>
            <button type="button" class="btn btn-soft-secondary" onclick="window.print()"><i class="bi bi-printer"></i></button>
        </form>
    </div>

    <div class="d-flex gap-2 mb-3 no-print flex-wrap">
        @foreach([
            'Today' => [now()->toDateString(), now()->toDateString()],
            'Last 7 days' => [now()->subDays(6)->toDateString(), now()->toDateString()],
            'This month' => [now()->startOfMonth()->toDateString(), now()->toDateString()],
            'Last month' => [now()->subMonthNoOverflow()->startOfMonth()->toDateString(), now()->subMonthNoOverflow()->endOfMonth()->toDateString()],
            'This year' => [now()->startOfYear()->toDateString(), now()->toDateString()],
        ] as $label => [$f, $t])
            <a href="{{ route('admin.reports', ['from' => $f, 'to' => $t]) }}" class="btn btn-sm {{ $from === $f && $to === $t ? 'btn-primary' : 'btn-soft-secondary' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3"><div class="card stat-card"><span class="icon bg-soft-primary"><i class="bi bi-receipt"></i></span><div><div class="label">Orders (excl. cancelled)</div><div class="value">{{ number_format($s['orders']) }}</div></div></div></div>
        <div class="col-6 col-xl-3"><div class="card stat-card"><span class="icon bg-soft-info"><i class="bi bi-currency-exchange"></i></span><div><div class="label">Revenue</div><div class="value">{{ money($s['revenue']) }}</div><div class="sub">incl. {{ money($s['delivery']) }} delivery</div></div></div></div>
        <div class="col-6 col-xl-3"><div class="card stat-card"><span class="icon bg-soft-warning"><i class="bi bi-shop"></i></span><div><div class="label">Vendor + courier + VAT</div><div class="value">{{ money($s['cost'] + $s['courier'] + ($s['vat'] ?? 0)) }}</div><div class="sub">{{ money($s['cost']) }} + {{ money($s['courier']) }} + {{ money($s['vat'] ?? 0) }} VAT</div></div></div></div>
        <div class="col-6 col-xl-3"><div class="card stat-card"><span class="icon bg-soft-success"><i class="bi bi-graph-up-arrow"></i></span><div><div class="label">Net profit <span class="text-muted fw-normal">(delivered)</span></div><div class="value text-success">{{ money($s['profit']) }}</div><div class="sub" title="Only delivered orders count as profit">{{ number_format($s['delivered'] ?? 0) }} delivered · {{ ($s['delivered_revenue'] ?? 0) > 0 ? round($s['profit'] / $s['delivered_revenue'] * 100, 1) : 0 }}% margin<br>{{ money($s['expected_profit'] ?? 0) }} expected from orders on the way</div></div></div></div>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header"><h5>Profit by vendor</h5></div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead><tr><th>Vendor</th><th class="text-center">Orders</th><th class="text-center">Units</th><th class="text-end">Sales</th><th class="text-end">Cost</th><th class="text-end">Profit <span class="fw-normal text-muted">(delivered)</span></th><th class="text-end">Expected</th></tr></thead>
                        <tbody>
                            @forelse($report['vendors'] as $row)
                                <tr>
                                    <td class="fw-semibold">{{ $row->vendor }}</td>
                                    <td class="text-center">{{ $row->orders }}</td>
                                    <td class="text-center">{{ $row->qty }}</td>
                                    <td class="text-end">{{ money($row->sales) }}</td>
                                    <td class="text-end text-muted">{{ money($row->cost) }}</td>
                                    <td class="text-end fw-bold text-success">{{ money($row->profit) }}</td>
                                    <td class="text-end text-muted" title="Becomes profit when these orders are delivered">{{ money($row->expected_profit ?? 0) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7"><div class="empty"><i class="bi bi-bar-chart"></i>No sales in this period</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer small text-muted">Product profit excludes delivery charges and courier costs, which are counted in the net profit above.</div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header"><h5>Orders by status</h5></div>
                <div class="card-body">
                    @forelse($report['statuses'] as $row)
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            {!! order_status_badge($row->status) !!}
                            <span><strong>{{ $row->c }}</strong> <span class="text-muted small">· {{ money($row->total) }}</span></span>
                        </div>
                    @empty
                        <div class="empty"><i class="bi bi-inbox"></i>No orders</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
