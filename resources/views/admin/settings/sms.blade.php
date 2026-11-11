{{-- Settings → SMS tab: provider (BulkSMSBD / custom / log), OTP text, balance, test SMS. Part of the settings form. --}}
@php
    $smsService = app(\App\Services\SmsService::class);
    $smsDriver = $smsService->driver(); // an old "HTTP" + bulksmsbd.net setup shows as BulkSMSBD
    $smsStatus = $smsService->lastStatus();
    $smsBalance = $smsService->lastBalance();
    $smsTemplate = $v('sms_otp_template') ?: \App\Services\SmsService::DEFAULT_TEMPLATE;
    $smsBrand = $v('sms_brand') ?: setting('site_name', config('app.name'));
@endphp
<div class="tab-pane fade" id="sms">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5><i class="bi bi-chat-dots me-1"></i> SMS gateway for OTP</h5>
            @if($smsDriver === 'bulksmsbd')
                <span class="badge badge-soft-success">BulkSMSBD</span>
            @elseif($smsDriver === 'http')
                <span class="badge badge-soft-primary">Custom provider</span>
            @else
                <span class="badge badge-soft-warning">Log only — no SMS is sent</span>
            @endif
        </div>
        <div class="card-body row g-3">
            <div class="col-md-4">
                <label class="form-label" for="sms_driver">Provider</label>
                <select name="sms_driver" id="sms_driver" class="form-select">
                    <option value="bulksmsbd" @selected($smsDriver === 'bulksmsbd')>BulkSMSBD (live)</option>
                    <option value="http" @selected($smsDriver === 'http')>Other provider — URL template</option>
                    <option value="log" @selected(! in_array($smsDriver, ['bulksmsbd', 'http'], true))>Log only (testing)</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="sms_api_key">API key</label>
                <input type="text" name="sms_api_key" id="sms_api_key" class="form-control font-monospace" value="{{ $v('sms_api_key') }}" placeholder="Your provider API key" maxlength="255" autocomplete="off" spellcheck="false">
                <div class="form-text" data-sms-show="bulksmsbd">BulkSMSBD panel → <em>API &amp; Docs</em>.</div>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="sms_sender_id">Sender ID</label>
                <input type="text" name="sms_sender_id" id="sms_sender_id" class="form-control font-monospace" value="{{ $v('sms_sender_id') }}" placeholder="e.g. 8809617XXXXXX" maxlength="30">
            </div>

            <div class="col-12" data-sms-show="http">
                <label class="form-label" for="sms_api_url">API URL template</label>
                <input type="text" name="sms_api_url" id="sms_api_url" class="form-control font-monospace small @error('sms_api_url') is-invalid @enderror" value="{{ $v('sms_api_url') }}" maxlength="500" placeholder="https://provider.com/api/send?key={api_key}&to={number}&from={sender_id}&text={message}">
                @error('sms_api_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Sent as GET. Placeholders: <code>{api_key}</code> <code>{sender_id}</code> <code>{number}</code> (880…) <code>{message}</code>.</div>
            </div>
            <div class="col-12" data-sms-show="bulksmsbd">
                <div class="small text-muted"><i class="bi bi-info-circle me-1"></i>Sent with <strong>POST</strong> to <code>{{ config('shop.sms.bulksmsbd.send_url') }}</code>. Numbers are sent as 8801XXXXXXXXX. If BulkSMSBD asks for it, whitelist your <strong>server's IP address</strong> in their panel.</div>
            </div>

            <div class="col-12"><hr class="my-1"></div>
            <div class="col-md-4">
                <label class="form-label" for="sms_brand">Brand name in the SMS</label>
                <input type="text" name="sms_brand" id="sms_brand" class="form-control @error('sms_brand') is-invalid @enderror" value="{{ $v('sms_brand') }}" placeholder="{{ setting('site_name', config('app.name')) }}" maxlength="40">
                @error('sms_brand')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Use the exact name approved by your SMS provider. Empty = site name.</div>
            </div>
            <div class="col-md-8">
                <label class="form-label" for="sms_otp_template">OTP message</label>
                <input type="text" name="sms_otp_template" id="sms_otp_template" class="form-control font-monospace @error('sms_otp_template') is-invalid @enderror" value="{{ $v('sms_otp_template') }}" placeholder="{{ \App\Services\SmsService::DEFAULT_TEMPLATE }}" maxlength="160">
                @error('sms_otp_template')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text"><code>{brand}</code> = brand name, <code>{otp}</code> = the code. BulkSMSBD's required format is <code>Your {brand} OTP is {otp}</code> — keep it unless they approve another. Empty = that format.</div>
                <div class="small mt-1">Customers get: <strong id="smsPreview">{{ strtr($smsTemplate, ['{brand}' => $smsBrand, '{otp}' => '123456']) }}</strong></div>
            </div>

            <div class="col-12"><hr class="my-1"></div>
            <div class="col-md-5">
                <label class="form-label">Balance</label>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <button type="button" class="btn btn-soft-primary" id="smsBalanceBtn" data-url="{{ route('admin.settings.sms-balance') }}" @disabled($smsDriver !== 'bulksmsbd' || ! $v('sms_api_key'))><i class="bi bi-wallet2 me-1"></i> Check balance</button>
                    <span class="small fw-semibold" id="smsBalanceResult" role="status">
                        @if($smsBalance)৳{{ number_format($smsBalance['balance'], 2) }} <span class="text-muted fw-normal">({{ \Illuminate\Support\Carbon::parse($smsBalance['at'])->diffForHumans() }})</span>@endif
                    </span>
                </div>
                <div class="form-text">@if($smsDriver !== 'bulksmsbd') Works with BulkSMSBD. @elseif(! $v('sms_api_key')) Save the API key first. @else Uses the saved API key. @endif</div>
            </div>
            <div class="col-md-7">
                <label class="form-label" for="smsTestMobile">Send a test SMS</label>
                <div class="input-group">
                    <input type="tel" id="smsTestMobile" class="form-control" placeholder="01XXXXXXXXX" maxlength="14" data-no-validate>
                    <button type="button" class="btn btn-soft-primary" id="smsTestBtn" data-url="{{ route('admin.settings.sms-test') }}"><i class="bi bi-send me-1"></i> Send test</button>
                </div>
                <div class="small mt-1" id="smsTestResult" role="status"></div>
                <div class="form-text">Sends the OTP message with a sample code, using the <strong>saved</strong> settings — save changes first.</div>
            </div>
            @if($smsStatus)
                <div class="col-12">
                    <div class="p-2 px-3 rounded-3 small {{ $smsStatus['ok'] ? 'bg-soft-success' : 'bg-soft-danger' }}" id="smsLastStatus">
                        <i class="bi {{ $smsStatus['ok'] ? 'bi-check-circle' : 'bi-exclamation-triangle' }} me-1"></i>
                        <strong>Last SMS</strong> {{ \Illuminate\Support\Carbon::parse($smsStatus['at'])->diffForHumans() }} to {{ $smsStatus['to'] }}: {{ $smsStatus['message'] }}
                    </div>
                </div>
            @endif
            <div class="col-12" data-sms-show="log">
                <div class="alert alert-info border-0 small mb-0" style="border-radius: 10px"><i class="bi bi-info-circle me-1"></i> With <strong>Log only</strong>, codes are written to <code>storage/logs/laravel.log</code>, and shown on the verify page while <code>APP_DEBUG=true</code>.</div>
            </div>
        </div>
    </div>
</div>

@push('page-scripts')
    <script>
        (function () {
            const driver = document.getElementById('sms_driver');
            if (!driver) return;
            const toggle = () => document.querySelectorAll('[data-sms-show]').forEach((el) => el.classList.toggle('d-none', el.dataset.smsShow !== driver.value));
            driver.addEventListener('change', toggle);
            toggle();

            const tpl = document.getElementById('sms_otp_template'), brand = document.getElementById('sms_brand'), preview = document.getElementById('smsPreview');
            const render = () => {
                preview.textContent = (tpl.value.trim() || @json(\App\Services\SmsService::DEFAULT_TEMPLATE))
                    .split('{brand}').join(brand.value.trim() || brand.placeholder).split('{otp}').join('123456');
            };
            tpl.addEventListener('input', render);
            brand.addEventListener('input', render);

            const run = (btn, out, data) => {
                btn.disabled = true;
                out.className = 'small text-muted';
                out.textContent = 'Please wait…';
                $.post(btn.dataset.url, data)
                    .done((r) => { out.className = 'small text-success fw-semibold'; out.innerHTML = '<i class="bi bi-check-circle me-1"></i>' + $('<div>').text(r.message).html(); })
                    .fail((x) => {
                        const j = x.responseJSON || {};
                        const msg = (j.errors && Object.values(j.errors)[0][0]) || j.message || 'Could not complete the request.';
                        out.className = 'small text-danger'; out.innerHTML = '<i class="bi bi-x-circle me-1"></i>' + $('<div>').text(msg).html();
                    })
                    .always(() => { btn.disabled = false; });
            };
            const balBtn = document.getElementById('smsBalanceBtn');
            balBtn.addEventListener('click', () => run(balBtn, document.getElementById('smsBalanceResult'), {}));
            const testBtn = document.getElementById('smsTestBtn');
            testBtn.addEventListener('click', () => run(testBtn, document.getElementById('smsTestResult'), { mobile: document.getElementById('smsTestMobile').value.trim() }));
        })();
    </script>
@endpush
