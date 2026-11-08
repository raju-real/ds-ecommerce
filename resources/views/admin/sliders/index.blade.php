@extends('admin.layouts.app')

@section('title', 'Sliders')

@section('content')
    <div class="page-header">
        <div>
            <h1>Home sliders</h1>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li><li class="breadcrumb-item active">Sliders</li></ol></nav>
        </div>
        <a href="{{ route('admin.sliders.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Add slide</a>
    </div>

    <div class="row g-4">
        @forelse($sliders as $slide)
            <div class="col-md-6 col-xl-4">
                <div class="card h-100 overflow-hidden">
                    <div class="position-relative">
                        <img src="{{ img_url($slide->image) }}" class="w-100" style="aspect-ratio: 16/6; object-fit: cover" alt="" loading="lazy">
                        <span class="position-absolute top-0 start-0 m-2">{!! $slide->is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Hidden</span>' !!}</span>
                        <span class="position-absolute top-0 end-0 m-2 badge bg-dark">#{{ $slide->sort_order }}</span>
                    </div>
                    <div class="card-body">
                        <div class="fw-bold">{{ $slide->title ?: 'Untitled slide' }}</div>
                        <div class="small text-muted text-truncate">{{ $slide->subtitle ?: '—' }}</div>
                        @if($slide->link)<div class="small mt-1 text-truncate"><i class="bi bi-link-45deg"></i> {{ $slide->link }}</div>@endif
                    </div>
                    <div class="card-footer d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.sliders.edit', $slide->unique_id) }}" class="btn btn-soft-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
                        @include('admin.partials.delete', ['action' => route('admin.sliders.destroy', $slide->unique_id)])
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><div class="card"><div class="empty"><i class="bi bi-images"></i>No slides yet. Add your first banner.</div></div></div>
        @endforelse
    </div>
@endsection
