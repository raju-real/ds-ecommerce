{{-- Blog post card. Expects $post (BlogRepository card columns); optional $headingTag (default h3). --}}
@php($headingTag = $headingTag ?? 'h3')
<article class="blog-card">
    <a href="{{ $post->url }}" class="blog-card-img" tabindex="-1" aria-hidden="true">
        <img src="{{ $post->thumb_url }}" alt="{{ $post->title }}" width="600" height="315" loading="lazy" decoding="async">
    </a>
    <div class="blog-card-body">
        <time datetime="{{ $post->published_at->toDateString() }}" class="blog-date"><i class="bi bi-calendar3 me-1"></i>{{ $post->published_at->format('d M Y') }}</time>
        <{{ $headingTag }} class="blog-card-title"><a href="{{ $post->url }}">{{ $post->title }}</a></{{ $headingTag }}>
        <p class="blog-card-excerpt">{{ \Illuminate\Support\Str::limit($post->excerpt, 130) }}</p>
        <a href="{{ $post->url }}" class="blog-read" aria-label="Read more: {{ $post->title }}">Read more <i class="bi bi-arrow-right"></i></a>
    </div>
</article>
