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

                            {{-- Text: title and subtitle are always required (also the image description for Google);
                                 each one is shown on the banner or hidden with its own switch. --}}
                            <div class="col-12">
                                <h6 class="fw-semibold mb-0"><i class="bi bi-type me-1"></i> Text</h6>
                                <div class="form-text mt-0">Use the <strong>Show</strong> switches to hide a part on the website, e.g. when the text is already in the image. Hidden text is still used as the image description for Google.</div>
                            </div>
                            <div class="col-md-8">
                                <div class="d-flex justify-content-between align-items-center gap-2">
                                    <label class="form-label" for="title">Title <span class="text-danger">*</span></label>
                                    @include('admin.sliders.partials.show-switch', ['part' => 'title'])
                                </div>
                                <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $slider->title) }}" placeholder="Big headline, e.g. Mega Gadget Sale" maxlength="80" data-counter required data-msg-required="Please enter a title.">
                                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4" data-part="title">@include('admin.sliders.partials.color', ['key' => 'title_color', 'label' => 'Title colour', 'default' => '#ffffff'])</div>
                            <div class="col-md-8">
                                <div class="d-flex justify-content-between align-items-center gap-2">
                                    <label class="form-label" for="subtitle">Subtitle <span class="text-danger">*</span></label>
                                    @include('admin.sliders.partials.show-switch', ['part' => 'subtitle'])
                                </div>
                                <input type="text" name="subtitle" id="subtitle" class="form-control @error('subtitle') is-invalid @enderror" value="{{ old('subtitle', $slider->subtitle) }}" placeholder="Supporting line, e.g. Up to 30% off this week" maxlength="150" data-counter required data-msg-required="Please enter a subtitle.">
                                @error('subtitle')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4" data-part="subtitle">@include('admin.sliders.partials.color', ['key' => 'subtitle_color', 'label' => 'Subtitle colour', 'default' => '#ffffff'])</div>

                            {{-- Button --}}
                            <div class="col-12"><hr class="my-1">
                                <div class="d-flex justify-content-between align-items-center gap-2">
                                    <h6 class="fw-semibold mb-0"><i class="bi bi-hand-index me-1"></i> Button</h6>
                                    @include('admin.sliders.partials.show-switch', ['part' => 'button'])
                                </div>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label" for="button_text">Button text</label>
                                <input type="text" name="button_text" id="button_text" class="form-control" value="{{ old('button_text', $slider->button_text) }}" placeholder="Shop now" maxlength="30">
                            </div>
                            <div class="col-md-7">
                                <label class="form-label" for="link">Link</label>
                                <input type="text" name="link" id="link" class="form-control" value="{{ old('link', $slider->link) }}" placeholder="category/electronics or https://…" maxlength="255">
                            </div>
                            <div class="col-12 mt-1"><div class="form-text mt-0">Link: a path inside the store or a full URL. The button shows when it is switched on and has text and a link. With the button hidden, the whole banner opens the link.</div></div>
                            <div class="col-md-5" data-part="button">
                                <label class="form-label" for="button_position">Button position</label>
                                <select name="button_position" id="button_position" class="form-select">
                                    @foreach(\App\Models\Slider::BUTTON_POSITIONS as $key => $label)
                                        <option value="{{ $key }}" @selected(old('button_position', $slider->buttonPosition()) === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-7 d-flex align-items-end" data-part="button">
                                <div class="btn-group w-100" role="group" aria-label="Button position quick pick">
                                    @foreach(['below_text' => 'bi-text-left', 'bottom_left' => 'bi-align-start', 'bottom_center' => 'bi-align-center', 'bottom_right' => 'bi-align-end'] as $key => $icon)
                                        <button type="button" class="btn btn-outline-secondary" data-btn-pos="{{ $key }}" title="{{ \App\Models\Slider::BUTTON_POSITIONS[$key] }}"><i class="bi {{ $icon }}"></i></button>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-md-5" data-part="button">@include('admin.sliders.partials.color', ['key' => 'button_color', 'label' => 'Button background', 'default' => '#f8f9fa'])</div>
                            <div class="col-md-5" data-part="button">@include('admin.sliders.partials.color', ['key' => 'button_text_color', 'label' => 'Button text colour', 'default' => '#212529'])</div>
                            <div class="col-md-2 d-flex align-items-end" data-part="button">
                                <span class="btn btn-light fw-bold w-100 text-truncate border" id="buttonSwatch" title="Button preview" style="pointer-events:none">Shop now</span>
                            </div>

                            {{-- Look --}}
                            <div class="col-12" data-part="text"><hr class="my-1"><h6 class="fw-semibold mb-0"><i class="bi bi-palette me-1"></i> Look</h6><div class="form-text mt-0">Pick what reads well on this image — the preview on the right updates as you change it. Empty colour = default.</div></div>
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
                                    <div class="hero-text">
                                        <h2 data-pv="title"></h2>
                                        <p data-pv="subtitle"></p>
                                    </div>
                                    <span class="btn btn-light btn-sm fw-bold hero-btn" data-pv="button"></span>
                                    <span class="btn btn-light btn-sm fw-bold hero-btn" data-pv-spacer aria-hidden="true" style="visibility:hidden"></span>
                                </div>
                                <div class="hero-btn-float" data-pv-float></div>
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

@push('page-scripts')
    <script>
        (function () {
            const $f = (sel) => document.querySelector(sel);
            const box = $f('#slidePreviewBox'), shade = $f('[data-pv-shade]');
            const code = (k) => { const v = $f('#' + k).value.trim(); return /^#[0-9a-fA-F]{6}$/.test(v) ? v : ''; };
            const paintButton = (el) => { el.style.backgroundColor = code('button_color'); el.style.borderColor = code('button_color'); el.style.color = code('button_text_color'); };
            const render = () => {
                const title = $f('#title').value.trim(), sub = $f('#subtitle').value.trim(), btn = $f('#button_text').value.trim(), link = $f('#link').value.trim();
                // Each part on its own: switched on AND filled in (the button also needs a link) — same rule as the website.
                const show = { title: $f('#show_title').checked && !!title, subtitle: $f('#show_subtitle').checked && !!sub, button: $f('#show_button').checked && !!btn && !!link };
                const t = $f('[data-pv="title"]'), s = $f('[data-pv="subtitle"]'), b = $f('[data-pv="button"]');
                t.textContent = title; t.style.display = show.title ? '' : 'none'; t.style.color = code('title_color');
                s.textContent = sub; s.style.display = show.subtitle ? '' : 'none'; s.style.color = code('subtitle_color');
                b.textContent = btn; b.style.display = show.button ? '' : 'none';
                // Put the preview button where the website will show it.
                const pos = $f('#button_position').value, float = $f('[data-pv-float]');
                float.className = 'hero-btn-float hero-btn-' + pos;
                if (pos === 'below_text') $f('.slide-preview .hero-text').appendChild(b);
                else if (pos === 'bottom_left') $f('.slide-preview .carousel-caption').appendChild(b);
                else float.appendChild(b);
                const spacer = $f('[data-pv-spacer]');
                spacer.textContent = btn;
                spacer.style.display = show.button && (pos === 'bottom_center' || pos === 'bottom_right') ? '' : 'none';
                document.querySelectorAll('[data-btn-pos]').forEach((x) => x.classList.toggle('active', x.dataset.btnPos === pos));
                paintButton(b);
                const sw = $f('#buttonSwatch');
                sw.textContent = btn || 'Shop now';
                paintButton(sw);
                const anyText = show.title || show.subtitle;
                const sh = $f('#text_shade').value;
                shade.className = 'hero-shade' + (anyText && (sh === 'dark' || sh === 'light') ? ' hero-shade-' + sh : '');
                $f('.slide-preview .carousel-caption').style.display = anyText || show.button ? '' : 'none';
                // Options for a hidden part do nothing on the website, so dim them.
                const switched = { title: $f('#show_title').checked, subtitle: $f('#show_subtitle').checked, button: $f('#show_button').checked };
                switched.text = switched.title || switched.subtitle;
                document.querySelectorAll('[data-part]').forEach((el) => el.classList.toggle('opacity-50', !switched[el.dataset.part]));
            };
            document.querySelectorAll('[data-show-part]').forEach((x) => x.addEventListener('change', render));
            $f('#button_position').addEventListener('change', render);
            document.querySelectorAll('[data-btn-pos]').forEach((x) => x.addEventListener('click', () => { $f('#button_position').value = x.dataset.btnPos; render(); }));
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
