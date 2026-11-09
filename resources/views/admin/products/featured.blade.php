@extends('admin.layouts.app')

@section('title', 'Featured Products')

@section('content')
    <div class="page-header">
        <div>
            <h1>Featured Products <span class="badge badge-soft-primary align-middle fs-6 ms-1" id="featuredCount">{{ $featured->count() }}</span></h1>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.products.index') }}">Products</a></li><li class="breadcrumb-item active">Featured</li></ol></nav>
        </div>
        <a href="{{ route('home') }}#featured" target="_blank" class="btn btn-soft-secondary"><i class="bi bi-box-arrow-up-right me-1"></i> View on home page</a>
    </div>

    {{-- Add: search the cached catalogue and add with one click --}}
    <div class="card mb-4" style="overflow: visible">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5><i class="bi bi-plus-circle me-1 text-primary"></i> Add a product to featured</h5>
            <span class="small text-muted">New products go to the top of the list. Home page: first {{ config('shop.home.featured') }}, {{ config('shop.home.featured') }} more per “Load more”, up to {{ config('shop.home.featured_max') }}; then “View all”.</span>
        </div>
        <div class="card-body">
            <div class="fp-picker" id="fpPicker" data-url="{{ route('admin.products.featured.search') }}" data-add-url="{{ route('admin.products.featured.add', 'aaaaaaaaaa') }}">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="search" class="form-control" id="fpSearch" placeholder="Search by product name, code, category, vendor or brand…" maxlength="100" autocomplete="off"
                           role="combobox" aria-expanded="false" aria-controls="fpResults" aria-autocomplete="list">
                </div>
                <div class="fp-panel" id="fpResults" role="listbox" hidden></div>
            </div>
            <div class="form-text mt-2"><i class="bi bi-lightbulb"></i> Combine words to narrow down, e.g. <code>zentro beauty</code> or <code>gadget hub watch</code>. Use <kbd>↑</kbd> <kbd>↓</kbd> and <kbd>Enter</kbd> to add from the keyboard.</div>
        </div>
    </div>

    {{-- Current featured products --}}
    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5>Featured list <span class="small text-muted fw-normal ms-1" id="fpLive">{{ $featured->where('is_featured', true)->count() }} shown on home</span></h5>
                <div class="small text-muted mt-1"><i class="bi bi-grip-vertical"></i> Drag rows to set the home-page order · the switch hides a product from home without removing it from this list.</div>
            </div>
            <input type="search" class="form-control form-control-sm" id="fpFilter" placeholder="Filter this list…" maxlength="100" style="max-width: 260px">
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle fp-table">
                <thead>
                    <tr>
                        <th class="fp-handle-col" aria-label="Order"></th>
                        <th>Product</th><th>Category / Vendor</th><th class="text-end">Price</th>
                        <th class="text-center" title="Orders that include this product">Orders</th>
                        <th title="Show this product in the Featured section of the home page">On home</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="featuredBody" data-sort-url="{{ route('admin.products.featured.sort') }}">
                    @include('admin.products.partials.featured-rows')
                </tbody>
            </table>
        </div>
        <div class="card-footer small text-muted d-none" id="fpNoMatch">No featured product matches this filter.</div>
        <div class="card-footer small text-muted d-none" id="fpSortOff"><i class="bi bi-info-circle me-1"></i>Clear the filter to drag and reorder.</div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
@endpush

