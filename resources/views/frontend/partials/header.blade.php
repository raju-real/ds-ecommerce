{{-- Top bar --}}
<div class="topbar py-2 d-none d-md-block">
    <div class="container d-flex justify-content-between align-items-center gap-3">
        <div class="d-flex align-items-center gap-3">
            @if(setting('topbar_text'))<span><i class="bi bi-megaphone me-1"></i>{{ setting('topbar_text') }}</span>@endif
        </div>
        <div class="d-flex align-items-center gap-3">
            @if(setting('phone'))<a href="tel:{{ setting('phone') }}"><i class="bi bi-telephone me-1"></i>{{ setting('phone') }}</a>@endif
            @if(setting('email'))<a href="mailto:{{ setting('email') }}" class="d-none d-lg-inline"><i class="bi bi-envelope me-1"></i>{{ setting('email') }}</a>@endif
            <a href="{{ route('track') }}"><i class="bi bi-truck me-1"></i>Track Order</a>
            <span class="social d-flex gap-1">
                @foreach(['facebook' => 'Facebook', 'messenger' => 'Messenger', 'instagram' => 'Instagram', 'youtube' => 'YouTube', 'tiktok' => 'TikTok'] as $key => $label)
                    @if(setting($key))<a href="{{ setting($key) }}" target="_blank" rel="noopener" aria-label="{{ $label }}" title="{{ $key === 'messenger' ? 'Chat on Messenger' : $label }}"><i class="bi bi-{{ $key }}"></i></a>@endif
                @endforeach
            </span>
        </div>
    </div>
</div>

{{-- Main header --}}
<header class="site-header">
    <div class="container py-3">
        <div class="d-flex align-items-center gap-3 gap-lg-4">
            <button class="btn btn-link text-dark p-0 d-lg-none fs-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu" aria-label="Open menu"><i class="bi bi-list"></i></button>

            <a href="{{ route('home') }}" class="logo flex-shrink-0" aria-label="{{ $siteName }}">
                @if(setting('logo'))
                    <img src="{{ img_url(setting('logo')) }}" alt="{{ $siteName }}" height="44">
                @else
                    @php($parts = explode(' ', $siteName, 2))
                    <span class="logo-text">{{ $parts[0] }}<span>{{ isset($parts[1]) ? ' '.$parts[1] : '.' }}</span></span>
                @endif
            </a>

            <form action="{{ route('products.index') }}" method="get" class="search-form flex-grow-1 d-none d-md-block mx-lg-4" role="search" data-suggest>
                <input type="search" name="q" class="form-control" placeholder="Search products, categories, brands…" value="{{ request('q') }}" maxlength="{{ config('shop.search.max_chars') }}" aria-label="Search products">
                <button class="btn btn-primary" type="submit" aria-label="Search"><i class="bi bi-search"></i></button>
            </form>

            <div class="d-flex align-items-center gap-3 gap-lg-4 ms-auto">
                @if(setting('phone'))
                    <a href="tel:{{ setting('phone') }}" class="header-action d-none d-xl-inline-flex">
                        <span class="icon"><i class="bi bi-headset"></i></span>
                        <span><small>Call us</small><span class="fw-semibold">{{ setting('phone') }}</span></span>
                    </a>
                @endif
                <a href="{{ route('track') }}" class="header-action d-none d-lg-inline-flex">
                    <span class="icon"><i class="bi bi-truck"></i></span>
                    <span><small>Your order</small><span class="fw-semibold">Track</span></span>
                </a>
                <button type="button" class="header-action" data-bs-toggle="offcanvas" data-bs-target="#miniCart" aria-label="Open cart">
                    <span class="icon"><i class="bi bi-bag"></i></span>
                    <span class="count {{ $cartCount ? '' : 'd-none' }}" data-cart-count>{{ $cartCount }}</span>
                    <span class="d-none d-lg-block text-start"><small>Shopping</small><span class="fw-semibold">Cart</span></span>
                </button>
            </div>
        </div>

        <form action="{{ route('products.index') }}" method="get" class="search-form mt-3 d-md-none" role="search" data-suggest>
            <input type="search" name="q" class="form-control" placeholder="Search products, brands…" value="{{ request('q') }}" maxlength="{{ config('shop.search.max_chars') }}" aria-label="Search products">
            <button class="btn btn-primary" type="submit" aria-label="Search"><i class="bi bi-search"></i></button>
        </form>
    </div>

    <nav class="cat-nav d-none d-lg-block" aria-label="Categories">
        <div class="container">
            <ul class="nav cat-scroll flex-nowrap">
                <li class="nav-item"><a class="nav-link ps-0 {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Home</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('products.index') ? 'active' : '' }}" href="{{ route('products.index') }}">All Products</a></li>
                @foreach($menuCategories as $cat)
                    @if($cat->subcategories->isNotEmpty())
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ request()->route('slug') === $cat->slug ? 'active' : '' }}" href="{{ route('category.show', $cat->slug) }}">{{ $cat->name }}</a>
                            <ul class="dropdown-menu">
                                @foreach($cat->subcategories as $sub)
                                    <li><a class="dropdown-item" href="{{ route('subcategory.show', [$cat->slug, $sub->slug]) }}">{{ $sub->name }}</a></li>
                                @endforeach
                            </ul>
                        </li>
                    @else
                        <li class="nav-item"><a class="nav-link {{ request()->route('slug') === $cat->slug ? 'active' : '' }}" href="{{ route('category.show', $cat->slug) }}">{{ $cat->name }}</a></li>
                    @endif
                @endforeach
                @if($menuBrands->isNotEmpty())
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('brand.show') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Brands</a>
                        <ul class="dropdown-menu">
                            @foreach($menuBrands as $b)
                                <li><a class="dropdown-item d-flex justify-content-between gap-3" href="{{ route('brand.show', $b->slug) }}">{{ $b->name }}<span class="text-muted small">{{ $b->products_count }}</span></a></li>
                            @endforeach
                        </ul>
                    </li>
                @endif
                @if($hasBlog)
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('blog.*') ? 'active' : '' }}" href="{{ route('blog.index') }}">Blog</a></li>
                @endif
            </ul>
        </div>
    </nav>
