{{-- Share menu for a product: product page + direct order (checkout) links, each with a short link. Expects $product; optional $label. --}}
@php
    $warning = ! $product->is_active
        ? '<i class="bi bi-eye-slash me-1"></i>Product is inactive — its links open the shop until you activate it.'
        : (! $product->in_stock ? '<i class="bi bi-exclamation-triangle me-1"></i>Out of stock — the order link opens the product page instead.' : null);
@endphp
@include('admin.partials.share-menu', array_filter([
    'title' => $product->name,
    'image' => $product->thumb_url,
    'targets' => [
        ['type' => 'product', 'uid' => $product->unique_id, 'label' => 'Product details', 'url' => $product->url],
        ['type' => 'buy', 'uid' => $product->unique_id, 'label' => 'Direct order (opens checkout)', 'url' => route('buy-now', $product->slug)],
    ],
    'primary' => 'buy',
    'shareText' => $product->name.' — '.money($product->selling_price).' · Cash on delivery. Order now:',
    'warning' => $warning,
    'label' => $label ?? null,
]))
