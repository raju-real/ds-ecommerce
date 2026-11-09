{{-- Tiny SEO status marks for the product list: meta title · description · keywords · share image (green = set).
     Expects $p from ProductRepository::adminPaginate (seo_title_len, seo_desc_len, seo_kw_count, meta_image). --}}
@php
    $seo = [
        ['T', 'Meta title', (int) $p->seo_title_len, $p->seo_title_len ? $p->seo_title_len.' characters' : 'not set (product name is used)'],
        ['D', 'Meta description', (int) $p->seo_desc_len, $p->seo_desc_len ? $p->seo_desc_len.' characters' : 'not set (short description is used)'],
        ['K', 'Meta keywords', (int) $p->seo_kw_count, $p->seo_kw_count ? $p->seo_kw_count.' '.\Illuminate\Support\Str::plural('keyword', (int) $p->seo_kw_count) : 'not set'],
        ['<i class="bi bi-image"></i>', 'Share image', $p->meta_image ? 1 : 0, $p->meta_image ? 'set' : 'not set (product photo is used)'],
    ];
    $done = collect($seo)->filter(fn ($s) => $s[2] > 0)->count();
@endphp
<a href="{{ route('admin.products.edit', $p->unique_id) }}#seo" class="seo-status"
   title="SEO {{ $done }}/4&#10;{{ collect($seo)->map(fn ($s) => ($s[2] ? '✓ ' : '✗ ').$s[1].': '.$s[3])->implode("\n") }}">
    @foreach($seo as [$mark, $label, $has, $info])
        <span class="seo-dot {{ $has ? 'on' : 'off' }}" aria-label="{{ $label }}: {{ $info }}">{!! $mark !!}</span>
    @endforeach
</a>
