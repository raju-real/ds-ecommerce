@extends('admin.layouts.app')

@section('title', $product->name)

@section('content')
    <div class="page-header">
        <div>
            <h1>{{ $product->name }}</h1>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.products.index') }}">Products</a></li><li class="breadcrumb-item active">{{ $product->code }}</li></ol></nav>
        </div>
        <div class="d-flex gap-2">
            @include('admin.partials.product-share', ['product' => $product, 'label' => 'Share links'])
            <a href="{{ route('product.show', $product->slug) }}" target="_blank" class="btn btn-soft-secondary"><i class="bi bi-box-arrow-up-right me-1"></i> View on store</a>
            <a href="{{ route('admin.products.edit', $product->unique_id) }}" class="btn btn-primary"><i class="bi bi-pencil me-1"></i> Edit</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3"><div class="card stat-card"><span class="icon bg-soft-primary"><i class="bi bi-receipt"></i></span><div><div class="label">Orders</div><div class="value">{{ number_format($sales['orders']) }}</div></div></div></div>
        <div class="col-6 col-xl-3"><div class="card stat-card"><span class="icon bg-soft-info"><i class="bi bi-boxes"></i></span><div><div class="label">Units sold</div><div class="value">{{ number_format($sales['qty']) }}</div></div></div></div>
        <div class="col-6 col-xl-3"><div class="card stat-card"><span class="icon bg-soft-warning"><i class="bi bi-currency-exchange"></i></span><div><div class="label">Sales</div><div class="value">{{ money($sales['revenue']) }}</div></div></div></div>
        <div class="col-6 col-xl-3"><div class="card stat-card"><span class="icon bg-soft-success"><i class="bi bi-graph-up-arrow"></i></span><div><div class="label">Profit earned</div><div class="value text-success">{{ money($sales['profit']) }}</div></div></div></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-body">
                    <img src="{{ $product->image_url }}" class="w-100 rounded-3 border mb-3" alt="{{ $product->name }}">
                    <div class="img-grid">
                        @foreach($product->gallery() as $img)<div class="item"><img src="{{ img_url($img->thumb) }}" alt="">@if($loop->first)<span class="main-tag">THUMB</span>@endif</div>@endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card mb-4">
                <div class="card-header"><h5>Pricing</h5></div>
                <div class="card-body">
                    <div class="row text-center g-3">
                        <div class="col-6 col-md-3"><div class="small text-muted">Regular</div><div class="fw-bold fs-5">{{ money($product->unit_price) }}</div></div>
                        <div class="col-6 col-md-3"><div class="small text-muted">Selling</div><div class="fw-bold fs-5 text-primary">{{ money($product->selling_price) }}</div></div>
                        <div class="col-6 col-md-3"><div class="small text-muted">Vendor cost</div><div class="fw-bold fs-5">{{ money($product->drop_shipping_price) }}</div></div>
                        <div class="col-6 col-md-3"><div class="small text-muted">Profit / unit</div><div class="fw-bold fs-5 {{ $product->profit >= 0 ? 'text-success' : 'text-danger' }}">{{ money($product->profit) }} <small class="text-muted fs-6">({{ $product->profit_margin }}%)</small></div></div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h5>Details</h5></div>
                <div class="card-body">
                    <dl class="kv mb-0">
                        <dt>Code</dt><dd>{{ $product->code }}</dd>
                        <dt>Category</dt><dd>{{ optional($product->category)->name }} @if($product->subcategory) › {{ $product->subcategory->name }} @endif</dd>
                        <dt>Vendor</dt><dd>{{ optional($product->vendor)->name ?? '—' }} @if($product->vendor?->phone)<span class="text-muted">· {{ $product->vendor->phone }}</span>@endif</dd>
                        <dt>Vendor link</dt><dd>@if($product->vendor_product_url)<a href="{{ $product->vendor_product_url }}" target="_blank" rel="noopener">{{ \Illuminate\Support\Str::limit($product->vendor_product_url, 50) }}</a>@else — @endif</dd>
                        <dt>Status</dt>
                        <dd>
                            {!! $product->is_active ? '<span class="badge badge-soft-success">Active</span>' : '<span class="badge badge-soft-secondary">Inactive</span>' !!}
                            {!! $product->in_stock ? '<span class="badge badge-soft-info">In stock</span>' : '<span class="badge badge-soft-danger">Out of stock</span>' !!}
                            @if($product->is_featured)<span class="badge badge-soft-warning">Featured</span>@endif
                        </dd>
                        <dt>URL</dt><dd class="text-break"><a href="{{ $product->url }}" target="_blank">{{ $product->url }}</a></dd>
                        <dt>Updated</dt><dd>{{ $product->updated_at->format('d M Y, h:i A') }}</dd>
                    </dl>
                    @if($product->short_description)<hr><p class="mb-0 text-muted">{{ $product->short_description }}</p>@endif
                </div>
            </div>
        </div>
    </div>
@endsection
