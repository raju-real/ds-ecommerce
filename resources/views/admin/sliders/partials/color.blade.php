{{-- One colour field: picker + #code box + reset, all the same height as a normal input (admin.css .color-field).
     Expects $key, $label, $default (and $slider from the form). --}}
@php($val = old($key, $slider->$key))
<label class="form-label" for="{{ $key }}">{{ $label }}</label>
<div class="input-group color-field" data-color-field>
    <input type="color" class="form-control form-control-color" value="{{ $val ?: $default }}" data-color-picker aria-label="{{ $label }} picker">
    <input type="text" name="{{ $key }}" id="{{ $key }}" class="form-control font-monospace @error($key) is-invalid @enderror" value="{{ $val }}" placeholder="Default" maxlength="7" pattern="#[0-9a-fA-F]{6}" data-msg-pattern="Colour code looks like #FFFFFF." data-color-code data-default="{{ $default }}" spellcheck="false" autocomplete="off">
    <button type="button" class="btn" data-color-reset title="Use default" aria-label="Use default {{ strtolower($label) }}"><i class="bi bi-arrow-counterclockwise"></i></button>
    @error($key)<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
