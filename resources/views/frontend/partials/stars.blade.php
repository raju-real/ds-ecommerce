{{-- 5 stars for a rating (0–5, halves shown). Expects $rating; optional $class. Colour: shop accent (shop.css .stars). --}}
@php($r = max(0, min(5, (float) $rating)))
<span class="stars {{ $class ?? '' }}" role="img" aria-label="Rated {{ rtrim(rtrim(number_format($r, 1), '0'), '.') }} out of 5">@for($i = 1; $i <= 5; $i++)<i class="bi {{ $r >= $i ? 'bi-star-fill' : ($r >= $i - 0.5 ? 'bi-star-half' : 'bi-star') }}" aria-hidden="true"></i>@endfor</span>
