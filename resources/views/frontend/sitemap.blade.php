{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url><loc>{{ route('home') }}</loc><changefreq>daily</changefreq><priority>1.0</priority></url>
    <url><loc>{{ route('products.index') }}</loc><changefreq>daily</changefreq><priority>0.9</priority></url>
@foreach($categories as $cat)
    <url><loc>{{ route('category.show', $cat->slug) }}</loc><lastmod>{{ $cat->updated_at->toAtomString() }}</lastmod><changefreq>weekly</changefreq><priority>0.8</priority></url>
@foreach($cat->subcategories as $sub)
    <url><loc>{{ route('subcategory.show', [$cat->slug, $sub->slug]) }}</loc><changefreq>weekly</changefreq><priority>0.7</priority></url>
@endforeach
@endforeach
@foreach($brands as $b)
    <url><loc>{{ route('brand.show', $b->slug) }}</loc><changefreq>weekly</changefreq><priority>0.6</priority></url>
@endforeach
@foreach($products as $p)
    <url><loc>{{ route('product.show', $p->slug) }}</loc><lastmod>{{ $p->updated_at->toAtomString() }}</lastmod><changefreq>weekly</changefreq><priority>0.8</priority></url>
@endforeach
@if($blogs->isNotEmpty())
    <url><loc>{{ route('blog.index') }}</loc><lastmod>{{ $blogs->max('updated_at')->toAtomString() }}</lastmod><changefreq>weekly</changefreq><priority>0.6</priority></url>
@foreach($blogs as $post)
    <url><loc>{{ $post->url }}</loc><lastmod>{{ $post->updated_at->toAtomString() }}</lastmod><changefreq>monthly</changefreq><priority>0.6</priority></url>
@endforeach
@endif
    <url><loc>{{ route('track') }}</loc><changefreq>monthly</changefreq><priority>0.3</priority></url>
</urlset>
