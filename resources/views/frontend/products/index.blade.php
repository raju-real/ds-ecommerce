@extends('layouts.frontend')

@php
    $heading = $subcategory->name ?? $category->name ?? $brand->name ?? (!empty($filters['q']) ? 'Search: “'.$filters['q'].'”' : (!empty($filters['featured']) ? 'Featured Products' : 'All Products'));
    $seo = $category ?? $brand;
    $baseUrl = $subcategory ? route('subcategory.show', [$category->slug, $subcategory->slug])
        : ($category ? route('category.show', $category->slug) : ($brand ? route('brand.show', $brand->slug) : route('products.index')));
    $canonical = $products->currentPage() > 1 ? $baseUrl.'?page='.$products->currentPage() : $baseUrl;
@endphp

@section('title', $seo?->meta_title ?: $heading.($products->currentPage() > 1 ? ' - Page '.$products->currentPage() : ''))
@section('description', $seo?->meta_description ?: 'Buy '.$heading.' online at the best price in Bangladesh. Cash on delivery available.')
@section('canonical', $canonical)
@if($seoImage = $category?->image ?? $brand?->logo)
    @section('og_image', img_url($seoImage))
@endif
@if(!empty($filters['q']) || request()->hasAny(['sort', 'min', 'max', 'stock', 'featured']))
    @section('robots', 'noindex, follow')
@endif

@push('head')
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_values(array_filter([
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')],
                $category ? ['@type' => 'ListItem', 'position' => 2, 'name' => $category->name, 'item' => route('category.show', $category->slug)] : null,
                $subcategory ? ['@type' => 'ListItem', 'position' => 3, 'name' => $subcategory->name, 'item' => $baseUrl] : null,
                $brand ? ['@type' => 'ListItem', 'position' => 2, 'name' => $brand->name, 'item' => $baseUrl] : null,
            ])),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
    <div class="page-head">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                    @if($category || $brand)
                        <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Products</a></li>
                        @if($subcategory)
                            <li class="breadcrumb-item"><a href="{{ route('category.show', $category->slug) }}">{{ $category->name }}</a></li>
                        @endif
                    @endif
                    <li class="breadcrumb-item active" aria-current="page">{{ $subcategory->name ?? $category->name ?? $brand->name ?? 'Products' }}</li>
                </ol>
            </nav>
            <div class="d-flex align-items-center gap-3">
                @if($brand && $brand->logo)<img src="{{ img_url($brand->logo) }}" alt="{{ $brand->name }}" width="48" height="48" class="rounded-3 border bg-white" style="object-fit:contain">@endif
                <h1>{{ $heading }}</h1>
            </div>
        </div>
    </div>

    <div class="container py-4">
        <div class="row g-4">
            {{-- Filters --}}
            <aside class="col-lg-3">
                <div class="d-lg-none mb-2">
                    <button class="btn btn-outline-primary w-100" type="button" data-bs-toggle="collapse" data-bs-target="#filters"><i class="bi bi-funnel me-1"></i> Filters & Categories</button>
                </div>
                <div class="collapse d-lg-block" id="filters">
                    <div class="filter-card mb-3">
                        <h6>Categories</h6>
                        <div class="cat-list">
                            <a href="{{ route('products.index') }}" class="{{ !$category && !$brand ? 'active' : '' }}"><span>All products</span></a>
                            @foreach($menuCategories as $cat)
                                <a href="{{ route('category.show', $cat->slug) }}" class="{{ $category?->id === $cat->id && !$subcategory ? 'active' : '' }}">
                                    <span>{{ $cat->name }}</span><span class="text-muted small">{{ $cat->products_count }}</span>
                                </a>
                                @if($category?->id === $cat->id && $cat->subcategories->isNotEmpty())
                                    <div class="sub">
                                        @foreach($cat->subcategories as $sub)
                                            <a href="{{ route('subcategory.show', [$cat->slug, $sub->slug]) }}" class="{{ $subcategory?->id === $sub->id ? 'active' : '' }}">{{ $sub->name }}</a>
                                        @endforeach
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    @if($brands->isNotEmpty())
                        <div class="filter-card mb-3">
                            <h6>Brands</h6>
                            <div class="cat-list">
                                @foreach($brands as $b)
                                    <a href="{{ route('brand.show', $b->slug) }}" class="{{ $brand?->id === $b->id ? 'active' : '' }}">
                                        <span>{{ $b->name }}</span><span class="text-muted small">{{ $b->products_count }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <form class="filter-card" method="get" action="{{ $baseUrl }}">
                        @if(!empty($filters['q']))<input type="hidden" name="q" value="{{ $filters['q'] }}">@endif
                        @if($filters['sort'])<input type="hidden" name="sort" value="{{ $filters['sort'] }}">@endif
                        @if(!empty($filters['featured']))<input type="hidden" name="featured" value="1">@endif
                        <h6>Price range ({{ config('shop.currency') }})</h6>
                        <div class="d-flex gap-2 align-items-center mb-3">
                            <input type="number" name="min" class="form-control form-control-sm" placeholder="Min {{ $bounds['min'] }}" value="{{ $filters['min'] }}" min="0" max="9999999" aria-label="Minimum price">
                            <span class="text-muted">–</span>
                            <input type="number" name="max" class="form-control form-control-sm" placeholder="Max {{ $bounds['max'] }}" value="{{ $filters['max'] }}" min="0" max="9999999" aria-label="Maximum price">
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="stock" value="in" id="inStock" @checked(request('stock') === 'in')>
                            <label class="form-check-label small" for="inStock">In stock only</label>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary btn-sm flex-grow-1">Apply</button>
                            <a href="{{ $baseUrl }}" class="btn btn-light btn-sm">Reset</a>
                        </div>
                    </form>
                </div>
            </aside>

            {{-- Products --}}
            <div class="col-lg-9">
                <div class="toolbar d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <span class="text-muted small">
                        @if($products->total())
                            Showing <strong class="text-dark">{{ $products->firstItem() }}–{{ $products->lastItem() }}</strong> of <strong class="text-dark">{{ $products->total() }}</strong> products
                        @else
                            No products found
                        @endif
                    </span>
                    <form method="get" action="{{ $baseUrl }}" class="d-flex align-items-center gap-2">
                        @foreach(request()->except('sort', 'page') as $k => $v)
                            @if(is_string($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
                        @endforeach
                        <label for="sort" class="small text-muted text-nowrap">Sort by</label>
                        <select name="sort" id="sort" class="form-select form-select-sm" data-auto-submit>
                            @foreach($sorts as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['sort'] ?? 'latest') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>

                @if($products->isEmpty())
                    <div class="card-plain empty-state">
                        <i class="bi bi-search"></i>
                        <p class="mb-3">We couldn't find products matching your filters.</p>
                        <a href="{{ route('products.index') }}" class="btn btn-primary btn-sm">Browse all products</a>
                    </div>
                @else
                    <div class="row g-3 g-lg-4 row-cols-2 row-cols-md-3 row-cols-lg-4">
                        @foreach($products as $product)
                            @include('frontend.partials.product-card')
                        @endforeach
                    </div>
                    <div class="d-flex justify-content-center mt-4">
                        {{ $products->onEachSide(1)->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
