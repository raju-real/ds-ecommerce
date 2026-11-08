@extends('admin.layouts.app')

@section('title', 'Notifications')

@section('content')
    <div class="page-header">
        <div>
            <h1>Notifications @if($unseen)<span class="badge badge-soft-danger align-middle fs-6 ms-1">{{ $unseen }} unseen</span>@endif</h1>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li><li class="breadcrumb-item active">Notifications</li></ol></nav>
        </div>
        @if($unseen)
            <form method="post" action="{{ route('admin.notifications.mark-all') }}">
                @csrf
                <button class="btn btn-soft-primary"><i class="bi bi-check2-all me-1"></i> Mark all as seen</button>
            </form>
        @endif
    </div>

    <div class="status-tabs mb-3">
        @foreach([null => 'All', 'unseen' => 'Unseen', 'seen' => 'Seen'] as $key => $label)
            <a href="{{ route('admin.notifications.index', array_filter(['show' => $key])) }}" class="{{ $show === ($key ?: null) ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="card">
        <div class="list-group list-group-flush notify-page">
            @forelse($notifications as $n)
                <div class="list-group-item d-flex align-items-center gap-3 py-3 {{ $n->read_at ? '' : 'unseen' }}">
                    <span class="notify-icon"><i class="bi bi-bag-check"></i></span>
                    <a href="{{ route('admin.notifications.open', $n->unique_id) }}" class="min-w-0 flex-grow-1 text-reset">
                        <div class="fw-semibold">{{ $n->title }} @unless($n->read_at)<span class="badge badge-soft-danger ms-1">New</span>@endunless</div>
                        <div class="small text-muted">{{ $n->body }}</div>
                        <div class="small text-muted">{{ $n->created_at->format('d M Y, h:i A') }} · {{ $n->created_at->diffForHumans() }}</div>
                    </a>
                    @if($n->order)<span class="d-none d-md-inline">{!! order_status_badge($n->order->status) !!}</span>@endif
                    <form method="post" action="{{ route('admin.notifications.toggle', $n->unique_id) }}">
                        @csrf @method('PATCH')
                        <button class="btn btn-soft-secondary btn-sm text-nowrap" title="{{ $n->read_at ? 'Mark as unseen' : 'Mark as seen' }}">
                            <i class="bi {{ $n->read_at ? 'bi-envelope' : 'bi-envelope-open' }} me-1"></i>{{ $n->read_at ? 'Unseen' : 'Seen' }}
                        </button>
                    </form>
                </div>
            @empty
                <div class="empty"><i class="bi bi-bell-slash"></i>No notifications {{ $show ? "($show)" : '' }}. New orders appear here.</div>
            @endforelse
        </div>
        @if($notifications->hasPages())<div class="card-footer">{{ $notifications->links() }}</div>@endif
    </div>
@endsection
