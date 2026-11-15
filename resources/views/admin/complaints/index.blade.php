@extends('admin.layouts.app')

@section('title', 'Complaints')

{{-- Customers → Complaints: numbered complaints from the shop (menu → Complaint), by status. --}}
@section('content')
    <div class="page-header">
        <div>
            <h1>Complaints</h1>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li><li class="breadcrumb-item active">Complaints</li></ol></nav>
        </div>
    </div>

    {{-- Status chips with counts --}}
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('admin.complaints.index', array_filter(['q' => $filters['q'] ?? null])) }}" class="btn btn-sm {{ empty($filters['status']) ? 'btn-primary' : 'btn-soft-secondary' }}">All <span class="badge bg-light text-dark ms-1">{{ array_sum($counts) }}</span></a>
        @foreach(\App\Models\Complaint::STATUSES as $key => $st)
            <a href="{{ route('admin.complaints.index', array_filter(['status' => $key, 'q' => $filters['q'] ?? null])) }}" class="btn btn-sm {{ ($filters['status'] ?? '') === $key ? 'btn-primary' : 'btn-soft-secondary' }}">{{ $st['label'] }} <span class="badge bg-light text-dark ms-1">{{ $counts[$key] ?? 0 }}</span></a>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header">
            <form class="filter-bar">
                @if(!empty($filters['status']))<input type="hidden" name="status" value="{{ $filters['status'] }}">@endif
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control search" placeholder="Search number, name or mobile…" maxlength="100">
                <button class="btn btn-soft-primary"><i class="bi bi-search me-1"></i> Search</button>
                @if(!empty($filters['q']))<a href="{{ route('admin.complaints.index', array_filter(['status' => $filters['status'] ?? null])) }}" class="btn btn-soft-secondary">Clear</a>@endif
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>No.</th><th>Customer</th><th style="min-width: 280px">Complaint</th><th>Status</th><th>Received</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    @forelse($complaints as $c)
                        <tr>
                            <td><a href="{{ route('admin.complaints.show', $c->unique_id) }}" class="fw-bold">#{{ $c->complaint_no }}</a></td>
                            <td class="small"><div class="fw-semibold">{{ $c->name }}</div><a href="tel:{{ $c->mobile }}" class="text-muted">{{ $c->mobile }}</a></td>
                            <td class="small">{{ \Illuminate\Support\Str::limit($c->message, 140) }} @if($c->images)<span class="text-muted ms-1" title="{{ count($c->images) }} photo(s)"><i class="bi bi-image"></i> {{ count($c->images) }}</span>@endif</td>
                            <td><span class="badge badge-soft-{{ $c->status_color }}">{{ $c->status_label }}</span></td>
                            <td class="small text-muted text-nowrap" title="{{ $c->created_at->format('d M Y, h:i A') }}">{{ $c->created_at->diffForHumans() }}</td>
                            <td class="text-end"><a href="{{ route('admin.complaints.show', $c->unique_id) }}" class="btn btn-soft-primary btn-sm"><i class="bi bi-eye me-1"></i>Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="empty"><i class="bi bi-megaphone"></i>No complaints {{ $filters ? 'match' : 'yet' }}.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($complaints->hasPages())<div class="card-footer">{{ $complaints->links() }}</div>@endif
    </div>
@endsection
