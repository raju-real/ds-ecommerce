{{-- Read-only field like the vendor's form, with a copy button. Expects $label, $value; optional $show, $hint, $strong. --}}
@php($shown = $show ?? $value)
<div class="po-label">{{ $label }}@if(! empty($hint))<span class="text-muted fw-normal"> · {{ $hint }}</span>@endif</div>
<div class="po-field {{ ! empty($strong) ? 'strong' : '' }} {{ filled($value) ? '' : 'empty' }}">
    <span class="po-text">{{ filled($value) ? $shown : '—' }}</span>
    @if(filled($value))<button type="button" class="copy-btn" data-copy="{{ $value }}" data-copy-msg="Copied {{ strtolower($label) }}" title="Copy {{ strtolower($label) }}" aria-label="Copy {{ strtolower($label) }}"><i class="bi bi-copy"></i></button>@endif
</div>
