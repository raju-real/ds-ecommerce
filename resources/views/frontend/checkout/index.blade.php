@extends('layouts.frontend')

@section('title', 'Checkout')
@section('robots', 'noindex, nofollow')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endpush

@section('content')
    <div class="page-head">
        <div class="container">
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1"><li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li><li class="breadcrumb-item active">Checkout</li></ol></nav>
            <h1>Checkout</h1>
        </div>
    </div>

    <div class="container py-4">
        @if($items->isEmpty())
            <div class="card-plain empty-state">
                <i class="bi bi-bag-x"></i>
                <h5 class="text-dark fw-bold">Your cart is empty</h5>
                <p>Add some products and come back to place your order.</p>
                <a href="{{ route('products.index') }}" class="btn btn-primary">Continue shopping</a>
            </div>
        @else
            <form id="checkoutForm" method="post" action="{{ route('checkout.store') }}" data-subtotal="{{ $items->sum('total') }}" data-validate novalidate>
                @csrf
                <div class="row g-4">
                    <div class="col-lg-7">
                        <div class="checkout-card mb-4">
                            <h5><span class="step-num">1</span> Delivery information</h5>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="customer_name" class="form-label">Full name <span class="text-danger">*</span></label>
                                    <input type="text" name="customer_name" id="customer_name" class="form-control @error('customer_name') is-invalid @enderror" value="{{ old('customer_name') }}" placeholder="Your full name" autocomplete="name" required minlength="3" maxlength="120" data-msg-required="Please enter your name.">
                                    @error('customer_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="mobile" class="form-label">Mobile number <span class="text-danger">*</span></label>
                                    <input type="tel" name="mobile" id="mobile" class="form-control @error('mobile') is-invalid @enderror" value="{{ old('mobile') }}" placeholder="01XXXXXXXXX" minlength="11" maxlength="11" pattern="01[3-9][0-9]{8}" inputmode="numeric" autocomplete="tel" required data-digits data-msg-pattern="Please enter a valid 11 digit mobile number (01XXXXXXXXX)." data-msg-required="Please enter your mobile number.">
                                    @error('mobile')<div class="invalid-feedback">{{ $message }}</div>@else<div class="form-text">We'll send a verification code to this number.</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="district_id" class="form-label">District <span class="text-danger">*</span></label>
                                    <select name="district_id" id="district_id" class="form-select @error('district_id') is-invalid @enderror" required data-msg-required="Please select your district.">
                                        <option></option>
                                        @foreach($districts as $d)
                                            <option value="{{ $d->id }}" @selected(old('district_id') == $d->id)>{{ $d->name }}{{ $d->bn_name ? ' ('.$d->bn_name.')' : '' }}</option>
                                        @endforeach
                                    </select>
                                    @error('district_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="thana_id" class="form-label">Thana / Upazila <span class="text-danger">*</span></label>
                                    <select name="thana_id" id="thana_id" class="form-select @error('thana_id') is-invalid @enderror" data-old="{{ old('thana_id') }}" required data-msg-required="Please select your thana / upazila.">
                                        <option></option>
                                    </select>
                                    @error('thana_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label for="address" class="form-label">Full address <span class="text-danger">*</span></label>
                                    <textarea name="address" id="address" rows="2" class="form-control @error('address') is-invalid @enderror" placeholder="House no, road, area, landmark" autocomplete="street-address" required minlength="10" maxlength="500" data-msg-required="Please enter your full address.">{{ old('address') }}</textarea>
                                    @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label for="note" class="form-label">Order note <span class="text-muted fw-normal">(optional)</span></label>
                                    <input type="text" name="note" id="note" class="form-control @error('note') is-invalid @enderror" value="{{ old('note') }}" placeholder="Size, color or delivery instructions" maxlength="500">
                                    @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="checkout-card">
                            <h5><span class="step-num">2</span> Payment method</h5>
                            <div class="cod-box">
                                <i class="bi bi-cash-stack fs-3 text-primary"></i>
                                <div>
                                    <div class="fw-bold">Cash on Delivery</div>
                                    <div class="small text-muted">Pay with cash after receiving and checking your product.</div>
                                </div>
                                <i class="bi bi-check-circle-fill text-primary fs-4 ms-auto"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="checkout-card position-sticky" style="top: 140px">
                            <h5><span class="step-num">3</span> Order summary</h5>

                            <div class="mb-2">
                                @foreach($items as $line)
                                    <div class="cart-line" data-id="{{ $line->product->id }}">
                                        <img src="{{ $line->product->thumb_url }}" alt="{{ $line->product->name }}" width="64" height="64">
                                        <div class="flex-grow-1 min-w-0">
                                            <a href="{{ $line->product->url }}" class="name d-block text-truncate">{{ $line->product->name }}</a>
                                            <div class="small text-muted mb-1">{{ money($line->price) }} each</div>
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="qty qty-sm">
                                                    <button type="button" data-step="-1" aria-label="Decrease">−</button>
                                                    <input type="number" value="{{ $line->qty }}" min="1" max="{{ \App\Services\CartService::MAX_QTY }}" placeholder="1" aria-label="Quantity">
                                                    <button type="button" data-step="1" aria-label="Increase">+</button>
                                                </div>
                                                <strong data-line-total>{{ money($line->total) }}</strong>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-link text-muted p-1" data-checkout-remove aria-label="Remove"><i class="bi bi-x-lg"></i></button>
                                    </div>
                                @endforeach
                            </div>

                            <div class="summary-row"><span class="text-muted">Subtotal</span><span id="sumSubtotal">{{ money($items->sum('total')) }}</span></div>
                            <div class="summary-row"><span class="text-muted">Delivery charge</span><span id="sumDelivery">Select district</span></div>
                            <div class="summary-row coupon-row d-none" id="couponRow"><span class="text-success"><i class="bi bi-ticket-perforated me-1"></i>Coupon <strong data-coupon-code></strong> <button type="button" class="btn btn-link btn-sm p-0 ms-1 text-danger align-baseline" data-coupon-remove>Remove</button></span><span class="text-success" id="sumCoupon"></span></div>
                            <div class="summary-row total"><span>Total</span><span id="sumTotal" class="text-primary">{{ money($items->sum('total')) }}</span></div>

                            {{-- Coupon (checked against the mobile number entered above) --}}
                            <div class="coupon-box mt-3" id="couponBox">
                                <label for="coupon_input" class="form-label small fw-semibold mb-1"><i class="bi bi-ticket-perforated me-1"></i>Have a coupon?</label>
                                <div class="input-group">
                                    <input type="text" id="coupon_input" class="form-control text-uppercase @error('coupon_code') is-invalid @enderror" value="{{ old('coupon_code') }}" placeholder="Enter coupon code" maxlength="30" autocomplete="off" aria-label="Coupon code" data-no-validate>
                                    <button type="button" class="btn btn-outline-primary" data-coupon-apply>Apply</button>
                                </div>
                                <div class="coupon-msg small mt-1 {{ $errors->has('coupon_code') ? 'text-danger' : '' }}" id="couponMsg" role="status" aria-live="polite">@error('coupon_code'){{ $message }}@enderror</div>
                                <input type="hidden" name="coupon_code" id="coupon_code" value="">
                            </div>

                            <div class="district-notice" id="districtNotice" role="alert"><i class="bi bi-geo-alt me-1"></i>Please select district first</div>
                            <button type="submit" class="btn btn-accent w-100 py-3 mt-2 fs-6" id="placeOrderBtn" disabled>
                                <i class="bi bi-shield-check me-1"></i> <span class="label">Confirm Order</span> · <span id="placeOrderTotal">{{ money($items->sum('total')) }}</span>
                            </button>
                            <p class="small text-muted text-center mt-2 mb-0"><i class="bi bi-lock"></i> Your mobile number will be verified with a one-time code.</p>
                        </div>
                    </div>
                </div>
            </form>
        @endif
    </div>
@endsection
