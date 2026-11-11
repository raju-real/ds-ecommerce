{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
{{-- Built by App\Support\Sitemap (same list as the summary in Admin → Settings → SEO). --}}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
@foreach($groups as $urls)
@foreach($urls as $u)
    <url>
        <loc>{{ $u['loc'] }}</loc>
@if($u['lastmod'])
        <lastmod>{{ $u['lastmod']->toAtomString() }}</lastmod>
@endif
@foreach($u['images'] ?? [] as $img)
        <image:image><image:loc>{{ $img['loc'] }}</image:loc></image:image>
@endforeach
    </url>
@endforeach
@endforeach
</urlset>
