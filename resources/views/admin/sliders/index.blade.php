@extends('admin.layouts.app')

@section('title', 'Sliders')

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
@endpush

@section('content')
    <div class="page-header">
        <div>
            <h1>Home sliders</h1>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li><li class="breadcrumb-item active">Sliders</li></ol></nav>
        </div>
        <a href="{{ route('admin.sliders.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Add slide</a>
    </div>

    @if($sliders->count() > 1)
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div class="small text-muted"><i class="bi bi-arrows-move me-1"></i>Drag a banner by <i class="bi bi-grip-vertical"></i> to change the order on the home page. It saves by itself.</div>
            <div class="small" id="sliderSortStatus" role="status" aria-live="polite"></div>
        </div>
    @endif

    <div class="row g-4" id="sliderSort" data-save-url="{{ route('admin.sliders.sort') }}">
        @forelse($sliders as $slide)
            <div class="col-md-6 col-xl-4 slider-sort-item" data-uid="{{ $slide->unique_id }}">
                <div class="card h-100 overflow-hidden">
                    <div class="position-relative">
                        <img src="{{ img_url($slide->image) }}" class="w-100" style="aspect-ratio: 16/6; object-fit: cover" alt="" loading="lazy">
                        <span class="position-absolute top-0 start-0 m-2">{!! $slide->is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Hidden</span>' !!}</span>
                        @unless($slide->showsText())<span class="position-absolute bottom-0 start-0 m-2 badge bg-dark bg-opacity-75"><i class="bi bi-eye-slash me-1"></i>Text hidden</span>@endunless
                        <span class="position-absolute top-0 end-0 m-2 d-flex gap-1 align-items-center">
                            <span class="badge bg-dark" data-position title="Position on the home page">#{{ $loop->iteration }}</span>
                            @if($sliders->count() > 1)<button type="button" class="btn btn-light btn-sm py-0 px-1 sort-handle" title="Drag to reorder" aria-label="Drag to reorder"><i class="bi bi-grip-vertical"></i></button>@endif
                        </span>
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

@push('styles')
    <style>
        #sliderSort .sort-handle { cursor: grab; line-height: 1.4; }
        #sliderSort .sort-ghost > .card { opacity: .4; outline: 2px dashed #4f46e5; }
    </style>
@endpush

@push('page-scripts')
    <script>
        (function ($) {
            const list = document.getElementById('sliderSort');
            if (!list || !window.Sortable || !list.querySelector('.sort-handle')) return;
            const $status = $('#sliderSortStatus');
            let timer = null;
            const renumber = () => $(list).children('.slider-sort-item').each((i, el) => $(el).find('[data-position]').text('#' + (i + 1)));
            function save() {
                const sliders = $(list).children('.slider-sort-item').map((_, el) => el.dataset.uid).get();
                $status.html('<span class="spinner-border spinner-border-sm me-1"></span>Saving…');
                $.ajax({ url: list.dataset.saveUrl, method: 'POST', contentType: 'application/json', data: JSON.stringify({ sliders }) })
                    .done(() => { $status.html('<i class="bi bi-check2-circle text-success me-1"></i>Saved'); notify('Banner order saved'); })
                    .fail(() => { $status.html('<span class="text-danger">Could not save — please try again</span>'); notify('Could not save the order', 'error'); });
            }
            Sortable.create(list, {
                handle: '.sort-handle', draggable: '.slider-sort-item', animation: 150, ghostClass: 'sort-ghost',
                onEnd: (e) => { if (e.oldIndex === e.newIndex) return; renumber(); clearTimeout(timer); timer = setTimeout(save, 300); },
            });
        })(jQuery);
    </script>
@endpush
