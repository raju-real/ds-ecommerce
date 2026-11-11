{{-- Small copy of the home page banner (Sliders list): image + text, colours, shade and button in its position,
     exactly as the website shows it (frontend/home.blade.php). Expects $slide. Styles: admin.css .slide-preview --}}
@php
    $pvPos = $slide->buttonPosition();
    $pvBtn = $slide->link && $slide->button_text;
    $pvText = $slide->showsText() && ($slide->title || $slide->subtitle);
    $pvButton = fn () => '<span class="btn btn-light btn-sm fw-bold hero-btn" style="'.e($slide->colorStyle('button')).'">'.e($slide->button_text).' <i class="bi bi-arrow-right ms-1"></i></span>';
@endphp
<div class="slide-preview slide-preview-flat">
    <div class="carousel-item active d-block">
        <img src="{{ img_url($slide->image) }}" alt="{{ $slide->title }}" loading="lazy">
        @if($pvText)
            @if(in_array($slide->text_shade, ['dark', 'light'], true))<div class="hero-shade hero-shade-{{ $slide->text_shade }}" aria-hidden="true"></div>@endif
            <div class="carousel-caption">
                <div class="hero-text">
                    @if($slide->title)<h2 @if($css = $slide->colorStyle('title')) style="{{ $css }}" @endif>{{ $slide->title }}</h2>@endif
                    @if($slide->subtitle)<p @if($css = $slide->colorStyle('subtitle')) style="{{ $css }}" @endif>{{ $slide->subtitle }}</p>@endif
                    @if($pvBtn && $pvPos === 'below_text'){!! $pvButton() !!}@endif
                </div>
                @if($pvBtn && $pvPos === 'bottom_left'){!! $pvButton() !!}@endif
                @if($pvBtn && in_array($pvPos, ['bottom_center', 'bottom_right'], true))<span class="btn btn-light btn-sm fw-bold hero-btn" aria-hidden="true" style="visibility:hidden">{{ $slide->button_text }}</span>@endif
            </div>
            @if($pvBtn && in_array($pvPos, ['bottom_center', 'bottom_right'], true))
                <div class="hero-btn-float hero-btn-{{ $pvPos }}">{!! $pvButton() !!}</div>
            @endif
        @endif
    </div>
</div>
