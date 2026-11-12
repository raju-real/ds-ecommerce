@extends('admin.layouts.app')

@section('title', 'Pages')

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
@endpush

{{-- Website → Pages: drag to set the footer order, switch active/inactive. Active pages are linked from the footer. --}}
@section('content')
    <div class="page-header">
        <div>
            <h1>Pages</h1>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li><li class="breadcrumb-item active">Pages</li></ol></nav>
        </div>
        <a href="{{ route('admin.pages.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Add page</a>
    </div>

    <div class="card">
        @if($pages->count() > 1)
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="small text-muted"><i class="bi bi-arrows-move me-1"></i>Drag a row by <i class="bi bi-grip-vertical"></i> to change the order in the footer. It saves by itself.</div>
                <div class="small" id="pageSortStatus" role="status" aria-live="polite"></div>
            </div>
        @endif
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th style="width: 36px"></th><th>Page</th><th>Status</th><th class="text-center">Order</th><th>Updated</th><th class="text-end">Actions</th></tr></thead>
                <tbody id="pageSort" data-save-url="{{ route('admin.pages.sort') }}">
                    @forelse($pages as $page)
                        <tr class="page-row" data-uid="{{ $page->unique_id }}">
                            <td>@if($pages->count() > 1)<button type="button" class="btn btn-light btn-sm py-0 px-1 sort-handle" title="Drag to reorder" aria-label="Drag to reorder"><i class="bi bi-grip-vertical"></i></button>@endif</td>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    @if($page->image)
                                        <img src="{{ $page->image_url }}" class="thumb" style="width: 72px; height: 38px; object-fit: cover" alt="" loading="lazy">
                                    @else
                                        <span class="thumb d-inline-flex align-items-center justify-content-center text-muted bg-light" style="width: 72px; height: 38px"><i class="bi bi-file-earmark-text"></i></span>
                                    @endif
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.pages.edit', $page->unique_id) }}" class="title d-block text-truncate cell-title" style="max-width: 360px">{{ $page->name }}</a>
                                        <div class="small text-muted text-truncate" style="max-width: 360px">/{{ $page->slug }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="form-check form-switch mb-0" title="Active pages are shown on the site and in the footer">
                                    <input class="form-check-input" type="checkbox" role="switch" data-toggle-url="{{ route('admin.pages.toggle', $page->unique_id) }}" @checked($page->is_active) aria-label="Active">
                                    <span class="small text-muted page-status" data-on="Active" data-off="Inactive">{{ $page->is_active ? 'Active' : 'Inactive' }}</span>
                                </div>
                            </td>
                            <td class="text-center"><span class="badge bg-dark" data-position>#{{ $loop->iteration }}</span></td>
                            <td class="small text-muted text-nowrap" title="{{ $page->updated_at?->format('d M Y, h:i A') }}">{{ $page->updated_at?->format('d M Y') }}</td>
                            <td class="text-end">
                                <div class="row-actions">
                                    <a href="{{ $page->url }}" target="_blank" class="btn btn-soft-secondary btn-icon {{ $page->is_active ? '' : 'disabled' }}" title="View on site" data-view-link><i class="bi bi-box-arrow-up-right"></i></a>
                                    <a href="{{ route('admin.pages.edit', $page->unique_id) }}" class="btn btn-soft-primary btn-icon" title="Edit"><i class="bi bi-pencil"></i></a>
                                    @include('admin.partials.delete', ['action' => route('admin.pages.destroy', $page->unique_id), 'message' => 'The page will be removed from the site and the footer.'])
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="empty"><i class="bi bi-file-earmark-richtext"></i>No pages yet. Add pages such as About us, Privacy policy or Return policy — active pages are linked from the footer.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        #pageSort .sort-handle { cursor: grab; line-height: 1.4; }
        #pageSort .sort-ghost { opacity: .45; background: #eef2ff; }
    </style>
@endpush

@push('page-scripts')
    <script>
        (function ($) {
            // Status label next to the switch follows it (the switch itself saves through admin.js [data-toggle-url]).
            $(document).on('change', '#pageSort [data-toggle-url]', function () {
                const $row = $(this).closest('tr'), on = this.checked, $label = $row.find('.page-status');
                $label.text(on ? $label.data('on') : $label.data('off'));
                $row.find('[data-view-link]').toggleClass('disabled', !on);
            });

            const list = document.getElementById('pageSort');
            if (!list || !window.Sortable || !list.querySelector('.sort-handle')) return;
            const $status = $('#pageSortStatus');
            let timer = null;
            const renumber = () => $(list).children('.page-row').each((i, el) => $(el).find('[data-position]').text('#' + (i + 1)));
            function save() {
                const pages = $(list).children('.page-row').map((_, el) => el.dataset.uid).get();
                $status.html('<span class="spinner-border spinner-border-sm me-1"></span>Saving…');
                $.ajax({ url: list.dataset.saveUrl, method: 'POST', contentType: 'application/json', data: JSON.stringify({ pages }) })
                    .done(() => { $status.html('<i class="bi bi-check2-circle text-success me-1"></i>Saved'); notify('Page order saved'); })
                    .fail(() => { $status.html('<span class="text-danger">Could not save — please try again</span>'); notify('Could not save the order', 'error'); });
            }
            Sortable.create(list, {
                handle: '.sort-handle', draggable: '.page-row', animation: 150, ghostClass: 'sort-ghost',
                onEnd: (e) => { if (e.oldIndex === e.newIndex) return; renumber(); clearTimeout(timer); timer = setTimeout(save, 300); },
            });
        })(jQuery);
    </script>
@endpush
