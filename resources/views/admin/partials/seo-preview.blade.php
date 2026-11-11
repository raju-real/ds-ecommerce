{{-- Live search-result + share preview (inside the SEO card). Updated as you type by admin.js [data-seo-preview],
     using the same rules as the shop pages: title = meta title or name + " | site name", description = meta description
     or the page's own text, image = share image or main image.
     Expects $preview = ['path' => URL before the slug, 'name' => selector, 'desc' => [selectors, first filled wins],
                         'image' => main image <img> selector, 'type' => product|blog]. --}}
@php
    $siteName = setting('site_name', config('app.name'));
    $host = parse_url(url('/'), PHP_URL_HOST);
    $fav = setting('favicon') ? img_url(setting('favicon')) : null;
    $cfg = $preview + ['siteName' => $siteName, 'host' => $host];
@endphp
<div class="seo-preview mt-4" data-seo-preview='@json($cfg)'>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
        <div class="fw-semibold"><i class="bi bi-eye me-1"></i> Preview</div>
        <div class="btn-group btn-group-sm" role="tablist">
            <button type="button" class="btn btn-outline-secondary active" data-seo-tab="google"><i class="bi bi-google me-1"></i>Google</button>
            <button type="button" class="btn btn-outline-secondary" data-seo-tab="social"><i class="bi bi-facebook me-1"></i>Facebook / WhatsApp</button>
        </div>
    </div>

    {{-- Google result --}}
    <div class="serp" data-seo-pane="google">
        <div class="serp-main">
            <div class="serp-site">
                <span class="serp-fav">@if($fav)<img src="{{ $fav }}" alt="">@else<i class="bi bi-globe2"></i>@endif</span>
                <span class="min-w-0">
                    <span class="serp-name">{{ $siteName }}</span>
                    <span class="serp-url" data-pv-url>{{ $host }}</span>
                </span>
            </div>
            <div class="serp-title" data-pv-title></div>
            <div class="serp-desc" data-pv-desc></div>
        </div>
        <div class="serp-thumb" data-pv-thumb><img alt=""></div>
    </div>

    {{-- Facebook / WhatsApp card --}}
    <div class="share-card d-none" data-seo-pane="social">
        <div class="share-img" data-pv-share-img><img alt=""><span><i class="bi bi-image"></i> No image</span></div>
        <div class="share-body">
            <div class="share-host">{{ strtoupper($host) }}</div>
            <div class="share-title" data-pv-share-title></div>
            <div class="share-desc" data-pv-share-desc></div>
        </div>
    </div>

    <div class="seo-meters small mt-2">
        <span data-pv-meter="title"></span>
        <span data-pv-meter="desc"></span>
        <span data-pv-meter="image"></span>
    </div>
    @unless($fav)
        <div class="small text-warning mt-1"><i class="bi bi-exclamation-circle me-1"></i>No favicon yet — add it in <a href="{{ route('admin.settings') }}#branding">Settings</a> so Google shows your icon.</div>
    @endunless
    <div class="small text-muted mt-1">Google decides the final look; this shows what the page sends. New pages and changes appear on Google after it visits the page again.</div>
</div>
