@extends('admin.layouts.app')

@section('title', 'Products')

@section('content')
    <div class="page-header">
        <div>
            <h1>Products</h1>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li><li class="breadcrumb-item active">Products</li></ol></nav>
        </div>
        <a href="{{ route('admin.products.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Add product</a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3"><div class="card stat-card"><span class="icon bg-soft-primary"><i class="bi bi-box-seam"></i></span><div><div class="label">Total products</div><div class="value">{{ $stats['total'] }}</div></div></div></div>
        <div class="col-6 col-xl-3"><div class="card stat-card"><span class="icon bg-soft-success"><i class="bi bi-eye"></i></span><div><div class="label">Active</div><div class="value">{{ $stats['active'] }}</div></div></div></div>
        <div class="col-6 col-xl-3"><div class="card stat-card"><span class="icon bg-soft-warning"><i class="bi bi-star"></i></span><div><div class="label">Featured</div><div class="value">{{ $stats['featured'] }}</div></div></div></div>
        <div class="col-6 col-xl-3"><div class="card stat-card"><span class="icon bg-soft-purple"><i class="bi bi-cash-coin"></i></span><div><div class="label">Avg. profit / unit</div><div class="value">{{ money($stats['avg_profit']) }}</div></div></div></div>
    </div>

    <div class="card">
        <div class="card-header">
            <form class="filter-bar">
                <input type="search" name="q" value="{{ request('q') }}" class="form-control search" placeholder="Search name or code…" maxlength="100">
                <div style="min-width: 190px">
                    <select name="category_id" class="form-select select2" data-placeholder="All categories">
                        <option></option>
                        @foreach($categories as $c)<option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>{{ $c->name }}</option>@endforeach
                    </select>
                </div>
                <div style="min-width: 190px">
                    <select name="vendor_id" class="form-select select2" data-placeholder="All vendors">
                        <option></option>
                        @foreach($vendors as $v)<option value="{{ $v->id }}" @selected(request('vendor_id') == $v->id)>{{ $v->name }}</option>@endforeach
                    </select>
                </div>
                <div style="min-width: 170px">
                    <select name="brand_id" class="form-select select2" data-placeholder="All brands">
                        <option></option>
                        @foreach($brands as $b)<option value="{{ $b->id }}" @selected(request('brand_id') == $b->id)>{{ $b->name }}</option>@endforeach
                    </select>
                </div>
                <select name="status" class="form-select">
                    <option value="">Any status</option>
                    @foreach(['active' => 'Active', 'inactive' => 'Inactive', 'featured' => 'Featured', 'out' => 'Out of stock'] as $k => $l)
                        <option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>
                    @endforeach
                </select>
                <button class="btn btn-soft-primary"><i class="bi bi-funnel me-1"></i> Filter</button>
                @if(request()->hasAny(['q', 'category_id', 'vendor_id', 'brand_id', 'status']))<a href="{{ route('admin.products.index') }}" class="btn btn-soft-secondary">Clear</a>@endif
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-compact">
                <thead>
                    <tr>
                        <th>Product</th><th>Category / Vendor</th>
                        <th class="text-end">Price</th><th class="text-end">Cost</th><th class="text-end">Profit</th>
                        <th class="text-center" title="Visible on the store">Active</th><th class="text-center" title="Shown in Featured Products">Feat.</th><th class="text-center" title="In stock">Stock</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $p)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <img src="{{ $p->thumb_url }}" class="thumb" alt="" loading="lazy">
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.products.show', $p->unique_id) }}" class="title d-block text-truncate cell-title">{{ $p->name }}</a>
                                        <div class="d-flex align-items-center gap-2"><span class="small text-muted">{{ $p->code }}</span>@include('admin.products.partials.seo-status')</div>
                                    </div>
                                </div>
                            </td>
                            <td class="small">
                                <div title="Category"><i class="bi bi-collection text-muted me-1"></i>{{ optional($p->category)->name ?? '—' }}@if($p->subcategory)<span class="text-muted"> › {{ $p->subcategory->name }}</span>@endif</div>
                                <div title="Vendor · Brand" class="mt-1"><i class="bi bi-shop text-muted me-1"></i>{{ optional($p->vendor)->name ?? 'No vendor' }}@if($p->brand)<span class="text-muted"> · {{ $p->brand->name }}</span>@endif</div>
                            </td>
                            <td class="text-end text-nowrap">
                                <span class="fw-semibold">{{ money($p->selling_price) }}</span>
                                @if($p->hasDiscount())<div class="small text-muted text-decoration-line-through">{{ money($p->unit_price) }}</div>@endif
                            </td>
                            <td class="text-end text-nowrap text-muted">{{ money($p->drop_shipping_price) }}</td>
                            <td class="text-end text-nowrap">
                                <span class="fw-bold {{ $p->profit >= 0 ? 'text-success' : 'text-danger' }}">{{ money($p->profit) }}</span>
                                <div class="small text-muted">{{ $p->profit_margin }}%</div>
                            </td>
                            @foreach(['is_active', 'is_featured', 'in_stock'] as $flag)
                                <td class="text-center">
                                    <div class="form-check form-switch d-inline-block m-0">
                                        <input class="form-check-input" type="checkbox" data-toggle-url="{{ route('admin.products.toggle', [$p->unique_id, $flag]) }}" @checked($p->{$flag}) aria-label="{{ $flag }}">
                                    </div>
                                </td>
                            @endforeach
                            <td class="text-end">
                                <div class="row-actions">
                                    @include('admin.partials.product-share', ['product' => $p])
                                    <a href="{{ route('product.show', $p->slug) }}" target="_blank" class="btn btn-soft-secondary btn-icon" title="View on store"><i class="bi bi-box-arrow-up-right"></i></a>
                                    <a href="{{ route('admin.products.edit', $p->unique_id) }}" class="btn btn-soft-primary btn-icon" title="Edit"><i class="bi bi-pencil"></i></a>
                                    @include('admin.partials.delete', ['action' => route('admin.products.destroy', $p->unique_id), 'message' => 'The product and all its images will be deleted.'])
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9"><div class="empty"><i class="bi bi-box-seam"></i>No products found</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($products->hasPages())<div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2"><span class="small text-muted">{{ $products->firstItem() }}–{{ $products->lastItem() }} of {{ $products->total() }}</span>{{ $products->links() }}</div>@endif
    </div>
@endsection
