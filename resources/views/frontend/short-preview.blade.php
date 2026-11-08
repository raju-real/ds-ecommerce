{{-- What Facebook, WhatsApp, Messenger… show for a short link: title, description and image of the target. --}}
@extends('layouts.frontend')

@section('title', $target['title'])
@section('description', \Illuminate\Support\Str::limit($target['description'], 200))
@section('og_type', $type === 'blog' ? 'article' : 'product')
@section('og_image', $target['image'])
@section('canonical', $shortUrl)
@section('robots', 'noindex, follow')

@push('head')
    @if($target['price'])
        <meta property="product:price:amount" content="{{ $target['price'] }}">
        <meta property="product:price:currency" content="BDT">
    @endif
@endpush

@section('content')
    <div class="container py-5">
        <div class="card border-0 shadow-sm mx-auto" style="max-width: 520px; border-radius: 18px">
            <div class="card-body p-4 text-center">
                <img src="{{ $target['image'] }}" alt="{{ $target['title'] }}" class="rounded-4 mb-3" style="max-width: 100%; max-height: 300px; object-fit: contain">
                <h1 class="h4 fw-bold">{{ $target['title'] }}</h1>
                <p class="text-muted">{{ \Illuminate\Support\Str::limit($target['description'], 160) }}</p>
                <a href="{{ $target['url'] }}" class="btn btn-accent w-100 py-3 fs-6">
                    @if($type === 'blog')<i class="bi bi-journal-text me-1"></i> Read the article
                    @elseif($type === 'buy')<i class="bi bi-lightning-charge-fill me-1"></i> Order Now — Cash on Delivery
                    @else<i class="bi bi-bag me-1"></i> View product @endif
                </a>
            </div>
        </div>
    </div>
@endsection
