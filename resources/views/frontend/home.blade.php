@extends('layouts.frontend')

@push('head')
    @if($sliders->isNotEmpty())
        <link rel="preload" as="image" href="{{ img_url($sliders->first()->image) }}">
    @endif
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => setting('site_name', config('app.name')),
            'url' => route('home'),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => route('products.index').'?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
    {{-- One H1 per page for search engines and screen readers; the sections below use H2. --}}
    <h1 class="visually-hidden">{{ setting('site_name', config('app.name')) }}{{ setting('tagline') ? ' — '.setting('tagline') : ' — Online Shopping in Bangladesh' }}</h1>
    {{-- Slider --}}
    @if($sliders->isNotEmpty())
        <section class="hero pt-3 pt-lg-4">
            <div class="container">
                <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5000">
                    @if($sliders->count() > 1)
                        <div class="carousel-indicators">
                            @foreach($sliders as $i => $slide)
                                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="{{ $i }}" class="{{ $i === 0 ? 'active' : '' }}" aria-label="Slide {{ $i + 1 }}"></button>
                            @endforeach
                        </div>
                    @endif
                    <div class="carousel-inner rounded-xl">
                        @foreach($sliders as $i => $slide)
                            @php($link = $slide->link ? (preg_match('#^https?://#', $slide->link) ? $slide->link : url($slide->link)) : null)
                            <div class="carousel-item {{ $i === 0 ? 'active' : '' }}">
                                <img src="{{ img_url($slide->image) }}" alt="{{ $slide->title ?: 'Banner '.($i + 1) }}" width="1600" height="600" @if($i > 0) loading="lazy" @else fetchpriority="high" @endif>
                                @if($slide->title || $slide->subtitle)
                                    <div class="carousel-caption">
                                        @if($slide->title)<h2>{{ $slide->title }}</h2>@endif
                                        @if($slide->subtitle)<p class="mb-3">{{ $slide->subtitle }}</p>@endif
                                        @if($link && $slide->button_text)<a href="{{ $link }}" class="btn btn-light fw-bold">{{ $slide->button_text }} <i class="bi bi-arrow-right ms-1"></i></a>@endif
                                    </div>
                                @elseif($link)
                                    <a href="{{ $link }}" class="stretched-link" aria-label="Open offer"></a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    @if($sliders->count() > 1)
                        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Previous</span></button>
                        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Next</span></button>
                    @endif
                </div>
            </div>
        </section>
    @endif

    {{-- Trust features --}}
    <section class="features pt-4">
        <div class="container">
            <div class="row g-3 row-cols-2 row-cols-lg-4">
                <div class="col"><div class="feature"><i class="bi bi-truck"></i><div><h6>Nationwide Delivery</h6><p>All 64 districts</p></div></div></div>
                <div class="col"><div class="feature"><i class="bi bi-cash-coin"></i><div><h6>Cash on Delivery</h6><p>Pay when you receive</p></div></div></div>
                <div class="col"><div class="feature"><i class="bi bi-arrow-repeat"></i><div><h6>Easy Replacement</h6><p>7 days guarantee</p></div></div></div>
                <div class="col"><div class="feature"><i class="bi bi-headset"></i><div><h6>Support</h6><p>{{ setting('phone', 'Always here to help') }}</p></div></div></div>
            </div>
        </div>
    </section>

    {{-- Categories --}}
    {{-- Only worth a section once the shop has more than 6 categories (the menu bar covers fewer). --}}
    @if($menuCategories->count() > config('shop.home.category_section_min'))
        <section class="section pb-0">
            <div class="container">
                <div class="section-head">
                    <div><h2>Shop by Category</h2><p>Find what you need faster</p></div>
                    <a href="{{ route('products.index') }}" class="view-all">All products <i class="bi bi-arrow-right"></i></a>
                </div>
                <div class="row g-3 row-cols-3 row-cols-md-4 row-cols-lg-6">
                    @foreach($menuCategories->take(12) as $cat)
                        <div class="col">
                            <a href="{{ route('category.show', $cat->slug) }}" class="cat-tile">
                                <img src="{{ img_url($cat->image) }}" alt="{{ $cat->name }}" width="72" height="72" loading="lazy">
                                <h6>{{ $cat->name }}</h6>
                                <small>{{ $cat->products_count }} items</small>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- New arrivals --}}
    @if($newArrivals->isNotEmpty())
        <section class="section">
            <div class="container">
                <div class="section-head">
                    <div><h2>New Arrivals</h2><p>Fresh products just added to the store</p></div>
                    <a href="{{ route('products.index', ['sort' => 'latest']) }}" class="view-all">View all <i class="bi bi-arrow-right"></i></a>
                </div>
                <div class="row g-3 g-lg-4 row-cols-2 row-cols-md-3 row-cols-lg-4">
                    @foreach($newArrivals as $product)
                        @include('frontend.partials.product-card')
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Featured: 12 at a time with "Load more"; after shop.home.featured_max it becomes "View all" --}}
    @if($featured['items']->isNotEmpty())
        <section class="section pt-0" id="featured">
            <div class="container">
                <div class="section-head">
                    <div><h2>Featured Products</h2><p>Hand-picked best sellers our customers love</p></div>
                    <a href="{{ route('products.index', ['featured' => 1]) }}" class="view-all">View all <i class="bi bi-arrow-right"></i></a>
                </div>
                <div class="row g-3 g-lg-4 row-cols-2 row-cols-md-3 row-cols-lg-4" id="featuredGrid">
                    @include('frontend.partials.product-cards', ['products' => $featured['items']])
                </div>
                <div class="load-more-bar" id="featuredMore" data-url="{{ route('home.featured') }}" data-next="2">
                    <div class="load-more-count" aria-live="polite">Showing <strong data-loaded>{{ $featured['loaded'] }}</strong> of <strong>{{ $featured['total'] }}</strong> featured products</div>
                    <button type="button" class="btn btn-outline-primary btn-load-more {{ $featured['has_more'] ? '' : 'd-none' }}" data-load-more>
                        <i class="bi bi-arrow-down-circle me-1"></i> Load more
                    </button>
                    <a href="{{ route('products.index', ['featured' => 1]) }}" class="btn btn-primary btn-load-more {{ $featured['reached_max'] ? '' : 'd-none' }}" data-view-all>
                        View all featured products <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </section>
    @endif

    {{-- Blog: the 4 newest posts, last section above the footer (only when posts exist) --}}
    @if($blogs->isNotEmpty())
        <section class="section pt-0" id="blog">
            <div class="container">
                <div class="section-head">
                    <div><h2>From Our Blog</h2><p>Tips, guides and news</p></div>
                    <a href="{{ route('blog.index') }}" class="view-all">View all <i class="bi bi-arrow-right"></i></a>
                </div>
                <div class="row g-3 g-lg-4 row-cols-1 row-cols-sm-2 row-cols-lg-4">
                    @foreach($blogs as $post)
                        <div class="col">@include('frontend.blog.partials.card')</div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if($newArrivals->isEmpty())
        <div class="container py-5"><div class="empty-state card-plain"><i class="bi bi-shop"></i>Products are coming soon. Stay tuned!</div></div>
    @endif
@endsection
