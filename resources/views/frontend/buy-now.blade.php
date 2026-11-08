{{-- Link-preview page for /buy/{slug}: what Facebook, WhatsApp, Messenger etc. show for a shared direct-order link. --}}
@extends('layouts.frontend')

@php
    $price = money($product->selling_price);
    $was = $product->hasDiscount() ? ' (was '.money($product->unit_price).')' : '';
@endphp
@section('title', 'Order now: '.$product->name.' — '.$price)
@section('description', $price.$was.' · Cash on delivery all over Bangladesh. '.($product->short_description ?: strip_tags((string) $product->description)))
@section('og_type', 'product')
@section('og_image', $product->image_url)
@section('canonical', route('buy-now', $product->slug))
@section('robots', 'noindex, follow')

@push('head')
    <meta property="product:price:amount" content="{{ $product->selling_price }}">
    <meta property="product:price:currency" content="BDT">
    <meta property="og:image:width" content="800">
    <meta property="og:image:height" content="800">
@endpush

@section('content')
    <div class="container py-5">
        <div class="card border-0 shadow-sm mx-auto" style="max-width: 520px; border-radius: 18px">
            <div class="card-body p-4 text-center">
                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" width="280" height="280" class="rounded-4 mb-3" style="object-fit: contain; max-width: 100%">
                <h1 class="h4 fw-bold">{{ $product->name }}</h1>
                <div class="fs-4 fw-bold mb-3" style="color: var(--accent-600)">{{ $price }} @if($product->hasDiscount())<del class="fs-6 text-muted fw-normal">{{ money($product->unit_price) }}</del>@endif</div>
                <a href="{{ route('buy-now', $product->slug) }}" class="btn btn-accent w-100 py-3 fs-6"><i class="bi bi-lightning-charge-fill me-1"></i> Order Now — Cash on Delivery</a>
                <a href="{{ $product->url }}" class="d-inline-block mt-3 small">View product details</a>
            </div>
        </div>
    </div>
@endsection
