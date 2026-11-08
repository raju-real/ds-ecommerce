{{-- Expects $product (card columns only; drop shipping price is never selected for cards). --}}
<div class="col">
    <article class="product-card">
        <div class="badges">
            @if($product->hasDiscount())<span class="badge-sale">-{{ $product->discount_percent }}%</span>@endif
            @if($product->is_featured)<span class="badge-hot">HOT</span>@endif
            @unless($product->in_stock)<span class="badge-out">Out of stock</span>@endunless
        </div>
        <div class="actions">
            <button type="button" data-quick-view="{{ route('product.quick-view', $product->slug) }}" title="Quick view" aria-label="Quick view {{ $product->name }}"><i class="bi bi-eye"></i></button>
            @if($product->in_stock)
                <button type="button" data-add-to-cart="{{ $product->id }}" title="Add to cart" aria-label="Add {{ $product->name }} to cart"><i class="bi bi-bag-plus"></i></button>
            @endif
        </div>
        <a href="{{ $product->url }}" class="thumb">
            <img src="{{ $product->thumb_url }}" alt="{{ $product->name }}" width="400" height="400" loading="lazy" decoding="async">
        </a>
        <div class="body">
            <a href="{{ $product->url }}" class="title">{{ $product->name }}</a>
            <div class="price">
                <span class="now">{{ money($product->selling_price) }}</span>
                @if($product->hasDiscount())<span class="old">{{ money($product->unit_price) }}</span>@endif
            </div>
            @if($product->in_stock)
                <button type="button" class="btn btn-accent btn-order" data-add-to-cart="{{ $product->id }}" data-buy-now><i class="bi bi-lightning-charge-fill me-1"></i>Order Now</button>
            @else
                <button type="button" class="btn btn-light btn-order" disabled>Out of Stock</button>
            @endif
        </div>
    </article>
</div>
