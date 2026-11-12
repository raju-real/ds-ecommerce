@extends('layouts.frontend')

{{-- A page built in Admin → Website → Pages (About us, Privacy policy …). --}}
@php($description = $page->meta_description ?: $page->summary())
@section('title', $page->meta_title ?: $page->name)
@section('description', $description)
@if($page->share_image_url)
    @section('og_image', $page->share_image_url)
@endif
@if($page->meta_keywords)
    @section('keywords', $page->meta_keywords)
@endif
@section('canonical', $page->url)

@push('head')
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@graph' => [
                array_filter([
                    '@type' => 'WebPage',
                    'name' => $page->name,
                    'url' => $page->url,
                    'description' => $description,
                    'image' => $page->share_image_url,
                    'dateModified' => $page->updated_at?->toIso8601String(),
                    'isPartOf' => ['@type' => 'WebSite', 'name' => setting('site_name', config('app.name')), 'url' => route('home')],
                ]),
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => $page->name, 'item' => $page->url],
                    ],
                ],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
    <div class="container py-4">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item active text-truncate" aria-current="page" style="max-width: 60ch">{{ $page->name }}</li>
            </ol>
        </nav>

        <article class="blog-article cms-page mx-auto">
            <header class="mb-3">
                <h1 class="blog-title">{{ $page->name }}</h1>
            </header>
            @if($page->image_url)
                <img src="{{ $page->image_url }}" alt="{{ $page->name }}" width="1200" height="630" class="blog-cover" fetchpriority="high">
            @endif
            <div class="blog-content">
                {!! $page->content_html !!}
            </div>
        </article>
    </div>
@endsection
