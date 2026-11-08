@extends('layouts.frontend')

@php($page = $posts->currentPage())
@section('title', 'Blog'.($page > 1 ? ' - Page '.$page : ''))
@section('description', 'Tips, guides and news from '.setting('site_name', config('app.name')).'. Read our latest articles.')
@section('canonical', $page > 1 ? $posts->url($page) : route('blog.index'))

@push('head')
    @if($posts->previousPageUrl())<link rel="prev" href="{{ $posts->previousPageUrl() }}">@endif
    @if($posts->nextPageUrl())<link rel="next" href="{{ $posts->nextPageUrl() }}">@endif
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => route('blog.index')],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
    <div class="page-head">
        <div class="container">
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1"><li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li><li class="breadcrumb-item active" aria-current="page">Blog</li></ol></nav>
            <h1>Blog</h1>
        </div>
    </div>

    <div class="container py-4">
        @if($posts->isEmpty())
            <div class="card-plain empty-state"><i class="bi bi-journal-text"></i>No posts yet. Please check back soon.</div>
        @else
            <div class="row g-4 row-cols-1 row-cols-md-2 row-cols-lg-3">
                @foreach($posts as $post)
                    <div class="col">@include('frontend.blog.partials.card', ['headingTag' => 'h2'])</div>
                @endforeach
            </div>
            @if($posts->hasPages())<div class="d-flex justify-content-center mt-4">{{ $posts->links() }}</div>@endif
        @endif
    </div>
@endsection