@push('page-scripts')
    <script>
        (function ($) {
            const $picker = $('#fpPicker');
            const $input = $('#fpSearch');
            const $panel = $('#fpResults');
            const searchUrl = $picker.data('url');
            const addUrl = (uid) => $picker.data('add-url').replace('aaaaaaaaaa', uid);
            const cache = new Map();
            let items = [];
            let active = -1;
            let timer = null;
            let seq = 0;

            const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
            const words = () => $input.val().trim().toLowerCase().split(/\s+/).filter(Boolean);
            // Highlight each search word inside already-escaped text.
            const mark = (text) => {
                let html = esc(text);
                words().forEach((w) => {
                    const re = new RegExp('(' + esc(w).replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'ig');
                    html = html.replace(re, '<mark>$1</mark>');
                });
                return html;
            };

            function render(data) {
                items = data.items;
                active = -1;
                const q = $input.val().trim();
                if (!items.length) {
                    $panel.html(`<div class="fp-empty"><i class="bi bi-search"></i>No product matches “${esc(q)}”.<div class="small">Try the code, part of the name, a vendor or a category.</div></div>`);
                } else {
                    const head = q ? `${data.total} match${data.total === 1 ? '' : 'es'}` : 'Best sellers not featured yet';
                    $panel.html(`<div class="fp-head">${head}</div>` + items.map((p, i) => `
                        <div class="fp-item${p.is_featured ? ' is-featured' : ''}" role="option" data-index="${i}" id="fp-opt-${i}">
                            <img src="${esc(p.thumb)}" alt="" loading="lazy">
                            <div class="fp-info">
                                <div class="fp-name">${mark(p.name)} <span class="fp-code">${mark(p.code)}</span></div>
                                <div class="fp-meta">
                                    <span title="Category"><i class="bi bi-collection"></i>${mark(p.category || '—')}${p.subcategory ? ' › ' + mark(p.subcategory) : ''}</span>
                                    <span title="Vendor"><i class="bi bi-shop"></i>${mark(p.vendor || 'No vendor')}</span>
                                    ${p.brand ? `<span title="Brand"><i class="bi bi-award"></i>${mark(p.brand)}</span>` : ''}
                                    ${p.is_active ? '' : '<span class="badge badge-soft-secondary">Hidden</span>'}
                                    ${p.in_stock ? '' : '<span class="badge badge-soft-danger">Out of stock</span>'}
                                </div>
                            </div>
                            <div class="fp-prices">
                                <div><span>Unit</span>${esc(p.unit_price)}</div>
                                <div><span>Discount</span>${p.discount_price ? `<b>${esc(p.discount_price)}</b>` : '—'}</div>
                            </div>
                            <div class="fp-orders" title="Orders that include this product"><b>${p.orders}</b>orders</div>
                            <div class="fp-action">
                                ${p.is_featured
                                    ? '<span class="badge badge-soft-success py-2 px-3"><i class="bi bi-star-fill me-1"></i>Featured</span>'
                                    : `<button type="button" class="btn btn-sm btn-primary" data-add="${esc(p.uid)}"><i class="bi bi-star me-1"></i>Add</button>`}
                            </div>
                        </div>`).join('')
                        + (data.total > items.length ? `<div class="fp-foot">Showing ${items.length} of ${data.total} — type more to narrow down.</div>` : ''));
                }
                open();
            }

            const open = () => { $panel.prop('hidden', false); $input.attr('aria-expanded', 'true'); };
            // Closing also cancels a pending keystroke timer and any request still in flight, so nothing reopens it later.
            const close = () => { clearTimeout(timer); seq++; $panel.prop('hidden', true); $input.attr('aria-expanded', 'false').removeAttr('aria-activedescendant'); active = -1; };

            function search() {
                const q = $input.val().trim();
                if (cache.has(q)) return render(cache.get(q));
                const mine = ++seq;
                $panel.html('<div class="fp-loading"><span class="spinner-border spinner-border-sm me-2"></span>Searching…</div>');
                open();
                $.getJSON(searchUrl, { q }).done((data) => {
                    cache.set(q, data);
                    if (mine === seq) render(data); // ignore late answers to older keystrokes
                }).fail(() => { if (mine === seq) $panel.html('<div class="fp-empty text-danger">Search failed. Please try again.</div>'); });
            }

            $input.on('input', () => { clearTimeout(timer); timer = setTimeout(search, 200); })
                // Opens on click or typing only — never on focus, which dialogs also restore programmatically.
                .on('click', () => { if ($panel.prop('hidden')) search(); })
                .on('keydown', (e) => {
                    // Escape in a search box would also clear it (and fire input → search); just close instead.
                    if (e.key === 'Escape') { e.preventDefault(); close(); return; }
                    if (e.key === 'ArrowDown' && $panel.prop('hidden')) { e.preventDefault(); search(); return; }
                    if ($panel.prop('hidden') || !items.length) return;
                    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                        e.preventDefault();
                        active = (active + (e.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length;
                        const $opt = $panel.find('.fp-item').removeClass('active').eq(active).addClass('active');
                        $input.attr('aria-activedescendant', $opt.attr('id'));
                        $opt[0].scrollIntoView({ block: 'nearest' });
                    } else if (e.key === 'Enter') {
                        e.preventDefault();
                        if (active >= 0) $panel.find('.fp-item').eq(active).find('[data-add]').trigger('click');
                    }
                });
            $panel.on('mouseenter', '.fp-item', function () { active = +this.dataset.index; $panel.find('.fp-item').removeClass('active'); $(this).addClass('active'); });
            $(document).on('click', (e) => { if (document.contains(e.target) && !$picker[0].contains(e.target)) close(); });

            function applyList(res, highlightUid) {
                $('#featuredBody').html(res.rows);
                $('#featuredCount').text(res.count);
                $('#fpLive').text(`${res.live} shown on home`);
                $('#fpFilter').trigger('input');
                if (highlightUid) $(`#featuredBody tr[data-uid="${highlightUid}"]`).addClass('row-flash');
                cache.clear(); // featured flags changed
            }

            // Drag & drop order (whole list, saved at once). Disabled while the list is filtered.
            const body = document.getElementById('featuredBody');
            const sortable = window.Sortable ? Sortable.create(body, {
                handle: '.fp-handle', draggable: 'tr[data-uid]', animation: 150, ghostClass: 'sortable-ghost',
                onEnd(evt) {
                    if (evt.oldIndex === evt.newIndex) return;
                    const ids = Array.from(body.querySelectorAll('tr[data-uid]')).map((tr) => tr.dataset.uid);
                    sortable.option('disabled', true);
                    $.ajax({ url: body.dataset.sortUrl, method: 'POST', contentType: 'application/json', data: JSON.stringify({ ids }) })
                        .done((res) => { applyList(res); notify(res.message); })
                        .fail(() => { notify('Could not save the order — the list was reloaded', 'error'); setTimeout(() => location.reload(), 1200); })
                        .always(() => sortable.option('disabled', !!$('#fpFilter').val().trim()));
                },
            }) : null;

            // On / off switch: hide from (or show again on) the home page, keeping the product in the list.
            $(document).on('change', '[data-featured-toggle]', function () {
                const el = this;
                el.disabled = true;
                $.ajax({ url: el.dataset.featuredToggle, method: 'PATCH' })
                    .done((res) => { applyList(res, $(el).closest('tr').data('uid')); notify(res.message); })
                    .fail(() => { el.checked = !el.checked; el.disabled = false; notify('Could not update the product', 'error'); });
            });

            // Add from the dropdown
            $panel.on('click', '[data-add]', function (e) {
                e.stopPropagation();
                const $btn = $(this);
                const uid = $btn.data('add');
                Busy.start(this, 'Adding…');
                $.post(addUrl(uid)).done((res) => {
                    applyList(res, uid);
                    $btn.replaceWith('<span class="badge badge-soft-success py-2 px-3"><i class="bi bi-star-fill me-1"></i>Featured</span>');
                    const item = items.find((p) => p.uid === uid);
                    if (item) item.is_featured = true;
                    notify(res.message);
                }).fail(() => { Busy.stop(this); notify('Could not add the product', 'error'); });
            });

            // Remove from the list
            $(document).on('click', '[data-unfeature]', function () {
                const $btn = $(this);
                Swal.fire({
                    title: 'Remove from featured?',
                    text: `“${$btn.data('name')}” will be taken out of this list. To only hide it from the home page, use the switch instead.`,
                    icon: 'question', showCancelButton: true, confirmButtonText: 'Remove', confirmButtonColor: '#dc2626', reverseButtons: true,
                }).then((r) => {
                    if (!r.isConfirmed) return;
                    Busy.start($btn[0]);
                    $.ajax({ url: $btn.data('unfeature'), method: 'DELETE' })
                        .done((res) => { applyList(res); notify(res.message); })
                        .fail(() => { Busy.stop($btn[0]); notify('Could not remove the product', 'error'); });
                });
            });

            // Filter the featured list (client side, same fields as the search)
            $('#fpFilter').on('input', function () {
                const ws = this.value.trim().toLowerCase().split(/\s+/).filter(Boolean);
                let shown = 0;
                $('#featuredBody tr[data-uid]').each(function () {
                    const ok = ws.every((w) => this.dataset.search.includes(w));
                    this.hidden = !ok;
                    shown += ok;
                });
                $('#fpNoMatch').toggleClass('d-none', !ws.length || shown > 0);
                // Reordering a filtered list would hide where rows really go.
                if (sortable) sortable.option('disabled', ws.length > 0);
                $('#fpSortOff').toggleClass('d-none', !ws.length || !shown);
                $('.fp-table').toggleClass('sort-off', ws.length > 0);
            });
        })(jQuery);
    </script>
@endpush
