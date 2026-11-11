{{-- What sits on top of a banner image: shade, title, subtitle, button — each shown or hidden on its own (Admin → Sliders).
     Used by the home page AND the admin previews, so they always match.
     Expects $slide; $link (full URL or null); $preview = true in the admin (button is a plain span, nothing clickable). --}}
@php
    $preview = $preview ?? false;
    $parts = $slide->visibleParts();
    $btnPos = $slide->buttonPosition();
    $floatBtn = $parts['button'] && in_array($btnPos, ['bottom_center', 'bottom_right'], true);
    $btnHtml = function () use ($slide, $link, $preview) {
        $style = $slide->colorStyle('button');
        $attrs = 'class="btn btn-light fw-bold hero-btn'.($preview ? ' btn-sm' : '').'"'.($style ? ' style="'.e($style).'"' : '');
        $label = e($slide->button_text).' <i class="bi bi-arrow-right ms-1"></i>';

        return $preview ? "<span $attrs>$label</span>" : '<a href="'.e($link).'" '.$attrs.'>'.$label.'</a>';
    };
@endphp
{{-- No button on the banner but a link is set: the whole banner opens it. --}}
@if(! $preview && $link && ! $parts['button'])
    <a href="{{ $link }}" class="stretched-link" aria-label="{{ $slide->title ?: 'Open offer' }}"></a>
@endif
@if(in_array(true, $parts, true))
    @if(($parts['title'] || $parts['subtitle']) && in_array($slide->text_shade, ['dark', 'light'], true))
        <div class="hero-shade hero-shade-{{ $slide->text_shade }}" aria-hidden="true"></div>
    @endif
    <div class="carousel-caption btn-pos-{{ $btnPos }} {{ $parts['button'] ? '' : 'no-btn' }}">
        <div class="hero-text">
            @if($parts['title'])<h2 @if($css = $slide->colorStyle('title')) style="{{ $css }}" @endif>{{ $slide->title }}</h2>@endif
            @if($parts['subtitle'])<p @if($css = $slide->colorStyle('subtitle')) style="{{ $css }}" @endif>{{ $slide->subtitle }}</p>@endif
            @if($parts['button'] && $btnPos === 'below_text'){!! $btnHtml() !!}@endif
        </div>
        @if($parts['button'] && $btnPos === 'bottom_left'){!! $btnHtml() !!}@endif
        {{-- Keeps the text above a center/right button --}}
        @if($floatBtn)<span class="btn btn-light fw-bold hero-btn hero-btn-spacer{{ $preview ? ' btn-sm' : '' }}" aria-hidden="true">{{ $slide->button_text }} <i class="bi bi-arrow-right ms-1"></i></span>@endif
    </div>
    @if($floatBtn)
        <div class="hero-btn-float hero-btn-{{ $btnPos }}">{!! $btnHtml() !!}</div>
    @endif
@endif
