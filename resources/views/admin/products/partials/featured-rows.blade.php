{{-- Featured products table body. Expects $featured (arrays from ProductRepository::adminFeatured). --}}
@forelse($featured as $p)
    <tr data-uid="{{ $p['uid'] }}" data-search="{{ $p['haystack'] }}">
        <td>
            <div class="d-flex align-items-center gap-3">
                <img src="{{ img_url($p['thumbnail']) }}" class="thumb" alt="" loading="lazy">
                <div class="min-w-0">
                    <a href="{{ route('admin.products.edit', $p['uid']) }}" class="title d-block text-truncate fp-title">{{ $p['name'] }}</a>
                    <div class="small text-muted">{{ $p['code'] }}</div>
                </div>
            </div>
        </td>
        <td class="small">
            <div title="Category"><i class="bi bi-collection text-muted me-1"></i>{{ $p['category'] ?? '—' }}@if($p['subcategory'])<span class="text-muted"> › {{ $p['subcategory'] }}</span>@endif</div>
            <div title="Vendor" class="mt-1"><i class="bi bi-shop text-muted me-1"></i>{{ $p['vendor'] ?? 'No vendor' }}@if($p['brand'])<span class="text-muted"> · {{ $p['brand'] }}</span>@endif</div>
        </td>
        <td class="text-end text-nowrap">{{ money($p['unit_price']) }}</td>
        <td class="text-end text-nowrap">@if($p['discount_price'] !== null)<span class="fw-semibold text-success">{{ money($p['discount_price']) }}</span>@else<span class="text-muted">—</span>@endif</td>
        <td class="text-center"><span class="badge badge-soft-primary">{{ $p['orders'] }}</span></td>
        <td>
            @if(! $p['is_active'])
                <span class="badge badge-soft-secondary" title="Product is inactive, so it is not shown on the store">Hidden</span>
            @elseif($p['on_home'])
                <span class="badge badge-soft-success" title="Among the first {{ config('shop.home.featured') }} on the home page"><i class="bi bi-house-check me-1"></i>On home</span>
            @elseif($p['home_position'] <= config('shop.home.featured_max'))
                <span class="badge badge-soft-info" title="Position {{ $p['home_position'] }} — appears after clicking Load more on the home page"><i class="bi bi-arrow-down-circle me-1"></i>Load more</span>
            @else
                <span class="badge badge-soft-warning" title="Beyond the first {{ config('shop.home.featured_max') }} — only on the View all page">View all only</span>
            @endif
            @unless($p['in_stock'])<span class="badge badge-soft-danger ms-1">Out of stock</span>@endunless
        </td>
        <td class="text-end">
            <div class="row-actions">
                @if($p['is_active'])<a href="{{ route('product.show', $p['slug']) }}" target="_blank" class="btn btn-soft-secondary btn-icon" title="View on store"><i class="bi bi-box-arrow-up-right"></i></a>@endif
                <a href="{{ route('admin.products.edit', $p['uid']) }}" class="btn btn-soft-primary btn-icon" title="Edit product"><i class="bi bi-pencil"></i></a>
                <button type="button" class="btn btn-soft-danger btn-icon" title="Remove from featured" data-unfeature="{{ route('admin.products.featured.remove', $p['uid']) }}" data-name="{{ $p['name'] }}"><i class="bi bi-x-lg"></i></button>
            </div>
        </td>
    </tr>
@empty
    <tr class="empty-row"><td colspan="7"><div class="empty"><i class="bi bi-star"></i>No featured products yet. Search above and click <strong>Add</strong>.</div></td></tr>
@endforelse
