{{-- A run of product cards (grid columns). Expects $products. --}}
@foreach($products as $product)
    @include('frontend.partials.product-card')
@endforeach
