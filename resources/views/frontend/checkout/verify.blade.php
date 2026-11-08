@extends('layouts.frontend')

@section('title', 'Verify your mobile')
@section('robots', 'noindex, nofollow')

@php
    $wait = $order->otp_sent_at ? max(0, config('shop.otp.resend_after_seconds') - $order->otp_sent_at->diffInSeconds(now())) : 0;
    $masked = substr($order->mobile, 0, 3).'****'.substr($order->mobile, -4);
@endphp

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-5">
                <div class="checkout-card text-center p-4 p-md-5">
                    <div class="icon-circle bg-primary-subtle text-primary mb-3"><i class="bi bi-phone-vibrate"></i></div>
                    <h1 class="h4 fw-bold mb-2">Verify your mobile number</h1>
                    <p class="text-muted mb-4">We've sent a {{ config('shop.otp.length') }}-digit code to <strong class="text-dark">{{ $masked }}</strong> for order <strong class="text-dark">#{{ $order->order_number }}</strong>.</p>

                    @if(session('dev_otp'))
                        <div class="alert alert-warning small py-2"><i class="bi bi-bug me-1"></i> Dev mode (SMS driver = log): your code is <strong>{{ session('dev_otp') }}</strong></div>
                    @endif

                    <form method="post" action="{{ route('checkout.verify.submit', $order->order_number) }}" id="otpForm">
                        @csrf
                        <input type="hidden" name="otp" id="otpValue">
                        <div class="otp-inputs mb-3">
                            @for($i = 0; $i < config('shop.otp.length'); $i++)
                                <input type="text" inputmode="numeric" maxlength="1" placeholder="·" pattern="[0-9]" autocomplete="{{ $i === 0 ? 'one-time-code' : 'off' }}" aria-label="Digit {{ $i + 1 }}">
                            @endfor
                        </div>
                        @error('otp')<div class="text-danger small mb-3">{{ $message }}</div>@enderror
                        <button class="btn btn-primary w-100 py-2 mb-3">Verify & Confirm Order</button>
                    </form>

                    <form method="post" action="{{ route('checkout.resend', $order->order_number) }}">
                        @csrf
                        <button class="btn btn-link btn-sm text-decoration-none" id="resendBtn" data-wait="{{ $wait }}">Resend code</button>
                    </form>

                    <hr class="my-4">
                    <div class="text-start small">
                        <div class="d-flex justify-content-between mb-1"><span class="text-muted">Items</span><span>{{ $order->items->sum('quantity') }}</span></div>
                        <div class="d-flex justify-content-between mb-1"><span class="text-muted">Delivery</span><span>{{ money($order->delivery_charge) }}</span></div>
                        @if($order->coupon_discount > 0)<div class="d-flex justify-content-between mb-1"><span class="text-muted">Coupon ({{ $order->coupon_code }})</span><span class="text-success">-{{ money($order->coupon_discount) }}</span></div>@endif
                        <div class="d-flex justify-content-between fw-bold"><span>Total payable</span><span>{{ money($order->total) }}</span></div>
                    </div>
                </div>
                <p class="text-center small text-muted mt-3">Didn't get the code? Our team may call you to confirm your order.</p>
            </div>
        </div>
    </div>
@endsection
