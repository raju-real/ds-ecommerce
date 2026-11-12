{{-- Settings → Courier check: the customer success-ratio check (same as the vendor panel), used from the order page. --}}
@php
    $courierSvc = app(\App\Services\CourierRatioService::class);
    $hasCourierToken = filled($s['courier_check_token'] ?? null);
    $courierTokenOk = $hasCourierToken && $courierSvc->token() !== null;
@endphp
<div class="tab-pane fade" id="courier">
    <input type="hidden" name="courier_present" value="1">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5><i class="bi bi-truck me-1"></i> Courier success ratio check</h5>
            @if($courierTokenOk)
                <span class="badge badge-soft-success"><i class="bi bi-check-circle me-1"></i>Ready</span>
            @else
                <span class="badge badge-soft-secondary">Not set up</span>
            @endif
        </div>
        <div class="card-body row g-3">
            <div class="col-12">
                <div class="small text-muted">Shows how many parcels a customer received or cancelled with each courier (Steadfast, Pathao, RedX, Paperfly …) — open it from an order with the <i class="bi bi-shield-check"></i> button next to <strong>Mobile</strong>. Uses the same check as your vendor panel.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="courier_check_url">API address</label>
                <input type="url" name="courier_check_url" id="courier_check_url" class="form-control font-monospace small @error('courier_check_url') is-invalid @enderror" value="{{ $v('courier_check_url') }}" placeholder="{{ \App\Services\CourierRatioService::DEFAULT_URL }}" maxlength="255">
                @error('courier_check_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Empty = {{ \App\Services\CourierRatioService::DEFAULT_URL }}</div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="courier_check_token">Authorization token</label>
                <input type="password" name="courier_check_token" id="courier_check_token" class="form-control font-monospace @error('courier_check_token') is-invalid @enderror" maxlength="4000" autocomplete="new-password" spellcheck="false"
                       placeholder="{{ $hasCourierToken ? '•••••••••••• saved — leave empty to keep' : 'Bearer eyJ…  (from the vendor panel)' }}">
                @error('courier_check_token')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">In the vendor panel open the ratio check, press <kbd>F12</kbd> → Network → the <em>check-customer</em> request → Request headers → copy <strong>Authorization</strong>. Stored encrypted.</div>
                @if($hasCourierToken && ! $courierTokenOk)<div class="text-danger small mt-1"><i class="bi bi-exclamation-triangle me-1"></i>The saved token cannot be read (the app key changed). Paste it again.</div>@endif
                @if($hasCourierToken)
                    <div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="remove_courier_check_token" value="1" id="remove_courier_check_token"><label class="form-check-label small" for="remove_courier_check_token">Remove the saved token</label></div>
                @endif
            </div>
            <div class="col-md-7">
                <label class="form-label" for="courierTestMobile">Test with a number</label>
                <div class="input-group">
                    <input type="tel" id="courierTestMobile" class="form-control" placeholder="01XXXXXXXXX" maxlength="11" data-no-validate>
                    <button type="button" class="btn btn-soft-primary" id="courierTestBtn" data-url="{{ route('admin.settings.courier-test') }}" @disabled(! $courierTokenOk)><i class="bi bi-search me-1"></i> Check</button>
                </div>
                <div class="form-text">@if(! $courierTokenOk) Save the token first. @else Uses the saved address and token. @endif</div>
            </div>
            <div class="col-12"><div id="courierTestResult"></div></div>
        </div>
    </div>
</div>

@push('page-scripts')
    <script>
        (function () {
            const btn = document.getElementById('courierTestBtn');
            if (!btn) return;
            btn.addEventListener('click', () => {
                const out = document.getElementById('courierTestResult');
                btn.disabled = true;
                out.innerHTML = '<div class="small text-muted"><span class="spinner-border spinner-border-sm me-1"></span>Checking…</div>';
                $.post(btn.dataset.url, { mobile: document.getElementById('courierTestMobile').value.trim() })
                    .done((r) => renderCourierRatio(out, r))
                    .fail((x) => { const j = x.responseJSON || {}; renderCourierRatio(out, j.rows ? j : { ok: false, message: (j.errors && Object.values(j.errors)[0][0]) || j.message || 'Could not check.' }); })
                    .always(() => { btn.disabled = false; });
            });
        })();
    </script>
@endpush
