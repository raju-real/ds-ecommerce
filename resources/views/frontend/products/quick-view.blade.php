@include('frontend.products.partials.buy-box', ['uid' => 'qv'])
<div class="text-center mt-3">
    <a href="{{ $product->url }}" class="small fw-semibold">View full details <i class="bi bi-arrow-right"></i></a>
</div>
@if(! empty($fbEvents))<script>window.fbPixelFire && window.fbPixelFire(@json($fbEvents));</script>@endif
