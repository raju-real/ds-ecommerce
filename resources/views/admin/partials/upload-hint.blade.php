{{-- Image upload guidance from config shop.uploads. Expects $type, optional $note and $extra (chip text). --}}
@php
    $cfg = config('shop.uploads');
    [$w, $h, $ratio] = $cfg['recommended'][$type];
    $min = $cfg['min'][$type] ?? null;
@endphp
<div class="upload-hint">
    <i class="bi bi-info-circle-fill"></i>
    <div class="min-w-0">
        <div class="lead-line">Recommended <strong>{{ $w }} × {{ $h }} px</strong> <span>· {{ $ratio }}</span></div>
        <div class="chips">
            <span><i class="bi bi-file-earmark-image"></i>{{ strtoupper(implode(', ', array_diff($cfg['mimes'], ['jpeg']))) }}</span>
            <span><i class="bi bi-hdd"></i>Max {{ round($cfg[$type] / 1024, 1) }} MB{{ $type === 'product' ? ' each' : '' }}</span>
            @if($min)<span><i class="bi bi-aspect-ratio"></i>Min {{ $min[0] }} × {{ $min[1] }} px</span>@endif
            @isset($extra)<span><i class="bi bi-collection"></i>{{ $extra }}</span>@endisset
        </div>
        @isset($note)<div class="note">{{ $note }}</div>@endisset
    </div>
</div>
