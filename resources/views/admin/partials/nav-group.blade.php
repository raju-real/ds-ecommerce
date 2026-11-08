{{-- Sidebar menu with a submenu. Expects $id, $label, $icon and $items: [[label, route, [active route patterns]], ...]; optional $badge (HTML shown on the parent). --}}
@php
    $open = collect($items)->contains(fn ($item) => request()->routeIs(...$item[2]));
@endphp
<div class="nav-group">
    <a class="nav-link has-sub {{ $open ? 'open' : '' }}" href="{{ route($items[0][1]) }}" data-bs-toggle="collapse" data-bs-target="#nav-{{ $id }}"
       role="button" aria-expanded="{{ $open ? 'true' : 'false' }}" aria-controls="nav-{{ $id }}" data-label="{{ $label }}">
        <i class="bi {{ $icon }}"></i><span class="label">{{ $label }}</span>{!! $badge ?? '' !!}<i class="bi bi-chevron-down caret"></i>
    </a>
    <div class="collapse {{ $open ? 'show' : '' }}" id="nav-{{ $id }}">
        <div class="sub">
            @foreach($items as [$text, $route, $patterns])
                <a class="nav-link {{ request()->routeIs(...$patterns) ? 'active' : '' }}" href="{{ route($route) }}">{{ $text }}</a>
            @endforeach
        </div>
    </div>
</div>
