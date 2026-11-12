<footer class="site-footer">
    <div class="container py-5">
        {{-- With pages (Admin → Website → Pages) the footer gets an "Information" column; widths make room for it. --}}
        @php($hasPages = isset($footerPages) && $footerPages->isNotEmpty())
        <div class="row g-4">
            <div class="{{ $hasPages ? 'col-lg-3' : 'col-lg-4' }}">
                <a href="{{ route('home') }}" class="footer-logo d-inline-block mb-3">
                    @if(setting('logo_light'))
                        <img src="{{ img_url(setting('logo_light')) }}" alt="{{ $siteName }}" height="42" loading="lazy">
                    @else
                        <span class="fs-4 fw-bold text-white">{{ $siteName }}</span>
                    @endif
                </a>
                <p class="mb-3">{{ setting('footer_about', setting('tagline')) }}</p>
                <div class="social d-flex gap-2">
                    @foreach(['facebook' => 'facebook', 'instagram' => 'instagram', 'youtube' => 'youtube', 'tiktok' => 'tiktok', 'messenger' => 'messenger'] as $key => $icon)
                        @if(setting($key))<a href="{{ setting($key) }}" target="_blank" rel="noopener" aria-label="{{ ucfirst($key) }}"><i class="bi bi-{{ $icon }}"></i></a>@endif
                    @endforeach
                    @if($whatsapp)<a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>@endif
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <h6>Categories</h6>
                <ul class="list-unstyled links">
                    @foreach($menuCategories->take(6) as $cat)
                        <li><a href="{{ route('category.show', $cat->slug) }}">{{ $cat->name }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <h6>Quick Links</h6>
                <ul class="list-unstyled links">
                    <li><a href="{{ route('products.index') }}">All Products</a></li>
                    <li><a href="{{ route('products.index', ['sort' => 'discount']) }}">Hot Deals</a></li>
                    <li><a href="{{ route('track') }}">Track Order</a></li>
                    @if($hasBlog)<li><a href="{{ route('blog.index') }}">Blog</a></li>@endif
                    <li><a href="{{ route('checkout') }}">Checkout</a></li>
                </ul>
            </div>
            @if($hasPages)
                <div class="col-6 col-lg-2">
                    <h6>Information</h6>
                    <ul class="list-unstyled links">
                        @foreach($footerPages as $fp)
                            <li><a href="{{ $fp->url }}">{{ $fp->name }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div class="{{ $hasPages ? 'col-lg-3' : 'col-lg-4' }}">
                <h6>Contact Us</h6>
                <ul class="list-unstyled links">
                    @if(setting('address'))<li class="d-flex gap-2"><i class="bi bi-geo-alt"></i><span>{{ setting('address') }}</span></li>@endif
                    @if(setting('phone'))<li class="d-flex gap-2"><i class="bi bi-telephone"></i><a href="tel:{{ setting('phone') }}">{{ setting('phone') }}</a></li>@endif
                    @if($whatsapp)<li class="d-flex gap-2"><i class="bi bi-whatsapp"></i><a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener">{{ setting('whatsapp') }}</a></li>@endif
                    @if(setting('email'))<li class="d-flex gap-2"><i class="bi bi-envelope"></i><a href="mailto:{{ setting('email') }}">{{ setting('email') }}</a></li>@endif
                </ul>
                <div class="d-flex align-items-center gap-2 mt-3 small"><i class="bi bi-cash-coin fs-5 text-success"></i> Cash on Delivery all over Bangladesh</div>
            </div>
        </div>
    </div>
    <div class="bottom py-3">
        <div class="container d-flex flex-column flex-md-row justify-content-between gap-2">
            <span>{{ setting('copyright_text', '© '.date('Y').' '.$siteName.'. All rights reserved.') }}</span>
            <span>Secure checkout · Mobile verified orders</span>
        </div>
    </div>
</footer>
