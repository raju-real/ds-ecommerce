{{-- A value with a small copy button. Expects $value (copied text); optional $show (displayed text), $class. --}}
@php($shown = $show ?? $value)
@if(filled($value))
    <span class="copy-val {{ $class ?? '' }}">{{ $shown }}<button type="button" class="copy-btn" data-copy="{{ $value }}" data-copy-msg="Copied: {{ \Illuminate\Support\Str::limit($value, 40) }}" title="Copy {{ \Illuminate\Support\Str::limit($value, 40) }}" aria-label="Copy"><i class="bi bi-copy"></i></button></span>
@else
    <span class="text-muted">—</span>
@endif
