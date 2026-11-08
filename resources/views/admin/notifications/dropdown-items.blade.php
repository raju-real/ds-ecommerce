{{-- Notification rows for the bell dropdown. Expects $items. --}}
@forelse($items as $n)
    <a href="{{ route('admin.notifications.open', $n->unique_id) }}" class="notify-item {{ $n->read_at ? 'seen' : 'unseen' }}">
        <span class="notify-icon"><i class="bi bi-bag-check"></i></span>
        <span class="min-w-0 flex-grow-1">
            <span class="notify-title">{{ $n->title }}</span>
            <span class="notify-body">{{ $n->body }}</span>
            <span class="notify-time">{{ $n->created_at->diffForHumans() }}</span>
        </span>
        @unless($n->read_at)<span class="notify-dot" title="Unseen"></span>@endunless
    </a>
@empty
    <div class="notify-empty"><i class="bi bi-bell-slash"></i>No notifications yet. New orders appear here.</div>
@endforelse
