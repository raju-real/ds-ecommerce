{{-- Top-bar bell: unseen count + latest notifications. Expects $notify (NotificationRepository::summary). --}}
<div class="dropdown notify-bell">
    <button type="button" class="icon-btn position-relative" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Notifications" aria-label="Notifications">
        <i class="bi bi-bell"></i>
        <span class="notify-count {{ $notify['unseen'] ? '' : 'd-none' }}" data-notify-count>{{ $notify['unseen'] > 99 ? '99+' : $notify['unseen'] }}</span>
    </button>
    <div class="dropdown-menu dropdown-menu-end notify-panel shadow border-0 p-0">
        <div class="notify-head">
            <strong>Notifications</strong>
            <form method="post" action="{{ route('admin.notifications.mark-all') }}" data-notify-mark-all class="{{ $notify['unseen'] ? '' : 'd-none' }}">
                @csrf
                <button class="btn btn-link btn-sm p-0 text-decoration-none">Mark all as seen</button>
            </form>
        </div>
        <div class="notify-list" data-notify-list>
            @include('admin.notifications.dropdown-items', ['items' => $notify['latest']])
        </div>
        <a href="{{ route('admin.notifications.index') }}" class="notify-foot">View all notifications <i class="bi bi-arrow-right"></i></a>
    </div>
</div>
