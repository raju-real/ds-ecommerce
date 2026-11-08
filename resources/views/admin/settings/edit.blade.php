@extends('admin.layouts.app')

@section('title', 'Site settings')

@php
    $v = fn ($key, $default = '') => old($key, $s[$key] ?? $default);
    $images = [
        'logo' => ['Logo (dark, for header)', 'logo', 'Used in the store header on a white background.'],
        'logo_light' => ['Logo (light, for footer & admin)', 'logo', 'White version on a transparent background.'],
        'favicon' => ['Favicon', 'favicon', 'Browser tab icon, saved as PNG.'],
        'og_image' => ['Social share image', 'og_image', 'Shown when links are shared on Facebook, WhatsApp, etc.'],
    ];
    $phonePattern = '\+?[0-9 \-]{6,20}';
@endphp

@section('content')
    <div class="page-header">
        <div>
            <h1>Site settings</h1>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li><li class="breadcrumb-item active">Settings</li></ol></nav>
        </div>
    </div>

    <form method="post" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" data-validate novalidate>
        @csrf
        <input type="hidden" name="tab" id="activeTab" value="general">
        <div class="row g-4">
            <div class="col-lg-3">
                <div class="card">
                    <div class="card-body p-2">
                        <div class="nav flex-column nav-tabs-modern" role="tablist">
                            <button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#general"><i class="bi bi-sliders"></i> General</button>
                            <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#branding"><i class="bi bi-palette"></i> Logo & branding</button>
                            <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#contact"><i class="bi bi-telephone"></i> Contact & social</button>
                            <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#seo"><i class="bi bi-search"></i> SEO & tracking</button>
                            <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#sms"><i class="bi bi-chat-dots"></i> SMS / OTP</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-9">
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="general">
                        <div class="card">
                            <div class="card-header"><h5>General</h5></div>
                            <div class="card-body row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="site_name">Site name <span class="text-danger">*</span></label>
                                    <input type="text" name="site_name" id="site_name" class="form-control" value="{{ $v('site_name') }}" placeholder="e.g. DS Shop" required minlength="2" maxlength="60" data-counter>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="tagline">Tagline</label>
                                    <input type="text" name="tagline" id="tagline" class="form-control" value="{{ $v('tagline') }}" placeholder="Quality products, delivered to your door" maxlength="120" data-counter>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="topbar_text">Top bar announcement</label>
                                    <input type="text" name="topbar_text" id="topbar_text" class="form-control" value="{{ $v('topbar_text') }}" placeholder="Free delivery over ৳2000!" maxlength="120" data-counter>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="footer_about">Footer about text</label>
                                    <textarea name="footer_about" id="footer_about" rows="3" class="form-control" placeholder="A short paragraph about your shop" maxlength="300" data-counter>{{ $v('footer_about') }}</textarea>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label" for="copyright_text">Copyright text</label>
                                    <input type="text" name="copyright_text" id="copyright_text" class="form-control" value="{{ $v('copyright_text') }}" placeholder="© {{ date('Y') }} Your Shop. All rights reserved." maxlength="120">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="default_delivery_charge">Fallback delivery charge</label>
                                    <div class="input-group"><span class="input-group-text">৳</span><input type="number" min="0" max="9999" step="1" name="default_delivery_charge" id="default_delivery_charge" class="form-control" value="{{ $v('default_delivery_charge', 130) }}" placeholder="130"></div>
                                    <div class="form-text">Per-district charges are in <a href="{{ route('admin.districts.index') }}">Districts</a>.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="branding">
                        <div class="card">
                            <div class="card-header"><h5>Logo & branding</h5></div>
                            <div class="card-body row g-4">
                                @foreach($images as $key => [$label, $type, $hint])
                                    <div class="col-md-6">
                                        <label class="form-label" for="f-{{ $key }}">{{ $label }}</label>
                                        <div class="d-flex align-items-center gap-3 mb-2 p-3 rounded-3 {{ $key === 'logo_light' ? 'bg-dark' : 'bg-light' }}" style="min-height: 90px">
                                            <img id="prev-{{ $key }}" src="{{ !empty($s[$key]) ? img_url($s[$key]) : '' }}" class="{{ empty($s[$key]) ? 'd-none' : '' }}" style="max-height: 70px; max-width: 100%" alt="">
                                            @if(empty($s[$key]))<span class="small {{ $key === 'logo_light' ? 'text-white-50' : 'text-muted' }}">No image uploaded</span>@endif
                                        </div>
                                        <input type="file" name="{{ $key }}" id="f-{{ $key }}" class="form-control @error($key) is-invalid @enderror" {!! upload_attrs($type) !!} data-preview="#prev-{{ $key }}">
                                        @error($key)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        @if(!empty($s[$key]))
                                            <div class="form-check mt-2 mb-0"><input class="form-check-input" type="checkbox" name="remove_{{ $key }}" value="1" id="rm-{{ $key }}"><label class="form-check-label text-danger small" for="rm-{{ $key }}">Remove current image</label></div>
                                        @endif
                                        <div class="mt-2">@include('admin.partials.upload-hint', ['type' => $type, 'note' => $hint])</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="contact">
                        <div class="card">
                            <div class="card-header"><h5>Contact & social links</h5></div>
                            <div class="card-body row g-3">
                                <div class="col-md-4">
                                    <label class="form-label" for="phone"><i class="bi bi-telephone me-1"></i>Phone / hotline</label>
                                    <input type="tel" name="phone" id="phone" class="form-control" value="{{ $v('phone') }}" placeholder="01XXXXXXXXX" maxlength="20" pattern="{{ $phonePattern }}" data-msg-pattern="Enter a valid phone number.">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="whatsapp"><i class="bi bi-whatsapp me-1 text-success"></i>WhatsApp number</label>
                                    <input type="tel" name="whatsapp" id="whatsapp" class="form-control" value="{{ $v('whatsapp') }}" placeholder="01XXXXXXXXX" maxlength="20" pattern="{{ $phonePattern }}" data-msg-pattern="Enter a valid WhatsApp number.">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="email"><i class="bi bi-envelope me-1"></i>Email</label>
                                    <input type="email" name="email" id="email" class="form-control" value="{{ $v('email') }}" placeholder="support@yourshop.com" maxlength="150">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="address"><i class="bi bi-geo-alt me-1"></i>Address</label>
                                    <input type="text" name="address" id="address" class="form-control" value="{{ $v('address') }}" placeholder="House, road, area, city" maxlength="255">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="map_embed_url">Google map embed URL</label>
                                    <input type="url" name="map_embed_url" id="map_embed_url" class="form-control" value="{{ $v('map_embed_url') }}" placeholder="https://www.google.com/maps/embed?pb=…" maxlength="1000">
                                </div>
                                <div class="col-12"><hr class="my-1"></div>
                                @foreach(['facebook' => ['Facebook page', 'https://facebook.com/yourpage'], 'messenger' => ['Messenger link', 'https://m.me/yourpage'], 'instagram' => ['Instagram', 'https://instagram.com/yourshop'], 'youtube' => ['YouTube', 'https://youtube.com/@yourshop'], 'tiktok' => ['TikTok', 'https://tiktok.com/@yourshop']] as $key => [$label, $ph])
                                    <div class="col-md-6">
                                        <label class="form-label" for="{{ $key }}"><i class="bi bi-{{ $key }} me-1"></i>{{ $label }}</label>
                                        <input type="url" name="{{ $key }}" id="{{ $key }}" class="form-control" value="{{ $v($key) }}" placeholder="{{ $ph }}" maxlength="255">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="seo">
                        <div class="card">
                            <div class="card-header"><h5>SEO & tracking</h5></div>
                            <div class="card-body row g-3">
                                <div class="col-12">
                                    <label class="form-label" for="meta_title">Default meta title</label>
                                    <input type="text" name="meta_title" id="meta_title" class="form-control" value="{{ $v('meta_title') }}" placeholder="Your Shop — Online Shopping in Bangladesh" maxlength="70" data-counter>
                                    <div class="form-text">Used on the home page. ~60 characters is ideal.</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="meta_description">Default meta description</label>
                                    <textarea name="meta_description" id="meta_description" rows="3" class="form-control" placeholder="Describe your shop for Google results (~155 characters)" maxlength="160" data-counter>{{ $v('meta_description') }}</textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="meta_keywords">Meta keywords</label>
                                    <input type="text" name="meta_keywords" id="meta_keywords" class="form-control" value="{{ $v('meta_keywords') }}" placeholder="online shopping, gadgets, fashion" maxlength="255">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="google_analytics_id">Google Analytics ID</label>
                                    <input type="text" name="google_analytics_id" id="google_analytics_id" class="form-control" value="{{ $v('google_analytics_id') }}" placeholder="G-XXXXXXXXXX" maxlength="30" pattern="(G|UA)-[A-Z0-9\-]+" data-msg-pattern="Analytics ID looks like G-XXXXXXXXXX.">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="facebook_pixel_id">Facebook Pixel ID</label>
                                    <input type="text" name="facebook_pixel_id" id="facebook_pixel_id" class="form-control" value="{{ $v('facebook_pixel_id') }}" placeholder="e.g. 123456789012345" minlength="10" maxlength="20" pattern="[0-9]+" inputmode="numeric" data-digits data-msg-pattern="Pixel ID contains digits only.">
                                </div>
                                <div class="col-12">
                                    <div class="p-3 rounded-3 bg-light small">
                                        <i class="bi bi-diagram-3 me-1"></i> Sitemap: <a href="{{ route('sitemap') }}" target="_blank">{{ route('sitemap') }}</a>
                                        &nbsp;·&nbsp; Robots: <a href="{{ url('robots.txt') }}" target="_blank">{{ url('robots.txt') }}</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="sms">
                        <div class="card">
                            <div class="card-header"><h5>SMS gateway for OTP</h5></div>
                            <div class="card-body row g-3">
                                <div class="col-md-4">
                                    <label class="form-label" for="sms_driver">Driver</label>
                                    <select name="sms_driver" id="sms_driver" class="form-select">
                                        <option value="log" @selected($v('sms_driver', 'log') === 'log')>Log only (testing)</option>
                                        <option value="http" @selected($v('sms_driver') === 'http')>HTTP API (live)</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="sms_api_key">API key</label>
                                    <input type="text" name="sms_api_key" id="sms_api_key" class="form-control" value="{{ $v('sms_api_key') }}" placeholder="Your provider API key" maxlength="255" autocomplete="off">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="sms_sender_id">Sender ID</label>
                                    <input type="text" name="sms_sender_id" id="sms_sender_id" class="form-control" value="{{ $v('sms_sender_id') }}" placeholder="e.g. 8809617XXXXXX" maxlength="30">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="sms_api_url">API URL template</label>
                                    <input type="text" name="sms_api_url" id="sms_api_url" class="form-control font-monospace small" value="{{ $v('sms_api_url') }}" maxlength="500" pattern="https?://.+" data-msg-pattern="Must start with http:// or https://" placeholder="http://bulksmsbd.net/api/smsapi?api_key={api_key}&type=text&number={number}&senderid={sender_id}&message={message}">
                                    <div class="form-text">Placeholders: <code>{api_key}</code> <code>{sender_id}</code> <code>{number}</code> (880…) <code>{message}</code>. Works with most Bangladeshi bulk SMS providers.</div>
                                </div>
                                <div class="col-12">
                                    <div class="alert alert-info border-0 small mb-0" style="border-radius: 10px"><i class="bi bi-info-circle me-1"></i> With <strong>Log only</strong>, codes are written to <code>storage/logs/laravel.log</code>, and shown on the verify page while <code>APP_DEBUG=true</code>.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <button class="btn btn-primary px-4"><i class="bi bi-check2 me-1"></i> Save settings</button>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('page-scripts')
    <script>
        (function () {
            const hash = window.location.hash;
            if (hash && document.querySelector(`[data-bs-target="${hash}"]`)) {
                bootstrap.Tab.getOrCreateInstance(document.querySelector(`[data-bs-target="${hash}"]`)).show();
                document.getElementById('activeTab').value = hash.slice(1);
            }
            // Open the tab holding the first server-side error.
            const firstError = document.querySelector('.tab-pane .is-invalid');
            if (firstError) {
                const pane = firstError.closest('.tab-pane');
                bootstrap.Tab.getOrCreateInstance(document.querySelector(`[data-bs-target="#${pane.id}"]`)).show();
            }
            document.querySelectorAll('[data-bs-toggle="tab"]').forEach((el) => el.addEventListener('shown.bs.tab', (e) => {
                const id = e.target.dataset.bsTarget;
                history.replaceState(null, '', id);
                document.getElementById('activeTab').value = id.slice(1);
            }));
        })();
    </script>
@endpush