</header>

{{-- Mobile menu --}}
<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileMenu" aria-labelledby="mobileMenuLabel" style="width:300px">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold" id="mobileMenuLabel">{{ $siteName }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
        <div class="list-group list-group-flush">
            <a href="{{ route('home') }}" class="list-group-item list-group-item-action py-3"><i class="bi bi-house me-2"></i>Home</a>
            <a href="{{ route('products.index') }}" class="list-group-item list-group-item-action py-3"><i class="bi bi-grid me-2"></i>All Products</a>
            @foreach($menuCategories as $cat)
                <div class="list-group-item py-0 px-0">
                    <div class="d-flex align-items-center">
                        <a href="{{ route('category.show', $cat->slug) }}" class="flex-grow-1 py-3 px-3 text-dark">{{ $cat->name }}</a>
                        @if($cat->subcategories->isNotEmpty())
                            <button class="btn btn-link text-muted px-3" data-bs-toggle="collapse" data-bs-target="#mm-{{ $cat->id }}" aria-label="Show subcategories"><i class="bi bi-chevron-down"></i></button>
                        @endif
                    </div>
                    @if($cat->subcategories->isNotEmpty())
                        <div class="collapse" id="mm-{{ $cat->id }}">
                            @foreach($cat->subcategories as $sub)
                                <a href="{{ route('subcategory.show', [$cat->slug, $sub->slug]) }}" class="d-block py-2 ps-4 text-muted small">{{ $sub->name }}</a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
            <a href="{{ route('track') }}" class="list-group-item list-group-item-action py-3"><i class="bi bi-truck me-2"></i>Track Order</a>
            @if($hasBlog)<a href="{{ route('blog.index') }}" class="list-group-item list-group-item-action py-3"><i class="bi bi-journal-text me-2"></i>Blog</a>@endif
        </div>
    </div>
</div>
