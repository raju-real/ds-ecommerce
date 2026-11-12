@php
    $siteName = setting('site_name', config('app.name'));
    // @section('title', $text) stores the text already HTML-escaped ("&" → "&amp;"), and the tags below escape
    // again when printing — which put "&amp;amp;" in titles. Read sections back as plain text first.
    // Texts: Seo::plain also turns "&nbsp;" etc. from rich product descriptions into plain text. Links: decoded once.
    $section = fn (string $name) => html_entity_decode(trim($__env->yieldContent($name)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = fn (string $name) => (string) \App\Support\Seo::plain($__env->yieldContent($name));
    $pageTitle = $text('title');
    $metaTitle = $pageTitle ? $pageTitle.' | '.$siteName : setting('meta_title', $siteName);
    $metaDescription = $text('description') ?: setting('meta_description');
    // Keywords: the page's own (product, category, brand, blog), else the site keywords from Settings → SEO.
    $metaKeywords = $text('keywords') ?: setting('meta_keywords');
    // Share image: the page's own, else the site share image, else the logo.
    $ogImage = $section('og_image') ?: collect([setting('og_image'), setting('logo')])->filter()->map(fn ($p) => img_url($p))->first();
    $canonical = $section('canonical') ?: url()->current();
    $whatsapp = setting('whatsapp') ? preg_replace('/\D/', '', setting('whatsapp')) : null;
    if ($whatsapp && str_starts_with($whatsapp, '01')) { $whatsapp = '88'.$whatsapp; }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $metaTitle }}</title>
    <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags($metaDescription), 160) }}">
    @if($metaKeywords)<meta name="keywords" content="{{ $metaKeywords }}">@endif
    {{-- max-image-preview:large lets Google show big product images in results; max-snippet:-1 = full description. --}}
    <meta name="robots" content="@yield('robots', 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1')">
    <link rel="canonical" href="{{ $canonical }}">
    <meta name="theme-color" content="#4f46e5">
    @if(request()->routeIs('home'))
        {{-- Ownership checks read the home page: Settings → SEO (Google Search Console) and Settings → Facebook Pixel. --}}
        @if(setting('google_site_verification'))<meta name="google-site-verification" content="{{ setting('google_site_verification') }}">@endif
        @if(setting('facebook_domain_verification'))<meta name="facebook-domain-verification" content="{{ setting('facebook_domain_verification') }}">@endif
    @endif

    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ \Illuminate\Support\Str::limit(strip_tags($metaDescription), 200) }}">
    <meta property="og:url" content="{{ $canonical }}">
    @if($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
        <meta property="og:image:alt" content="{{ $metaTitle }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ \Illuminate\Support\Str::limit(strip_tags($metaDescription), 200) }}">
    @if($ogImage)<meta name="twitter:image" content="{{ $ogImage }}">@endif

    {{-- Favicons for every device, all from Settings → Logo & branding:
         .ico (if uploaded) for /favicon.ico + shortcut icon; PNG sizes made from the favicon upload: 32 / 48 (tabs, Google
         needs a multiple of 48), 192 (Android, Google), 180 (Apple), 512 (installed web app, via /site.webmanifest).
         Sizes saved before this change fall back to the main favicon. --}}
    @php($fav = setting('favicon'))
    @if($fav || setting('favicon_ico'))
        @if(setting('favicon_ico'))
            <link rel="icon" href="{{ url('/favicon.ico') }}" sizes="any">
        @endif
        @if($fav)
            <link rel="icon" type="image/png" sizes="32x32" href="{{ img_url(setting('favicon_32') ?: $fav) }}">
            <link rel="icon" type="image/png" sizes="48x48" href="{{ img_url(setting('favicon_48') ?: $fav) }}">
            <link rel="icon" type="image/png" sizes="192x192" href="{{ img_url($fav) }}">
            <link rel="apple-touch-icon" sizes="180x180" href="{{ img_url(setting('favicon_180') ?: $fav) }}">
            <link rel="manifest" href="{{ url('/site.webmanifest') }}">
        @endif
        <link rel="shortcut icon" href="{{ url('/favicon.ico') }}">
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Hind+Siliguri:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    @stack('styles')
    <link href="{{ asset('css/shop.css') }}?v={{ filemtime(public_path('css/shop.css')) }}" rel="stylesheet">

    @stack('head')

    @if(setting('google_analytics_id'))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ setting('google_analytics_id') }}"></script>
        <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','{{ setting('google_analytics_id') }}');</script>
    @endif
    @include('frontend.partials.facebook-pixel')
