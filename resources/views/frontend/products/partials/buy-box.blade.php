{{-- Shared by the product page and the quick view modal. Expects $product and $uid. --}}
@php($images = $product->gallery())
<div class="row g-4">
    <div class="col-md-6">
        <div data-gallery>
            <div class="gallery-main">
                <img src="{{ img_url($images->first()->path) }}" alt="{{ $product->name }}" width="800" height="800" fetchpriority="high">
                @if($product->hasDiscount())<span class="badge-sale position-absolute top-0 start-0 m-3">-{{ $product->discount_percent }}%</span>@endif
            </div>
            @if($images->count() > 1)
                <div class="gallery-thumbs">
                    @foreach($images as $i => $img)
                        <button type="button" class="{{ $i === 0 ? 'active' : '' }}" data-src="{{ img_url($img->path) }}" aria-label="Image {{ $i + 1 }}">
                            <img src="{{ img_url($img->thumb) }}" alt="" width="72" height="72" loading="lazy">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    <div class="col-md-6">
        @if($product->category)
            <a href="{{ route('category.show', $product->category->slug) }}" class="small text-uppercase fw-semibold">{{ $product->category->name }}</a>
        @endif
        @if($uid === 'qv')
            <h2 class="pd-title mt-1 mb-2"><a href="{{ $product->url }}" class="text-dark">{{ $product->name }}</a></h2>
        @else
            <h1 class="pd-title mt-1 mb-2">{{ $product->name }}</h1>
        @endif

        <div class="d-flex flex-wrap gap-3 small text-muted mb-3">
            @if($product->brand)
                <span>Brand: <a href="{{ route('brand.show', $product->brand->slug) }}" class="fw-semibold">{{ $product->brand->name }}</a></span>
            @endif
            @if($product->in_stock)
                <span class="text-success fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>In stock</span>
            @else
                <span class="text-danger fw-semibold"><i class="bi bi-x-circle-fill me-1"></i>Out of stock</span>
            @endif
        </div>

        <div class="price pd-price mb-3">
            <span class="now">{{ money($product->selling_price) }}</span>
            @if($product->hasDiscount())
                <span class="old fs-6">{{ money($product->unit_price) }}</span>
                <span class="badge text-bg-success">Save {{ money($product->unit_price - $product->discount_price) }}</span>
            @endif
        </div>

        @if($product->short_description)
            <p class="text-muted mb-4">{{ $product->short_description }}</p>
        @endif

        @if($product->in_stock)
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <div class="qty">
                    <button type="button" data-step="-1" aria-label="Decrease quantity">−</button>
                    <input type="number" id="qty-{{ $uid }}" value="1" min="1" max="{{ \App\Services\CartService::MAX_QTY }}" placeholder="1" aria-label="Quantity">
                    <button type="button" data-step="1" aria-label="Increase quantity">+</button>
                </div>
                <button type="button" class="btn btn-outline-primary flex-grow-1 py-2" data-add-to-cart="{{ $product->id }}" data-qty-from="#qty-{{ $uid }}"><i class="bi bi-bag-plus me-1"></i> Add to Cart</button>
            </div>
            <button type="button" class="btn btn-accent w-100 py-3 fs-6 mb-2" data-add-to-cart="{{ $product->id }}" data-qty-from="#qty-{{ $uid }}" data-buy-now><i class="bi bi-lightning-charge-fill me-1"></i> Order Now — Cash on Delivery</button>
        @else
            <button class="btn btn-light w-100 py-3 mb-2" disabled>Currently out of stock</button>
        @endif

        @if(setting('whatsapp') || setting('phone'))
            <div class="d-flex gap-2 mb-4">
                @if(setting('whatsapp'))
                    @php($wa = preg_replace('/^01/', '8801', preg_replace('/\D/', '', setting('whatsapp'))))
                    <a href="https://wa.me/{{ $wa }}?text={{ urlencode('I want to order: '.$product->name.' ('.$product->code.') '.$product->url) }}" target="_blank" rel="noopener" class="btn btn-whatsapp flex-fill"><i class="bi bi-whatsapp me-1"></i> WhatsApp Order</a>
                @endif
                @if(setting('phone'))
                    <a href="tel:{{ setting('phone') }}" class="btn btn-soft flex-fill"><i class="bi bi-telephone me-1"></i> {{ setting('phone') }}</a>
                @endif
            </div>
        @endif

        <ul class="trust-list">
            <li><i class="bi bi-patch-check-fill"></i> 100% quality checked</li>
            <li><i class="bi bi-cash-coin"></i> Cash on delivery</li>
            <li><i class="bi bi-truck"></i> Delivery in 2–5 days</li>
            <li><i class="bi bi-box-seam"></i> Check before you pay</li>
        </ul>
    </div>
</div>
