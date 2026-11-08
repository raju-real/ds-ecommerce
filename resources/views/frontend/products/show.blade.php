@extends('layouts.frontend')

@section('title', $product->meta_title ?: $product->name)
@section('description', $product->meta_description ?: ($product->short_description ?: strip_tags($product->description)))
@section('og_type', 'product')
@section('og_image', $product->image_url)
@section('canonical', $product->url)

@push('head')
    <meta property="product:price:amount" content="{{ $product->selling_price }}">
    <meta property="product:price:currency" content="BDT">
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Product',
                    'name' => $product->name,
                    'sku' => $product->code,
                    'image' => $product->gallery()->map(fn ($i) => img_url($i->path))->all(),
                    'description' => \Illuminate\Support\Str::limit(strip_tags($product->short_description ?: $product->description), 500),
                    'category' => optional($product->category)->name,
                    'brand' => ['@type' => 'Brand', 'name' => optional($product->brand)->name ?? setting('site_name', config('app.name'))],
                    'offers' => [
                        '@type' => 'Offer',
                        'url' => $product->url,
                        'priceCurrency' => 'BDT',
                        'price' => $product->selling_price,
                        'availability' => $product->in_stock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                        'itemCondition' => 'https://schema.org/NewCondition',
                    ],
                ],
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => array_values(array_filter([
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')],
                        $product->category ? ['@type' => 'ListItem', 'position' => 2, 'name' => $product->category->name, 'item' => route('category.show', $product->category->slug)] : null,
                        ['@type' => 'ListItem', 'position' => 3, 'name' => $product->name, 'item' => $product->url],
                    ])),
                ],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
    <div class="container pt-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                @if($product->category)
                    <li class="breadcrumb-item"><a href="{{ route('category.show', $product->category->slug) }}">{{ $product->category->name }}</a></li>
                @endif
                @if($product->subcategory && $product->category)
                    <li class="breadcrumb-item"><a href="{{ route('subcategory.show', [$product->category->slug, $product->subcategory->slug]) }}">{{ $product->subcategory->name }}</a></li>
                @endif
                <li class="breadcrumb-item active text-truncate" aria-current="page" style="max-width: 260px">{{ $product->name }}</li>
            </ol>
        </nav>
    </div>

    <div class="container py-3">
        <div class="pd-card">
            @include('frontend.products.partials.buy-box', ['uid' => 'pd'])
        </div>

        <div class="pd-card mt-4">
            <ul class="nav nav-pills-line border-bottom mb-4" role="tablist">
                <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-desc" type="button" role="tab">Description</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-delivery" type="button" role="tab">Delivery & Payment</button></li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane fade show active description" id="tab-desc" role="tabpanel">
                    {{-- Sanitised again on output so descriptions saved before the sanitiser existed are safe too. --}}
                    {!! \App\Support\RichText::render(\App\Support\RichText::sanitize($product->description), null, $product->name) ?: '<p class="text-muted">No description available.</p>' !!}
                </div>
                <div class="tab-pane fade description" id="tab-delivery" role="tabpanel">
                    <ul>
                        <li>Delivery charge depends on your district and is shown at checkout.</li>
                        <li>Usual delivery time: Dhaka 1–3 days, outside Dhaka 3–5 days.</li>
                        <li>Pay cash to the delivery person after checking the product.</li>
                        <li>Track your order any time from “Track Order” with your order number and mobile number.</li>
                    </ul>
                </div>
            </div>
        </div>

        @if($related->isNotEmpty())
            <section class="section pb-0">
                <div class="section-head"><div><h2>You may also like</h2></div></div>
                <div class="row g-3 g-lg-4 row-cols-2 row-cols-md-3 row-cols-lg-4">
                    @foreach($related as $item)
                        @include('frontend.partials.product-card', ['product' => $item])
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
