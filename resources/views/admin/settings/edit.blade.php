@extends('admin.layouts.app')

@section('title', 'Site settings')

@php
    $v = fn ($key, $default = '') => old($key, $s[$key] ?? $default);
    $images = [
        'logo' => ['Logo (dark, for header)', 'logo', 'Used in the store header on a white background.'],
        'logo_light' => ['Logo (light, for footer & admin)', 'logo', 'White version on a transparent background.'],
        'favicon' => ['Favicon', 'favicon', 'Browser tab and Google search icon. Upload a square image (512 × 512 is best); it is saved as a 192 × 192 PNG.'],
        'og_image' => ['Social share image', 'og_image', 'Shown when links are shared on Facebook, WhatsApp, etc.'],
    ];
    // Google only shows favicons whose size is a multiple of 48 px; older uploads were saved at 64 × 64.
    $favFile = setting('favicon') ? rtrim((string) config('filesystems.disks.uploads.root', public_path('uploads')), '/\\').'/'.ltrim(setting('favicon'), '/') : null;
    $favSize = $favFile && is_file($favFile) ? @getimagesize($favFile) : null;
    if ($favSize && ($favSize[0] % 48 !== 0 || $favSize[0] !== $favSize[1])) {
        $images['favicon'][2] .= ' ⚠ The current one is '.$favSize[0].' × '.$favSize[1].' px, which Google does not show — upload it again.';
    }
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
                            <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#pixel"><i class="bi bi-facebook"></i> Facebook Pixel</button>
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
                                <div class="col-12"><hr class="my-1"></div>
                                <div class="col-12">
                                    <label class="form-label" for="whatsapp_invoice_message"><i class="bi bi-whatsapp me-1 text-success"></i>WhatsApp invoice message</label>
                                    <textarea name="whatsapp_invoice_message" id="whatsapp_invoice_message" rows="2" class="form-control" maxlength="1000" data-counter
                                              placeholder="{{ \App\Support\WhatsappInvoice::DEFAULT_MESSAGE }}">{{ $v('whatsapp_invoice_message') }}</textarea>
                                    <div class="form-text">Sent with the mini invoice from an order (Orders → order → <em>Send invoice</em>); it can be changed before each send. You can use <code>{name}</code> <code>{order}</code> <code>{total}</code>. Empty = the text shown.</div>
                                </div>
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
                                    <input type="hidden" name="meta_keywords_present" value="1">
                                    <select name="meta_keywords[]" id="meta_keywords" class="form-select" multiple data-keywords="{{ route('admin.seo.keywords') }}" data-max="{{ \App\Support\Seo::MAX_KEYWORDS }}" data-placeholder="e.g. online shopping, gadgets — press Enter after each">
                                        @foreach(\App\Support\Seo::list($v('meta_keywords')) as $word)<option value="{{ $word }}" selected>{{ $word }}</option>@endforeach
                                    </select>
                                    <div class="form-text">Used on pages that have no keywords of their own (home, lists). Press <kbd>Enter</kbd> or <kbd>,</kbd> after each keyword.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="google_analytics_id">Google Analytics ID</label>
                                    <input type="text" name="google_analytics_id" id="google_analytics_id" class="form-control" value="{{ $v('google_analytics_id') }}" placeholder="G-XXXXXXXXXX" maxlength="30" pattern="(G|UA)-[A-Z0-9\-]+" data-msg-pattern="Analytics ID looks like G-XXXXXXXXXX.">
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <div class="form-text mb-2"><i class="bi bi-facebook me-1"></i>Facebook Pixel is on the <a href="#pixel" data-open-tab="#pixel">Facebook Pixel</a> tab.</div>
                                </div>
                            </div>
                        </div>

                        <div class="card mt-4" id="search-console">
                            <div class="card-header"><h5><i class="bi bi-google me-1"></i> Google Search Console & sitemap</h5></div>
                            <div class="card-body row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="google_site_verification">Search Console verification code</label>
                                    <input type="text" name="google_site_verification" id="google_site_verification" class="form-control font-monospace @error('google_site_verification') is-invalid @enderror" value="{{ $v('google_site_verification') }}" placeholder="e.g. AbC123xYz_-…" maxlength="300" spellcheck="false" autocomplete="off">
                                    @error('google_site_verification')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    <div class="form-text">Search Console → Add property → <em>URL prefix</em> → HTML tag. Paste the code or the whole <code>&lt;meta name="google-site-verification" …&gt;</code> tag; it is added to the home page.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="sitemapUrl">Sitemap — submit this in Search Console → Sitemaps</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control font-monospace small" id="sitemapUrl" value="{{ route('sitemap') }}" readonly data-no-validate>
                                        <button type="button" class="btn btn-outline-secondary" data-copy="{{ route('sitemap') }}" data-copy-msg="Sitemap link copied" title="Copy"><i class="bi bi-clipboard"></i></button>
                                        <a href="{{ route('sitemap') }}" target="_blank" rel="noopener" class="btn btn-outline-secondary" title="Open"><i class="bi bi-box-arrow-up-right"></i></a>
                                    </div>
                                    <div class="small mt-2" id="sitemapSummary">
                                        <strong>{{ number_format($sitemap['total']) }}</strong> links:
                                        @foreach(['pages' => 'pages', 'categories' => 'categories', 'brands' => 'brands', 'products' => 'products', 'blog' => 'blog posts'] as $key => $label)
                                            <span class="badge badge-soft-secondary">{{ number_format($sitemap['counts'][$key] ?? 0) }} {{ $label }}</span>
                                        @endforeach
                                        <span class="badge badge-soft-primary">{{ number_format($sitemap['images']) }} images</span>
                                        @if($sitemap['updated'])<div class="text-muted mt-1">Last change {{ $sitemap['updated']->diffForHumans() }} · updates by itself when you add or edit products, categories, brands or posts.</div>@endif
                                    </div>
                                    <div class="small text-muted mt-1">Robots: <a href="{{ url('robots.txt') }}" target="_blank" rel="noopener">{{ url('robots.txt') }}</a></div>
                                </div>
                                @if(! request()->isSecure() && ! app()->environment('local'))
                                    <div class="col-12"><div class="p-2 px-3 rounded-3 small bg-soft-danger"><i class="bi bi-exclamation-triangle me-1"></i>This page is open over <strong>http</strong>. Use the <strong>https</strong> address of your site in Search Console, and submit the https sitemap.</div></div>
                                @endif
                            </div>
                        </div>
                    </div>

                    @include('admin.settings.facebook')

                    @include('admin.settings.sms')
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
            document.querySelectorAll('[data-open-tab]').forEach((a) => a.addEventListener('click', (e) => {
                e.preventDefault();
                bootstrap.Tab.getOrCreateInstance(document.querySelector(`[data-bs-target="${a.dataset.openTab}"]`)).show();
            }));
            document.querySelectorAll('[data-bs-toggle="tab"]').forEach((el) => el.addEventListener('shown.bs.tab', (e) => {
                const id = e.target.dataset.bsTarget;
                history.replaceState(null, '', id);
                document.getElementById('activeTab').value = id.slice(1);
            }));
        })();
    </script>
@endpush
