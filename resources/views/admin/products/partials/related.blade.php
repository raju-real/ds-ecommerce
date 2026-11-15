{{-- Related products ("You may need" on checkout): search by name or code, add, reorder, remove. Saved with the product
     as related[] = unique ids in order (Admin\ProductController::syncRelated). Expects $product, $related. --}}
@php
    $relatedItems = collect(old('related_present') ? [] : $related)->map(fn ($p) => [
        'uid' => $p->unique_id, 'name' => $p->name, 'code' => $p->code, 'thumb' => img_url($p->thumbnail),
        'price' => money($p->discount_price ?: $p->unit_price), 'active' => (bool) $p->is_active,
    ])->values();
@endphp
<div class="card mb-4" id="relatedCard">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5><i class="bi bi-diagram-3 me-1 text-primary"></i> Related products <span class="badge badge-soft-secondary ms-1" data-related-count>{{ $relatedItems->count() }}</span></h5>
        <span class="small text-muted">Shown as <strong>“You may need”</strong> on checkout when this product is in the cart</span>
    </div>
    <div class="card-body">
        <input type="hidden" name="related_present" value="1">
        <div class="position-relative mb-3">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="search" class="form-control" id="relatedSearch" placeholder="Search products by name or code…" autocomplete="off" data-no-validate
                       data-url="{{ route('admin.products.featured.search') }}" data-self="{{ $product->unique_id }}" aria-label="Search products to add">
            </div>
            <div class="related-results list-group shadow d-none" id="relatedResults"></div>
        </div>
        <div id="relatedList" class="related-list" data-max="20"></div>
        <div class="small text-muted mt-2" id="relatedEmpty">No related products yet. Search above and click a product to add it (up to 20). Only active, in-stock ones are shown to customers.</div>
    </div>
</div>

@push('styles')
    <style>
        .related-results { position: absolute; left: 0; right: 0; top: calc(100% + 4px); z-index: 30; max-height: 320px; overflow: auto; border-radius: 10px; }
        .related-results .list-group-item { display: flex; align-items: center; gap: .6rem; cursor: pointer; }
        .related-results img, .related-row img { width: 40px; height: 40px; object-fit: cover; border-radius: 8px; flex: 0 0 40px; }
        .related-row { display: flex; align-items: center; gap: .6rem; padding: .5rem .6rem; border: 1px solid #e5e7eb; border-radius: 10px; margin-bottom: .4rem; background: #fff; }
        .related-row .grow { flex: 1 1 auto; min-width: 0; }
        .related-row .nm { font-weight: 600; font-size: .88rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .related-row .btn { padding: .15rem .45rem; }
    </style>
@endpush

@push('page-scripts')
    <script>
        (function () {
            const input = document.getElementById('relatedSearch');
            if (!input) return;
            const results = document.getElementById('relatedResults'), list = document.getElementById('relatedList');
            const empty = document.getElementById('relatedEmpty'), count = document.querySelector('[data-related-count]');
            const max = +list.dataset.max, self = input.dataset.self;
            const e = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
            const chosen = () => [...list.querySelectorAll('[data-uid]')].map((r) => r.dataset.uid);

            function refresh() {
                const n = chosen().length;
                count.textContent = n;
                empty.classList.toggle('d-none', n > 0);
                input.disabled = n >= max;
                input.placeholder = n >= max ? 'Maximum ' + max + ' related products' : 'Search products by name or code…';
            }
            function add(p) {
                if (chosen().includes(p.uid) || chosen().length >= max || p.uid === self) return;
                const row = document.createElement('div');
                row.className = 'related-row'; row.dataset.uid = p.uid;
                row.innerHTML = `<input type="hidden" name="related[]" value="${e(p.uid)}">
                    <img src="${e(p.thumb)}" alt="">
                    <div class="grow"><div class="nm">${e(p.name)}</div><div class="small text-muted">${e(p.code)} · ${e(p.price)}${p.active === false ? ' · <span class="text-danger">inactive</span>' : ''}</div></div>
                    <button type="button" class="btn btn-light btn-sm" data-move="-1" title="Move up" aria-label="Move up"><i class="bi bi-arrow-up"></i></button>
                    <button type="button" class="btn btn-light btn-sm" data-move="1" title="Move down" aria-label="Move down"><i class="bi bi-arrow-down"></i></button>
                    <button type="button" class="btn btn-soft-danger btn-sm" data-remove title="Remove" aria-label="Remove"><i class="bi bi-x-lg"></i></button>`;
                list.appendChild(row);
                refresh();
            }
            @json($relatedItems).forEach(add);

            let timer = null, last = '';
            input.addEventListener('input', () => {
                clearTimeout(timer);
                const q = input.value.trim();
                if (q.length < 2) { results.classList.add('d-none'); return; }
                timer = setTimeout(() => {
                    last = q;
                    $.getJSON(input.dataset.url, { q }, (r) => {
                        if (q !== last) return;
                        const items = (r.items || []).filter((p) => p.uid !== self);
                        results.innerHTML = items.length ? items.map((p) => {
                            const price = p.discount_price || p.unit_price;
                            const taken = chosen().includes(p.uid);
                            return `<button type="button" class="list-group-item list-group-item-action ${taken ? 'disabled' : ''}" data-pick='${e(JSON.stringify({ uid: p.uid, name: p.name, code: p.code, thumb: p.thumb, price }))}'>
                                <img src="${e(p.thumb)}" alt=""><span class="flex-grow-1 min-w-0"><span class="d-block text-truncate fw-semibold">${e(p.name)}</span><span class="small text-muted">${e(p.code)} · ${e(price)}</span></span>${taken ? '<i class="bi bi-check2 text-success"></i>' : '<i class="bi bi-plus-lg text-primary"></i>'}</button>`;
                        }).join('') : '<div class="list-group-item small text-muted">No products found.</div>';
                        results.classList.remove('d-none');
                    });
                }, 250);
            });
            results.addEventListener('click', (ev) => {
                const b = ev.target.closest('[data-pick]');
                if (!b || b.classList.contains('disabled')) return;
                add(JSON.parse(b.dataset.pick));
                b.classList.add('disabled'); b.querySelector('.bi').className = 'bi bi-check2 text-success';
            });
            list.addEventListener('click', (ev) => {
                const row = ev.target.closest('.related-row');
                if (!row) return;
                if (ev.target.closest('[data-remove]')) { row.remove(); refresh(); return; }
                const mv = ev.target.closest('[data-move]');
                if (mv) {
                    const sib = +mv.dataset.move < 0 ? row.previousElementSibling : row.nextElementSibling;
                    if (sib) +mv.dataset.move < 0 ? list.insertBefore(row, sib) : list.insertBefore(sib, row);
                }
            });
            input.addEventListener('keydown', (ev) => { if (ev.key === 'Enter') ev.preventDefault(); }); // never submits the product form
            document.addEventListener('click', (ev) => { if (!ev.target.closest('#relatedCard .position-relative')) results.classList.add('d-none'); });
            refresh();
        })();
    </script>
@endpush
