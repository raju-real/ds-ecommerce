@extends('admin.layouts.app')

@section('title', 'Sort categories')

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
@endpush

@section('content')
    <div class="page-header">
        <div>
            <h1>Sort categories</h1>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.categories.index') }}">Categories</a></li><li class="breadcrumb-item active">Sort</li></ol></nav>
        </div>
        <a href="{{ route('admin.categories.index') }}" class="btn btn-soft-secondary"><i class="bi bi-arrow-left me-1"></i> Categories</a>
    </div>

    <div class="alert alert-info border-0 small d-flex gap-2" style="border-radius: 12px">
        <i class="bi bi-arrows-move"></i>
        <div>Drag the <i class="bi bi-grip-vertical"></i> handle to change the order. Categories and their subcategories are saved automatically after each move — this order is used in the store menu, the home page and filters.</div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5>{{ $categories->count() }} categories</h5>
            <span class="small text-muted" id="sortStatus" aria-live="polite"></span>
        </div>
        <div class="card-body">
            @if($categories->isEmpty())
                <div class="empty"><i class="bi bi-collection"></i>No categories yet.</div>
            @else
                <ul class="sort-list" id="categorySort" data-save-url="{{ route('admin.categories.sort.save') }}">
                    @foreach($categories as $cat)
                        <li class="sort-item" data-uid="{{ $cat->unique_id }}">
                            <div class="sort-row">
                                <span class="sort-handle" title="Drag to move"><i class="bi bi-grip-vertical"></i></span>
                                <img src="{{ img_url($cat->image) }}" alt="" width="36" height="36">
                                <span class="fw-semibold flex-grow-1 text-truncate">{{ $cat->name }}</span>
                                @unless($cat->is_active)<span class="badge badge-soft-secondary">Hidden</span>@endunless
                                <span class="badge badge-soft-primary">{{ $cat->subcategories->count() }} sub</span>
                                @if($cat->subcategories->count() > 1)
                                    <button type="button" class="btn btn-sm btn-soft-secondary" data-bs-toggle="collapse" data-bs-target="#subs-{{ $cat->unique_id }}" aria-expanded="false">Subcategories</button>
                                @endif
                            </div>
                            @if($cat->subcategories->count() > 1)
                                <div class="collapse" id="subs-{{ $cat->unique_id }}">
                                    <ul class="sort-list sub-sort" data-category="{{ $cat->unique_id }}">
                                        @foreach($cat->subcategories as $sub)
                                            <li class="sort-item" data-uid="{{ $sub->unique_id }}">
                                                <div class="sort-row small">
                                                    <span class="sort-handle" title="Drag to move"><i class="bi bi-grip-vertical"></i></span>
                                                    <span class="flex-grow-1 text-truncate">{{ $sub->name }}</span>
                                                    @unless($sub->is_active)<span class="badge badge-soft-secondary">Hidden</span>@endunless
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endsection

@push('page-scripts')
    <script>
        (function ($) {
            const list = document.getElementById('categorySort');
            if (!list || !window.Sortable) return;
            const $status = $('#sortStatus');
            let timer = null;

            function save() {
                const data = {
                    categories: $(list).children('.sort-item').map((_, li) => li.dataset.uid).get(),
                    subcategories: {},
                };
                $(list).find('.sub-sort').each(function () {
                    data.subcategories[this.dataset.category] = $(this).children('.sort-item').map((_, li) => li.dataset.uid).get();
                });
                $status.html('<span class="spinner-border spinner-border-sm me-1"></span>Saving…');
                $.ajax({ url: list.dataset.saveUrl, method: 'POST', contentType: 'application/json', data: JSON.stringify(data) })
                    .done(() => { $status.html('<i class="bi bi-check2-circle text-success me-1"></i>Saved'); notify('Category order saved'); })
                    .fail(() => { $status.html('<span class="text-danger">Could not save — please try again</span>'); notify('Could not save the order', 'error'); });
            }
            const opts = { handle: '.sort-handle', dataIdAttr: 'data-uid', animation: 150, ghostClass: 'sort-ghost', onEnd: () => { clearTimeout(timer); timer = setTimeout(save, 300); } };
            Sortable.create(list, opts);                                         // categories among themselves
            list.querySelectorAll('.sub-sort').forEach((ul) => Sortable.create(ul, opts)); // subcategories within their category
        })(jQuery);
    </script>
@endpush
