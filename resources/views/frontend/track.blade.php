@extends('layouts.frontend')

@section('title', 'Track your order')
@section('description', 'Track the status of your order using your order number and mobile number.')
@if($searched)
    @section('robots', 'noindex, nofollow')
@endif

@php
    $steps = config('shop.public_statuses');
    $keys = array_keys($steps);
    $current = $order ? array_search($order->public_status, $keys, true) : false;
@endphp

@section('content')
    <div class="page-head">
        <div class="container">
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1"><li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li><li class="breadcrumb-item active">Track Order</li></ol></nav>
            <h1>Track your order</h1>
        </div>
    </div>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <form class="checkout-card mb-4" method="get" action="{{ route('track') }}" data-validate novalidate>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label for="order" class="form-label">Order number</label>
                            <input type="text" name="order" id="order" class="form-control @error('order') is-invalid @enderror" value="{{ request('order') }}" placeholder="e.g. NT-00012" required minlength="3" maxlength="30" pattern="\s*#?\s*[A-Za-z]{2}[\s\-]?[0-9]+\s*" data-msg-pattern="Enter the order number from your confirmation, e.g. NT-00012." autocomplete="off" autocapitalize="characters">
                            @error('order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="mobile" class="form-label">Mobile number</label>
                            <input type="tel" name="mobile" id="mobile" class="form-control @error('mobile') is-invalid @enderror" value="{{ request('mobile') }}" placeholder="01XXXXXXXXX" minlength="11" maxlength="11" pattern="01[3-9][0-9]{8}" inputmode="numeric" required data-digits data-msg-pattern="Enter the 11 digit mobile number used for the order.">
                            @error('mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3"><button class="btn btn-primary w-100"><i class="bi bi-search me-1"></i> Track</button></div>
                    </div>
                </form>

                @if($searched && !$errors->any())
                    @if(!$order)
                        <div class="card-plain empty-state">
                            <i class="bi bi-question-circle"></i>
                            <p class="mb-0">No order found with this order number and mobile. Please check and try again.</p>
                        </div>
                    @else
                        <div class="checkout-card">
                            <div class="d-flex flex-wrap justify-content-between gap-2">
                                <div>
                                    <div class="small text-muted">Order</div>
                                    <div class="fw-bold fs-5">#{{ $order->order_number }}</div>
                                </div>
                                <div class="text-md-end">
                                    <div class="small text-muted">Placed on</div>
                                    <div class="fw-semibold">{{ $order->created_at->format('d M Y, h:i A') }}</div>
                                </div>
                            </div>

                            @if($order->public_status === 'cancelled')
                                <div class="alert alert-danger mt-4 mb-0"><i class="bi bi-x-circle me-1"></i> This order has been {{ strtolower($order->status_label) }}. Please contact us if you have any questions.</div>
                            @else
                                <div class="track-steps">
                                    <div class="progress-line" style="width: {{ $current === false ? 0 : $current / (count($keys) - 1) * 76 }}%"></div>
                                    @foreach($steps as $key => $step)
                                        @php($idx = $loop->index)
                                        <div class="step {{ $current !== false && $idx <= $current ? 'done' : '' }} {{ $idx === $current ? 'current' : '' }}">
                                            <div class="dot"><i class="bi {{ $current !== false && $idx < $current ? 'bi-check-lg' : $step['icon'] }}"></i></div>
                                            <div class="label">{{ $step['label'] }}</div>
                                            <div class="text">{{ $step['text'] }}</div>
                                        </div>
                                    @endforeach
                                </div>
                                @if($order->status === 'delivered')
                                    <div class="alert alert-success mb-0"><i class="bi bi-box-seam me-1"></i> Your order has been delivered. Thank you for shopping with us!</div>
                                @endif
                            @endif

                            <hr>
                            @foreach($order->items as $item)
                                <div class="cart-line">
                                    <img src="{{ img_url($item->thumbnail) }}" alt="{{ $item->product_name }}" width="64" height="64">
                                    <div class="flex-grow-1"><div class="name">{{ $item->product_name }}</div><div class="small text-muted">Qty: {{ $item->quantity }}</div></div>
                                    <strong>{{ money($item->line_total) }}</strong>
                                </div>
                            @endforeach
                            <div class="summary-row"><span class="text-muted">Delivery charge</span><span>{{ money($order->delivery_charge) }}</span></div>
                            @if($order->coupon_discount > 0)<div class="summary-row"><span class="text-muted">Coupon ({{ $order->coupon_code }})</span><span class="text-success">-{{ money($order->coupon_discount) }}</span></div>@endif
                            @if($order->discount > 0)<div class="summary-row"><span class="text-muted">Extra discount</span><span>-{{ money($order->discount) }}</span></div>@endif
                            <div class="summary-row total"><span>Total (Cash on Delivery)</span><span>{{ money($order->total) }}</span></div>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
@endsection
