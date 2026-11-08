@extends('admin.layouts.app')

@section('title', 'Coupons')

@section('content')
    <div class="page-header">
        <div>
            <h1>Coupons</h1>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li><li class="breadcrumb-item active">Coupons</li></ol></nav>
        </div>
        <a href="{{ route('admin.coupons.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Add coupon</a>
    </div>

    <div class="card">
        <div class="card-header">
            <form class="filter-bar">
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control search" placeholder="Search code…" maxlength="50">
                <select name="type" class="form-select" style="max-width: 240px" aria-label="Type">
                    <option value="">All types</option>
                    @foreach(\App\Models\Coupon::TYPES as $value => $label)<option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>@endforeach
                </select>
                <button class="btn btn-soft-primary"><i class="bi bi-funnel me-1"></i> Filter</button>
                @if($filters)<a href="{{ route('admin.coupons.index') }}" class="btn btn-soft-secondary">Clear</a>@endif
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Code</th><th>Type</th><th>Discount</th><th class="text-center">Used</th><th>Valid</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    @forelse($coupons as $c)
                        @php
                            $status = ! $c->is_active ? ['Hidden', 'secondary']
                                : ($c->ends_at && $c->ends_at->isPast() ? ['Expired', 'danger']
                                : ($c->starts_at && $c->starts_at->isFuture() ? ['Scheduled', 'info']
                                : ($c->usage_limit && $c->used_count >= $c->usage_limit ? ['Used up', 'warning'] : ['Active', 'success'])));
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('admin.coupons.edit', $c->unique_id) }}" class="title font-monospace">{{ $c->code }}</a>
                                @if($c->note)<div class="small text-muted text-truncate" style="max-width: 220px">{{ $c->note }}</div>@endif
                            </td>
                            <td class="small">
                                <span class="badge badge-soft-{{ ['all_users' => 'primary', 'new_user' => 'purple', 'fixed_user' => 'indigo'][$c->type] }}">{{ \App\Models\Coupon::TYPES[$c->type] }}</span>
                                @if($c->type === 'fixed_user')<div class="text-muted mt-1">{{ count($c->mobiles ?? []) }} mobile(s)</div>@endif
                            </td>
                            <td class="small">
                                <span class="fw-semibold">{{ $c->label }}</span>
                                @if($c->min_order)<div class="text-muted">Min order {{ money($c->min_order) }}</div>@endif
                            </td>
                            <td class="text-center">
                                <span class="badge badge-soft-primary">{{ $c->used_count }}{{ $c->usage_limit ? ' / '.$c->usage_limit : '' }}</span>
                                @if($c->discount_given > 0)<div class="small text-muted mt-1">−{{ money($c->discount_given) }}</div>@endif
                            </td>
                            <td class="small text-nowrap">
                                {{ $c->starts_at?->format('d M Y') ?? 'Now' }} → {{ $c->ends_at?->format('d M Y') ?? 'No end' }}
                                <div class="text-muted">{{ $c->per_mobile_limit }}× per mobile</div>
                            </td>
                            <td><span class="badge badge-soft-{{ $status[1] }}">{{ $status[0] }}</span></td>
                            <td class="text-end">
                                <div class="row-actions">
                                    <button type="button" class="btn btn-soft-secondary btn-icon" data-copy="{{ $c->code }}" data-copy-msg="Coupon code copied" title="Copy code"><i class="bi bi-clipboard"></i></button>
                                    <a href="{{ route('admin.coupons.edit', $c->unique_id) }}" class="btn btn-soft-primary btn-icon" title="Edit"><i class="bi bi-pencil"></i></a>
                                    @include('admin.partials.delete', ['action' => route('admin.coupons.destroy', $c->unique_id), 'message' => 'The coupon will stop working. Orders that already used it keep their discount.'])
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="empty"><i class="bi bi-ticket-perforated"></i>No coupons yet.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($coupons->hasPages())<div class="card-footer">{{ $coupons->links() }}</div>@endif
    </div>
@endsection
