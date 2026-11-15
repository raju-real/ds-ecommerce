{{-- English / বাংলা switch. The choice is kept in the "lang" cookie; first visit follows the browser language. --}}
@php($current = app()->getLocale())
<span class="lang-switch {{ $class ?? '' }}" role="group" aria-label="Language" translate="no">
    @foreach(\App\Support\Locale::SUPPORTED as $code => $label)
        @if($code === $current)
            <span class="active" aria-current="true" lang="{{ $code }}">{{ $code === 'en' ? 'EN' : 'বাংলা' }}</span>
        @else
            <a href="{{ route('lang.switch', $code) }}" lang="{{ $code }}" hreflang="{{ $code }}" rel="nofollow">{{ $code === 'en' ? 'EN' : 'বাংলা' }}</a>
        @endif
    @endforeach
</span>
