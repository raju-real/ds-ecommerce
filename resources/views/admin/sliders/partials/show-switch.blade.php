{{-- "Show" switch for one banner part (title | subtitle | button). Expects $part and $slider. --}}
<div class="form-check form-switch mb-2 small">
    <input type="hidden" name="show_{{ $part }}" value="0">
    <input class="form-check-input" type="checkbox" role="switch" name="show_{{ $part }}" value="1" id="show_{{ $part }}" data-show-part="{{ $part }}" @checked(old("show_$part", $slider->shows($part)))>
    <label class="form-check-label fw-semibold" for="show_{{ $part }}">Show {{ $part === 'button' ? 'button' : 'on website' }}</label>
</div>
