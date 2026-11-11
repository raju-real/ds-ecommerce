@extends('admin.layouts.app')

@php($editing = $slider->exists)
@php($hasColours = \Illuminate\Support\Facades\Schema::hasColumn('sliders', 'text_shade'))
@section('title', $editing ? 'Edit slide' : 'Add slide')

@section('content')
    <div class="page-header">
        <div>
            <h1>{{ $editing ? 'Edit slide' : 'Add slide' }}</h1>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.sliders.index') }}">Sliders</a></li><li class="breadcrumb-item active">{{ $editing ? 'Edit' : 'New' }}</li></ol></nav>
        </div>
        <a href="{{ route('admin.sliders.index') }}" class="btn btn-soft-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <form method="post" enctype="multipart/form-data" data-validate novalidate action="{{ $editing ? route('admin.sliders.update', $slider->unique_id) : route('admin.sliders.store') }}">
        @csrf
        @if($editing) @method('PUT') @endif
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-body">
                        <div class="row g-3">
                            @unless($hasColours)
                                <div class="col-12"><div class="p-2 px-3 rounded-3 small bg-soft-danger"><i class="bi bi-exclamation-triangle me-1"></i>Colours are not saved yet: run <code>php artisan migrate</code> on this server once.</div></div>
                            @endunless

                            {{-- Text --}}
                            <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <h6 class="fw-semibold mb-0"><i class="bi bi-type me-1"></i> Text</h6>
                                <div class="form-check form-switch mb-0">
                                    <input type="hidden" name="show_text" value="0">
                                    <input class="form-check-input" type="checkbox" role="switch" name="show_text" value="1" id="show_text" @checked(old('show_text', $slider->showsText()))>
                                    <label class="form-check-label fw-semibold" for="show_text">Show text on website</label>
                                </div>
                            </div>
                            <div class="col-12 mt-1"><div class="form-text mt-0" id="showTextHint">Off: only the image is shown (the whole banner opens the link). The title is still used as the image description for Google.</div></div>
                            <div class="col-md-8">
                                <label class="form-label" for="title">Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $slider->title) }}" placeholder="Big headline, e.g. Mega Gadget Sale" maxlength="80" data-counter required data-msg-required="Please enter a title.">
                                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4" data-text-only>@include('admin.sliders.partials.color', ['key' => 'title_color', 'label' => 'Title colour', 'default' => '#ffffff'])</div>
                            <div class="col-md-8">
                                <label class="form-label" for="subtitle">Subtitle <span class="text-danger">*</span></label>
                                <input type="text" name="subtitle" id="subtitle" class="form-control @error('subtitle') is-invalid @enderror" value="{{ old('subtitle', $slider->subtitle) }}" placeholder="Supporting line, e.g. Up to 30% off this week" maxlength="150" data-counter required data-msg-required="Please enter a subtitle.">
                                @error('subtitle')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4" data-text-only>@include('admin.sliders.partials.color', ['key' => 'subtitle_color', 'label' => 'Subtitle colour', 'default' => '#ffffff'])</div>

                            {{-- Button --}}
                            <div class="col-12"><hr class="my-1"><h6 class="fw-semibold mb-0"><i class="bi bi-hand-index me-1"></i> Button</h6></div>
                            <div class="col-md-5">
                                <label class="form-label" for="button_text">Button text</label>
                                <input type="text" name="button_text" id="button_text" class="form-control" value="{{ old('button_text', $slider->button_text) }}" placeholder="Shop now" maxlength="30">
                            </div>
                            <div class="col-md-7">
                                <label class="form-label" for="link">Button link</label>
                                <input type="text" name="link" id="link" class="form-control" value="{{ old('link', $slider->link) }}" placeholder="category/electronics or https://…" maxlength="255">
                            </div>
                            <div class="col-12 mt-1"><div class="form-text mt-0">Link: a path inside the store or a full URL. The button shows when both text and link are set.</div></div>
                            <div class="col-md-5" data-text-only>@include('admin.sliders.partials.color', ['key' => 'button_color', 'label' => 'Button background', 'default' => '#f8f9fa'])</div>
                            <div class="col-md-5" data-text-only>@include('admin.sliders.partials.color', ['key' => 'button_text_color', 'label' => 'Button text colour', 'default' => '#212529'])</div>
                            <div class="col-md-2 d-flex align-items-end" data-text-only>
                                <span class="btn btn-light btn-sm fw-bold w-100 text-truncate" id="buttonSwatch" title="Button preview" style="pointer-events:none">Shop now</span>
                            </div>

                            {{-- Look --}}
                            <div class="col-12" data-text-only><hr class="my-1"><h6 class="fw-semibold mb-0"><i class="bi bi-palette me-1"></i> Look</h6><div class="form-text mt-0">Pick what reads well on this image — the preview on the right updates as you change it. Empty colour = default.</div></div>
                            <div class="col-sm-6">
                                <label class="form-label" for="text_shade">Shade behind text</label>
                                <select name="text_shade" id="text_shade" class="form-select">
                                    @foreach(\App\Models\Slider::SHADES as $key => $label)
                                        <option value="{{ $key }}" @selected(old('text_shade', $slider->text_shade ?: 'none') === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Helps on busy or bright images.</div>
                            </div>
                            <div class="col-sm-6 d-flex align-items-end">
                                <div class="d-flex flex-wrap gap-1 small mb-sm-4" aria-label="Quick colour sets">
                                    <span class="text-muted me-1 align-self-center">Quick:</span>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-color-preset='{"title_color":"#ffffff","subtitle_color":"#ffffff","button_color":"#ffffff","button_text_color":"#111827","text_shade":"dark"}'>Light text</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-color-preset='{"title_color":"#111827","subtitle_color":"#374151","button_color":"#4f46e5","button_text_color":"#ffffff","text_shade":"light"}'>Dark text</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-color-preset='{"title_color":"","subtitle_color":"","button_color":"","button_text_color":"","text_shade":"none"}'>Reset</button>
                                </div>
                            </div>

                            {{-- Position + visibility --}}
                            <div class="col-12"><hr class="my-1"></div>
                            <div class="col-sm-6">
                                <label class="form-label" for="sort_order">Position</label>
                                <input type="number" name="sort_order" id="sort_order" class="form-control" value="{{ old('sort_order', $slider->sort_order ?? 0) }}" placeholder="0" min="0" max="9999" step="1">
                                <div class="form-text">Lower shows first. Easier: drag the banners on the <a href="{{ route('admin.sliders.index') }}">Sliders</a> page.</div>
                            </div>
                            <div class="col-sm-6 d-flex align-items-center">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $slider->is_active))>
                                    <label class="form-check-label" for="is_active">Show on home page</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header"><h5>Banner image {!! $editing ? '' : '<span class="text-danger">*</span>' !!}</h5></div>
                    <div class="card-body">
                        {{-- Live preview: the banner with its text in the chosen colours (same look as the home page). --}}
                        <div class="slide-preview hero mb-3 {{ $editing ? '' : 'd-none' }}" id="slidePreviewBox">
                            <div class="carousel-item active d-block">
                                <img id="slidePreview" src="{{ $editing ? img_url($slider->image) : '' }}" alt="">
                                <div class="hero-shade" data-pv-shade aria-hidden="true"></div>
                                <div class="carousel-caption">
                                    <h2 data-pv="title"></h2>
                                    <p class="mb-2" data-pv="subtitle"></p>
                                    <span class="btn btn-light btn-sm fw-bold" data-pv="button"></span>
                                </div>
                            </div>
                        </div>
                        <input type="file" name="image" class="form-control @error('image') is-invalid @enderror" {!! upload_attrs('slider') !!} data-preview="#slidePreview" {{ $editing ? '' : 'required' }} data-msg-required="Please choose a banner image.">
                        @error('image')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        <div class="mt-2">@include('admin.partials.upload-hint', ['type' => 'slider', 'note' => 'Cropped to '.implode(' × ', config('shop.images.slider')).' px. The heading sits on the left, so keep the product on the right side.'])</div>
                        <button class="btn btn-primary w-100 mt-4"><i class="bi bi-check2 me-1"></i> {{ $editing ? 'Save changes' : 'Create slide' }}</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('styles')
    <style>
        /* Same look as the home page banner (shop.css .hero), scaled to the preview box */
        .slide-preview .carousel-item { position: relative; aspect-ratio: 16 / 6; border-radius: 12px; overflow: hidden; background: #ddd; border: 1px solid #e5e7eb; }
        .slide-preview .carousel-item img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .slide-preview .carousel-caption { position: absolute; left: 8%; right: auto; top: 50%; bottom: auto; transform: translateY(-50%); text-align: left; max-width: 60%; color: #fff; padding: 0; }
        .slide-preview .carousel-caption h2 { font-weight: 800; font-size: 1.05rem; margin: 0 0 .25rem; letter-spacing: -.02em; text-shadow: 0 2px 20px rgba(0, 0, 0, .15); }
        .slide-preview .carousel-caption p { font-size: .72rem; opacity: .95; }
        .slide-preview .carousel-caption .btn { font-size: .68rem; padding: .2rem .55rem; pointer-events: none; }
        .slide-preview .hero-shade { position: absolute; inset: 0; pointer-events: none; }
        .slide-preview .hero-shade-dark { background: linear-gradient(90deg, rgba(0, 0, 0, .55) 0%, rgba(0, 0, 0, .25) 45%, rgba(0, 0, 0, 0) 70%); }
        .slide-preview .hero-shade-light { background: linear-gradient(90deg, rgba(255, 255, 255, .8) 0%, rgba(255, 255, 255, .4) 45%, rgba(255, 255, 255, 0) 70%); }
        .slide-preview .hero-shade-light ~ .carousel-caption { color: #0f172a; }
        .slide-preview .hero-shade-light ~ .carousel-caption h2 { text-shadow: none; }
    </style>
@endpush

@push('page-scripts')
    <script>
        (function () {
            const $f = (sel) => document.querySelector(sel);
            const box = $f('#slidePreviewBox'), shade = $f('[data-pv-shade]');
            const code = (k) => { const v = $f('#' + k).value.trim(); return /^#[0-9a-fA-F]{6}$/.test(v) ? v : ''; };
            const paintButton = (el) => { el.style.backgroundColor = code('button_color'); el.style.borderColor = code('button_color'); el.style.color = code('button_text_color'); };
            const render = () => {
                const title = $f('#title').value.trim(), sub = $f('#subtitle').value.trim(), btn = $f('#button_text').value.trim();
                const t = $f('[data-pv="title"]'), s = $f('[data-pv="subtitle"]'), b = $f('[data-pv="button"]');
                t.textContent = title; t.style.display = title ? '' : 'none'; t.style.color = code('title_color');
                s.textContent = sub; s.style.display = sub ? '' : 'none'; s.style.color = code('subtitle_color');
                b.textContent = btn; b.style.display = btn && $f('#link').value.trim() ? '' : 'none';
                paintButton(b);
                const sw = $f('#buttonSwatch');
                sw.textContent = btn || 'Shop now';
                paintButton(sw);
                const on = $f('#show_text').checked;
                const sh = $f('#text_shade').value;
                shade.className = 'hero-shade' + (on && (sh === 'dark' || sh === 'light') ? ' hero-shade-' + sh : '');
                $f('.slide-preview .carousel-caption').style.display = on ? '' : 'none';
                // Text off: the colour/button options do nothing on the website, so dim them.
                document.querySelectorAll('[data-text-only]').forEach((el) => el.classList.toggle('opacity-50', !on));
            };
            $f('#show_text').addEventListener('change', render);
            document.querySelectorAll('[data-color-field]').forEach((g) => {
                const picker = g.querySelector('[data-color-picker]'), text = g.querySelector('[data-color-code]');
                picker.addEventListener('input', () => { text.value = picker.value; render(); });
                text.addEventListener('input', () => { if (/^#[0-9a-fA-F]{6}$/.test(text.value.trim())) picker.value = text.value.trim(); render(); });
                g.querySelector('[data-color-reset]').addEventListener('click', () => { text.value = ''; picker.value = text.dataset.default; render(); });
            });
            document.querySelectorAll('[data-color-preset]').forEach((btn) => btn.addEventListener('click', () => {
                Object.entries(JSON.parse(btn.dataset.colorPreset)).forEach(([k, v]) => {
                    const el = $f('#' + k);
                    el.value = v;
                    const picker = el.closest('[data-color-field]') && el.closest('[data-color-field]').querySelector('[data-color-picker]');
                    if (picker) picker.value = v || el.dataset.default;
                });
                render();
            }));
            ['#title', '#subtitle', '#button_text', '#link'].forEach((sel) => $f(sel).addEventListener('input', render));
            $f('#text_shade').addEventListener('change', render);
            const file = document.querySelector('input[type=file][data-preview]');
            if (file) file.addEventListener('change', function () { if (this.files && this.files[0]) box.classList.remove('d-none'); });
            render();
        })();
    </script>
@endpush
