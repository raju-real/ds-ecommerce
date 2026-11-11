{{-- Banner button (home slider). Expects $slide, $link. Colours from Admin → Sliders. --}}
<a href="{{ $link }}" class="btn btn-light fw-bold hero-btn" @if($css = $slide->colorStyle('button')) style="{{ $css }}" @endif>{{ $slide->button_text }} <i class="bi bi-arrow-right ms-1"></i></a>
