@extends('layouts.frontend')

@php
    $siteName = setting('site_name', config('app.name'));
    $description = $blog->meta_description ?: \Illuminate\Support\Str::limit($blog->excerpt, 158);
@endphp
@section('title', $blog->meta_title ?: $blog->title)
@section('description', $description)
@section('og_type', 'article')
@section('og_image', $blog->image_url)
@section('canonical', $blog->url)

@push('head')
    <meta property="article:published_time" content="{{ $blog->published_at->toIso8601String() }}">
    <meta property="article:modified_time" content="{{ $blog->updated_at->toIso8601String() }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'BlogPosting',
                    'headline' => \Illuminate\Support\Str::limit($blog->title, 110, ''),
                    'description' => $description,
                    'image' => [$blog->image_url],
                    'datePublished' => $blog->published_at->toIso8601String(),
                    'dateModified' => $blog->updated_at->toIso8601String(),
                    'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $blog->url],
                    'author' => ['@type' => 'Organization', 'name' => $siteName, 'url' => route('home')],
                    'publisher' => array_filter([
                        '@type' => 'Organization',
                        'name' => $siteName,
                        'logo' => setting('logo') ? ['@type' => 'ImageObject', 'url' => img_url(setting('logo'))] : null,
                    ]),
                ],
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => route('blog.index')],
                        ['@type' => 'ListItem', 'position' => 3, 'name' => $blog->title, 'item' => $blog->url],
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
                <li class="breadcrumb-item"><a href="{{ route('blog.index') }}">Blog</a></li>
                <li class="breadcrumb-item active text-truncate" aria-current="page" style="max-width: 60ch">{{ $blog->title }}</li>
            </ol>
        </nav>

        <div class="row g-4">
            <article class="col-lg-8">
                <div class="blog-article">
                    <header class="mb-3">
                        <h1 class="blog-title">{{ $blog->title }}</h1>
                        <div class="blog-meta">
                            <time datetime="{{ $blog->published_at->toDateString() }}"><i class="bi bi-calendar3 me-1"></i>{{ $blog->published_at->format('d F Y') }}</time>
                            <span><i class="bi bi-clock me-1"></i>{{ $blog->reading_minutes }} min read</span>
                        </div>
                    </header>
                    <img src="{{ $blog->image_url }}" alt="{{ $blog->title }}" width="1200" height="630" class="blog-cover" fetchpriority="high">
                    <div class="blog-content">
                        {!! $blog->content_html !!}
                    </div>
                    <div class="blog-share">
                        <span class="small fw-semibold text-muted">Share:</span>
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ rawurlencode($shareUrl) }}" target="_blank" rel="noopener" class="share-btn fb" aria-label="Share on Facebook"><i class="bi bi-facebook"></i></a>
                        <a href="https://wa.me/?text={{ rawurlencode($blog->title.' '.$shareUrl) }}" target="_blank" rel="noopener" class="share-btn wa" aria-label="Share on WhatsApp"><i class="bi bi-whatsapp"></i></a>
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ rawurlencode($shareUrl) }}" target="_blank" rel="noopener" class="share-btn in" aria-label="Share on LinkedIn"><i class="bi bi-linkedin"></i></a>
                        <button type="button" class="btn btn-sm btn-outline-secondary ms-auto rounded-pill" data-copy="{{ $shareUrl }}" title="Copy the link to this article"><i class="bi bi-link-45deg me-1"></i>Copy link</button>
                    </div>
                </div>
            </article>

            <aside class="col-lg-4" aria-label="More to read">
                <div class="blog-sidebar">
                    <h2 class="blog-side-title">More to read</h2>
                    @forelse($related as $post)
                        <a href="{{ $post->url }}" class="blog-side-item">
                            <img src="{{ $post->thumb_url }}" alt="" width="120" height="63" loading="lazy" decoding="async">
                            <span>
                                <span class="t">{{ $post->title }}</span>
                                <time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->format('d M Y') }}</time>
                            </span>
                        </a>
                    @empty
                        <p class="small text-muted mb-0">More posts are coming soon.</p>
                    @endforelse
                    <a href="{{ route('blog.index') }}" class="btn btn-outline-primary w-100 mt-3">All blog posts <i class="bi bi-arrow-right ms-1"></i></a>
                </div>
            </aside>
        </div>
    </div>
@endsection
