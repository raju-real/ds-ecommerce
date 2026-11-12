{{-- Courier success ratio of this customer (button next to "Mobile"): loads when opened, from
     Admin\OrderController@courierRatio → CourierRatioService (cached 6 hours; "Check again" asks the service now). --}}
<div class="modal fade" id="courierRatioModal" tabindex="-1" aria-labelledby="courierRatioTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="courierRatioTitle"><i class="bi bi-shield-check text-primary me-1"></i> Courier success ratio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="small text-muted mb-3">{{ $order->customer_name }} · <span class="font-monospace">{{ $order->mobile }}</span></div>
                <div id="courierRatioBody" data-url="{{ route('admin.orders.courier-ratio', $order->unique_id) }}"></div>
            </div>
            <div class="modal-footer justify-content-between">
                <a href="{{ route('admin.settings') }}#courier" class="small text-muted">Settings → Courier check</a>
                <button type="button" class="btn btn-soft-primary btn-sm" id="courierRatioRefresh"><i class="bi bi-arrow-repeat me-1"></i> Check again</button>
            </div>
        </div>
    </div>
</div>

@push('page-scripts')
    <script>
        (function () {
            const modal = document.getElementById('courierRatioModal');
            if (!modal) return;
            const body = document.getElementById('courierRatioBody');
            let loaded = false;
            const load = (fresh) => {
                body.innerHTML = '<div class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2"></span>Checking courier history…</div>';
                $.getJSON(body.dataset.url + (fresh ? '?fresh=1' : ''))
                    .done((r) => { renderCourierRatio(body, r); loaded = r.ok; })
                    .fail((x) => renderCourierRatio(body, { ok: false, message: (x.responseJSON && x.responseJSON.message) || 'Could not check this number.' }));
            };
            modal.addEventListener('show.bs.modal', () => { if (!loaded) load(false); });
            document.getElementById('courierRatioRefresh').addEventListener('click', () => load(true));
            // Tooltip on the small button next to "Mobile"
            document.querySelectorAll('.label-tool[title]').forEach((el) => bootstrap.Tooltip.getOrCreateInstance(el));
        })();
    </script>
@endpush