</head>
<body>
    @include('frontend.partials.header', compact('siteName', 'whatsapp'))

    <main>
        @yield('content')
    </main>

    @include('frontend.partials.footer', compact('siteName', 'whatsapp'))

    {{-- Mini cart --}}
    <div class="offcanvas offcanvas-end offcanvas-cart" tabindex="-1" id="miniCart" aria-labelledby="miniCartLabel">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title fw-bold" id="miniCartLabel"><i class="bi bi-bag me-2"></i>Shopping Cart</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body" id="miniCartBody">
            <div class="skeleton mb-3" style="height:64px"></div><div class="skeleton" style="height:64px"></div>
        </div>
        <div class="border-top p-3 d-none" id="miniCartFooter">
            <div class="d-flex justify-content-between mb-3"><span class="text-muted">Subtotal</span><strong id="miniCartSubtotal"></strong></div>
            <a href="{{ route('checkout') }}" class="btn btn-accent w-100 py-2"><i class="bi bi-lightning-charge-fill me-1"></i> Checkout Now</a>
        </div>
    </div>

    {{-- Quick view --}}
    <div class="modal fade qv-modal" id="quickViewModal" tabindex="-1" aria-label="Quick view" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-body p-3 p-md-4"></div>
            </div>
        </div>
    </div>

    {{-- Mobile bottom navigation --}}
    <nav class="mobile-nav d-lg-none" aria-label="Mobile navigation">
        <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}"><i class="bi bi-house"></i>Home</a>
        <a href="{{ route('products.index') }}" class="{{ request()->routeIs('products.*', 'category.*', 'subcategory.*') ? 'active' : '' }}"><i class="bi bi-grid"></i>Shop</a>
        <button type="button" data-bs-toggle="offcanvas" data-bs-target="#miniCart"><i class="bi bi-bag"></i>Cart<span class="count {{ $cartCount ? '' : 'd-none' }}" data-cart-count>{{ $cartCount }}</span></button>
        <a href="{{ route('track') }}" class="{{ request()->routeIs('track') ? 'active' : '' }}"><i class="bi bi-truck"></i>Track</a>
        @if(setting('phone'))<a href="tel:{{ setting('phone') }}"><i class="bi bi-telephone"></i>Call</a>@endif
    </nav>

    @if($whatsapp)
        <a href="https://wa.me/{{ $whatsapp }}" class="float-whatsapp" target="_blank" rel="noopener" aria-label="Chat on WhatsApp"><i class="bi bi-whatsapp"></i></a>
    @endif

    <script>
        window.SHOP = {
            currency: @json(config('shop.currency')),
            cartUrl: @json(route('cart.summary')),
            cartAddUrl: @json(route('cart.add')),
            cartUpdateUrl: @json(route('cart.update')),
            cartRemoveUrl: @json(route('cart.remove')),
            productsUrl: @json(route('products.index')),
            thanasUrl: @json(route('locations.thanas', '__ID__')),
            couponUrl: @json(route('checkout.coupon')),
            suggestUrl: @json(route('search.suggest')),
            searchMin: {{ (int) config('shop.search.min_chars') }},
            flash: { success: @json(session('success')), error: @json(session('error')) }
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/ui.js') }}?v={{ filemtime(public_path('js/ui.js')) }}"></script>
    <script src="{{ asset('js/validate.js') }}?v={{ filemtime(public_path('js/validate.js')) }}"></script>
    @stack('scripts')
    <script src="{{ asset('js/shop.js') }}?v={{ filemtime(public_path('js/shop.js')) }}"></script>
</body>
</html>
