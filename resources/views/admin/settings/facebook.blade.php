{{-- Settings → Facebook Pixel tab (#pixel — "#facebook" is the Facebook page link input): Pixel ID, Conversions API (server events), test code, status. Part of the settings form. --}}
@php
    $fbPixel = app(\App\Services\FacebookPixel::class);
    $hasToken = filled($s['facebook_capi_token'] ?? null);
    $tokenReadable = $hasToken && $fbPixel->token() !== null;
    $fbStatus = $fbPixel->lastStatus();
    // Why server events are not running (null = running).
    $serverOff = match (true) {
        ! $fbPixel->pixelId() => 'add the Pixel ID',
        ! $hasToken => 'paste an access token',
        ! $tokenReadable => 'paste the access token again',
        ($s['facebook_capi_enabled'] ?? null) !== '1' => 'switch on "Send server events"',
        default => null,
    };
@endphp
<div class="tab-pane fade" id="pixel">
    <input type="hidden" name="facebook_present" value="1">
    <div class="card mb-4">
        <div class="card-header"><h5><i class="bi bi-facebook me-1 text-primary"></i> Facebook Pixel</h5></div>
        <div class="card-body row g-3">
            <div class="col-md-6">
                <label class="form-label" for="facebook_pixel_id">Pixel ID</label>
                <input type="text" name="facebook_pixel_id" id="facebook_pixel_id" class="form-control @error('facebook_pixel_id') is-invalid @enderror" value="{{ $v('facebook_pixel_id') }}" placeholder="e.g. 123456789012345" minlength="10" maxlength="20" pattern="[0-9]+" inputmode="numeric" data-digits data-msg-pattern="Pixel ID contains digits only.">
                @error('facebook_pixel_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Events Manager → Data sources → your Pixel → the number under its name. Empty = Pixel off.</div>

                <label class="form-label mt-3" for="facebook_domain_verification">Domain verification code</label>
                <input type="text" name="facebook_domain_verification" id="facebook_domain_verification" class="form-control font-monospace @error('facebook_domain_verification') is-invalid @enderror" value="{{ $v('facebook_domain_verification') }}" placeholder='e.g. gkft31iudg72d1q2vy2gcgkbcczr4a' maxlength="200" spellcheck="false" autocomplete="off">
                @error('facebook_domain_verification')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Business Settings → Brand safety → Domains → <em>Meta-tag verification</em>. Paste the code or the whole <code>&lt;meta name="facebook-domain-verification" …&gt;</code> tag; it is added to the home page.</div>
            </div>
            <div class="col-md-6">
                <div class="p-3 rounded-3 bg-light small h-100">
                    <div class="fw-semibold mb-1">Sent automatically</div>
                    <span class="badge badge-soft-secondary">PageView</span>
                    <span class="badge badge-soft-primary">ViewContent</span>
                    <span class="badge badge-soft-primary">Search</span>
                    <span class="badge badge-soft-primary">AddToCart</span>
                    <span class="badge badge-soft-primary">InitiateCheckout</span>
                    <span class="badge badge-soft-success">Purchase</span>
                    <div class="text-muted mt-1">With product codes, prices and BDT. Purchase is sent once per order, when the customer verifies the OTP.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5><i class="bi bi-hdd-network me-1"></i> Conversions API (server events)</h5>
            @if($serverOff === null)
                <span class="badge badge-soft-success"><i class="bi bi-check-circle me-1"></i>Active</span>
            @else
                <span class="badge badge-soft-warning" id="fbServerOff"><i class="bi bi-pause-circle me-1"></i>Off — {{ $serverOff }}, then save</span>
            @endif
        </div>
        <div class="card-body row g-3">
            <div class="col-12">
                <div class="small text-muted">Your server also sends every event straight to Facebook, so ad blockers and iPhone privacy settings do not lose them. Each event has the same ID in the browser and the server, so Facebook counts it once. Customer phone and name are hashed (SHA-256) before sending.</div>
            </div>
            <div class="col-12">
                <input type="hidden" name="facebook_capi_enabled" value="0">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" name="facebook_capi_enabled" id="facebook_capi_enabled" value="1" @checked($v('facebook_capi_enabled') === '1')>
                    <label class="form-check-label fw-semibold" for="facebook_capi_enabled">Send server events</label>
                </div>
                <div class="form-text">Needs the Pixel ID and an access token.</div>
            </div>
            <div class="col-md-7">
                <label class="form-label" for="facebook_capi_token">Access token</label>
                <input type="password" name="facebook_capi_token" id="facebook_capi_token" class="form-control font-monospace @error('facebook_capi_token') is-invalid @enderror" maxlength="1000" autocomplete="new-password" spellcheck="false"
                       placeholder="{{ $hasToken ? '•••••••••••• saved — leave empty to keep' : 'Paste the token from Events Manager' }}">
                @error('facebook_capi_token')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Events Manager → your Pixel → Settings → Conversions API → <em>Generate access token</em>. Stored encrypted.</div>
                @if($hasToken && ! $tokenReadable)
                    <div class="text-danger small mt-1"><i class="bi bi-exclamation-triangle me-1"></i>The saved token cannot be read (the app key changed). Paste it again.</div>
                @endif
                @if($hasToken)
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="remove_facebook_capi_token" value="1" id="remove_facebook_capi_token">
                        <label class="form-check-label small" for="remove_facebook_capi_token">Remove the saved token</label>
                    </div>
                @endif
            </div>
            <div class="col-md-5">
                <label class="form-label" for="facebook_test_event_code">Test event code <span class="text-muted fw-normal">(only while testing)</span></label>
                <input type="text" name="facebook_test_event_code" id="facebook_test_event_code" class="form-control @error('facebook_test_event_code') is-invalid @enderror" value="{{ $v('facebook_test_event_code') }}" placeholder="e.g. TEST12345" maxlength="30" pattern="[A-Za-z0-9]+" data-msg-pattern="Letters and digits only.">
                @error('facebook_test_event_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Events Manager → Test events. While set, server events appear there. <strong>Clear it when you are done.</strong></div>
            </div>
            <div class="col-12 d-flex flex-wrap align-items-center gap-2">
                <button type="button" class="btn btn-soft-primary" id="fbTestBtn" data-url="{{ route('admin.settings.facebook-test') }}" @disabled(! $fbPixel->pixelId() || ! $tokenReadable)>
                    <i class="bi bi-send me-1"></i> Send test event
                </button>
                <span class="small text-muted" id="fbTestHint">@if(! $fbPixel->pixelId() || ! $tokenReadable) Save the Pixel ID and token first. @else Uses the saved settings and the test code. @endif</span>
                <span class="small" id="fbTestResult" role="status"></span>
            </div>
            @if($fbStatus)
                <div class="col-12">
                    <div class="p-2 px-3 rounded-3 small {{ $fbStatus['ok'] ? 'bg-soft-success' : 'bg-soft-danger' }}" id="fbLastStatus">
                        <i class="bi {{ $fbStatus['ok'] ? 'bi-check-circle' : 'bi-exclamation-triangle' }} me-1"></i>
                        <strong>Last send</strong> {{ \Illuminate\Support\Carbon::parse($fbStatus['at'])->diffForHumans() }}
                        ({{ $fbStatus['events'] }}{{ $fbStatus['test'] ? ', test' : '' }}): {{ $fbStatus['message'] }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@push('page-scripts')
    <script>
        (function () {
            const btn = document.getElementById('fbTestBtn');
            if (!btn) return;
            const out = document.getElementById('fbTestResult');
            btn.addEventListener('click', () => {
                btn.disabled = true;
                out.className = 'small text-muted';
                out.textContent = 'Sending…';
                $.post(btn.dataset.url, { code: document.getElementById('facebook_test_event_code').value.trim() })
                    .done((r) => { out.className = 'small text-success fw-semibold'; out.innerHTML = '<i class="bi bi-check-circle me-1"></i>' + $('<div>').text(r.message).html() + ' Check Events Manager → Test events.'; })
                    .fail((x) => { out.className = 'small text-danger'; out.innerHTML = '<i class="bi bi-x-circle me-1"></i>' + $('<div>').text((x.responseJSON && (x.responseJSON.message)) || 'Could not send.').html(); })
                    .always(() => { btn.disabled = false; });
            });
        })();
    </script>
@endpush
