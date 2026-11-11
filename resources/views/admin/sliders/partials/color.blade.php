{{-- One colour field: picker + #code box + reset. Expects $key, $label, $default (and $slider from the form). --}}
@php($val = old($key, $slider->$key))
<label class="form-label" for="{{ $key }}">{{ $label }}</label>
<div class="input-group" data-color-field>
    <input type="color" class="form-control form-control-color flex-grow-0" value="{{ $val ?: $default }}" data-color-picker aria-label="{{ $label }} picker" style="width: 46px">
    <input type="text" name="{{ $key }}" id="{{ $key }}" class="form-control font-monospace @error($key) is-invalid @enderror" value="{{ $val }}" placeholder="Default" maxlength="7" pattern="#[0-9a-fA-F]{6}" data-msg-pattern="Colour code looks like #FFFFFF." data-color-code data-default="{{ $default }}" spellcheck="false" autocomplete="off">
    <button type="button" class="btn btn-outline-secondary" data-color-reset title="Use default"><i class="bi bi-arrow-counterclockwise"></i></button>
    @error($key)<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
