@extends('admin.layouts.app')

@section('title', 'Reviews')

{{-- Customers → Reviews: every rating & review (shown on the site right away). Hide one with the switch, or delete it. --}}
@section('content')
    <div class="page-header">
        <div>
            <h1>Reviews</h1>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li><li class="breadcrumb-item active">Reviews</li></ol></nav>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <form class="filter-bar">
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control search" placeholder="Search name, product or text…" maxlength="100">
                <select name="rating" class="form-select" style="max-width: 150px" aria-label="Rating">
                    <option value="">Any rating</option>
                    @for($i = 5; $i >= 1; $i--)<option value="{{ $i }}" @selected(($filters['rating'] ?? '') === (string) $i)>{{ $i }} {{ \Illuminate\Support\Str::plural('star', $i) }}</option>@endfor
                </select>
                <select name="status" class="form-select" style="max-width: 150px" aria-label="Status">
                    <option value="">Visible &amp; hidden</option>
                    <option value="visible" @selected(($filters['status'] ?? '') === 'visible')>Visible</option>
                    <option value="hidden" @selected(($filters['status'] ?? '') === 'hidden')>Hidden</option>
                </select>
                <button class="btn btn-soft-primary"><i class="bi bi-funnel me-1"></i> Filter</button>
                @if($filters)<a href="{{ route('admin.reviews.index') }}" class="btn btn-soft-secondary">Clear</a>@endif
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Product</th><th>Customer</th><th>Rating</th><th style="min-width: 260px">Review</th><th>Date</th><th>Visible</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    @forelse($reviews as $rv)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="{{ img_url($rv->product?->thumbnail) }}" class="thumb" alt="" loading="lazy">
                                    <div class="min-w-0">
                                        @if($rv->product)<a href="{{ route('product.show', $rv->product->slug) }}#reviews" target="_blank" class="d-block text-truncate cell-title" style="max-width: 200px">{{ $rv->product->name }}</a>@endif
                                    </div>
                                </div>
                            </td>
                            <td class="small"><div class="fw-semibold">{{ $rv->name }}</div><div class="text-muted">{{ $rv->masked_mobile }} · {{ $rv->order?->order_number }}</div></td>
                            <td class="text-nowrap"><span class="text-warning">@for($i = 1; $i <= 5; $i++)<i class="bi {{ $rv->rating >= $i ? 'bi-star-fill' : 'bi-star' }}"></i>@endfor</span></td>
                            <td class="small">
                                {{ \Illuminate\Support\Str::limit($rv->review, 180) }}
                                @if($rv->images)
                                    <div class="d-flex gap-1 mt-1">@foreach($rv->images as $img)<a href="{{ img_url($img) }}" target="_blank" rel="noopener"><img src="{{ img_url($img) }}" alt="" width="40" height="40" class="rounded-2 object-fit-cover"></a>@endforeach</div>
                                @endif
                            </td>
                            <td class="small text-nowrap text-muted">{{ $rv->created_at->format('d M Y') }}</td>
                            <td><div class="form-check form-switch mb-0"><input class="form-check-input" type="checkbox" role="switch" data-toggle-url="{{ route('admin.reviews.toggle', $rv->unique_id) }}" @checked($rv->is_active) aria-label="Visible on the site"></div></td>
                            <td class="text-end">@include('admin.partials.delete', ['action' => route('admin.reviews.destroy', $rv->unique_id), 'message' => 'The review will be removed from the product page. This order still cannot be reviewed again.'])</td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="empty"><i class="bi bi-star"></i>No reviews yet. Customers can review a product after their order is delivered.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($reviews->hasPages())<div class="card-footer">{{ $reviews->links() }}</div>@endif
    </div>
@endsection
