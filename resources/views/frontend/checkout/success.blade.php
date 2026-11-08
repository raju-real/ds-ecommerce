@extends('layouts.frontend')

@section('title', 'Order confirmed')
@section('robots', 'noindex, nofollow')

@if(setting('facebook_pixel_id'))
    @push('scripts')
        <script>if (window.fbq) fbq('track', 'Purchase', { value: {{ $order->total }}, currency: 'BDT' });</script>
    @endpush
@endif

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="checkout-card p-4 p-md-5">
                    <div class="text-center mb-4">
                        <div class="icon-circle bg-success-subtle text-success mb-3"><i class="bi bi-check2-circle"></i></div>
                        <h1 class="h3 fw-bold mb-2">Thank you, {{ $order->customer_name }}!</h1>
                        <p class="text-muted mb-0">Your order has been placed and verified. We'll call you shortly to confirm delivery.</p>
                    </div>

                    <div class="bg-light rounded-3 p-3 d-flex flex-wrap justify-content-between gap-3 mb-4">
                        <div><div class="small text-muted">Order number</div><div class="fw-bold fs-5">#{{ $order->order_number }}</div></div>
                        <div><div class="small text-muted">Payment</div><div class="fw-semibold">Cash on Delivery</div></div>
                        <div><div class="small text-muted">Total</div><div class="fw-bold fs-5 text-primary">{{ money($order->total) }}</div></div>
                    </div>

                    @foreach($order->items as $item)
                        <div class="cart-line">
                            <img src="{{ img_url($item->thumbnail) }}" alt="{{ $item->product_name }}" width="64" height="64">
                            <div class="flex-grow-1"><div class="name">{{ $item->product_name }}</div><div class="small text-muted">{{ $item->quantity }} × {{ money($item->unit_price) }}</div></div>
                            <strong>{{ money($item->line_total) }}</strong>
                        </div>
                    @endforeach

                    <div class="mt-3">
                        <div class="summary-row"><span class="text-muted">Subtotal</span><span>{{ money($order->subtotal) }}</span></div>
                        <div class="summary-row"><span class="text-muted">Delivery charge</span><span>{{ money($order->delivery_charge) }}</span></div>
                        @if($order->coupon_discount > 0)<div class="summary-row"><span class="text-muted">Coupon ({{ $order->coupon_code }})</span><span class="text-success">-{{ money($order->coupon_discount) }}</span></div>@endif
                        <div class="summary-row total"><span>Total</span><span>{{ money($order->total) }}</span></div>
                    </div>

                    <div class="small text-muted mt-3"><i class="bi bi-geo-alt me-1"></i>{{ $order->full_address }} · {{ $order->mobile }}</div>

                    <div class="d-flex flex-column flex-sm-row gap-2 mt-4">
                        <a href="{{ route('track', ['order' => $order->order_number, 'mobile' => $order->mobile]) }}" class="btn btn-outline-primary flex-fill"><i class="bi bi-truck me-1"></i> Track this order</a>
                        <a href="{{ route('products.index') }}" class="btn btn-primary flex-fill">Continue shopping</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
