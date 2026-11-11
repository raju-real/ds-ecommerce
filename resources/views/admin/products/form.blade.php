@extends('admin.layouts.app')

@php($editing = $product->exists)
@section('title', $editing ? 'Edit product' : 'Add product')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
@endpush
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
@endpush

@section('content')
    <div class="page-header">
        <div>
            <h1>{{ $editing ? 'Edit product' : 'Add product' }}</h1>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.products.index') }}">Products</a></li><li class="breadcrumb-item active">{{ $editing ? $product->code : 'New' }}</li></ol></nav>
        </div>
        <div class="d-flex gap-2">
            @if($editing)<a href="{{ route('product.show', $product->slug) }}" target="_blank" class="btn btn-soft-secondary"><i class="bi bi-box-arrow-up-right me-1"></i> View</a>@endif
            <a href="{{ route('admin.products.index') }}" class="btn btn-soft-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
        </div>
    </div>

    @unless($editing)
        {{-- Quick check before adding: is this product code already used? (outside the form, never submitted) --}}
        <div class="card mb-4 code-check" id="codeCheck" data-url="{{ route('admin.products.check-code') }}">
            <div class="card-body py-3">
                <label class="form-label mb-1" for="codeCheckInput"><i class="bi bi-upc-scan me-1 text-primary"></i> Check a product code before adding</label>
                <div class="input-group">
                    <input type="search" id="codeCheckInput" class="form-control" placeholder="Type a product code, e.g. DS-0036" maxlength="60" autocomplete="off">
                    <button type="button" class="btn btn-primary" id="codeCheckBtn" data-no-busy><i class="bi bi-search me-1"></i> Check</button>
                </div>
                <div class="result" id="codeCheckResult" aria-live="polite"></div>
            </div>
        </div>
    @endunless

    <form method="post" enctype="multipart/form-data" id="productForm" data-validate novalidate action="{{ $editing ? route('admin.products.update', $product->unique_id) : route('admin.products.store') }}">
        @csrf
        @if($editing) @method('PUT') @endif
        <div class="row g-4">
            <div class="col-xl-8">
                <div class="card mb-4">
                    <div class="card-header"><h5>Basic information</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Product name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $product->name) }}" placeholder="e.g. T800 Ultra Smart Watch" data-slug-source="#slug" required minlength="3" maxlength="200" data-counter>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Product code / SKU <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control" value="{{ old('code', $product->code) }}" placeholder="e.g. DS-0001" required maxlength="60" pattern="[A-Za-z0-9_-]+" data-msg-pattern="Use only letters, numbers, dashes and underscores.">
                            </div>
                            <div class="col-12">
                                <div class="slug-label-row">
                                    <label class="form-label" for="slug">Slug (URL)</label>
                                    @include('admin.partials.slug-generator', ['type' => 'product', 'id' => $product->id])
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text">/product/</span>
                                    <input type="text" name="slug" id="slug" class="form-control" value="{{ old('slug', $product->slug) }}" data-slug placeholder="auto-generated-from-name" maxlength="200" pattern="[A-Za-z0-9_-]+" data-msg-pattern="Use only letters, numbers, dashes and underscores.">
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Short description</label>
                                <textarea name="short_description" rows="2" class="form-control" maxlength="500" placeholder="One or two lines shown near the price" data-counter>{{ old('short_description', $product->short_description) }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="editor" maxlength="65000" data-upload-url="{{ route('admin.editor.image') }}" data-upload-max="{{ config('shop.uploads.editor') }}" placeholder="Full product details, features, package contents…">{{ old('description', $product->description) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Thumbnail <span class="text-danger">*</span></h5>
                        <span class="small text-muted">Main image on cards, search, cart & orders</span>
                    </div>
                    <div class="card-body">
                        <div class="thumb-upload">
                            <label class="thumb-box @error('thumbnail') is-invalid @enderror" for="thumbnail">
                                <img id="thumbPreview" src="{{ $product->thumbnail ? img_url($product->thumbnail) : '' }}" alt="" class="{{ $product->thumbnail ? '' : 'd-none' }}">
                                <span class="thumb-empty"><i class="bi bi-image"></i>Choose thumbnail<small>{{ implode(' × ', array_slice(config('shop.uploads.recommended.thumbnail'), 0, 2)) }} px</small></span>
                                <span class="thumb-change"><i class="bi bi-arrow-repeat"></i> Replace</span>
                            </label>
                            <div class="flex-grow-1 min-w-0">
                                <input type="file" name="thumbnail" id="thumbnail" class="form-control @error('thumbnail') is-invalid @enderror" {!! upload_attrs('thumbnail') !!} data-preview="#thumbPreview"
                                       {{ $product->thumbnail ? '' : 'required' }} data-msg-required="Please choose a thumbnail image.">
                                @error('thumbnail')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="mt-2">@include('admin.partials.upload-hint', ['type' => 'thumbnail', 'note' => 'Use a clean, centred product photo on a white or light background. Non-square images are fitted on a white square.'])</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Gallery images <span class="badge badge-soft-secondary ms-1">Optional</span></h5>
                        <span class="small text-muted">Shown after the thumbnail · drag to reorder</span>
                    </div>
                    <div class="card-body">
                        <div class="img-grid mb-3 sortable" id="imageGrid" data-sort-url="{{ $editing ? route('admin.products.images.sort', $product->unique_id) : '' }}">
                            @foreach($editing ? $product->images : [] as $img)
                                <div class="item" data-id="{{ $img->unique_id }}">
                                    <img src="{{ img_url($img->thumb) }}" alt="">
                                    <button type="button" class="del" data-delete-image="{{ route('admin.products.images.destroy', [$product->unique_id, $img->unique_id]) }}" title="Remove image" aria-label="Remove image"><i class="bi bi-x-lg"></i></button>
                                </div>
                            @endforeach
                        </div>
                        <label class="dropzone d-block">
                            <input type="file" name="images[]" multiple {!! upload_attrs('product') !!} data-grid="#imageGrid">
                            <i class="bi bi-cloud-arrow-up"></i>
                            <strong>Click or drop images here</strong>
                            <div class="small">Extra angles, details, packaging · up to {{ config('shop.uploads.product_files') }} at a time</div>
                        </label>
                        <div class="mt-2">@include('admin.partials.upload-hint', ['type' => 'product', 'extra' => 'Up to '.config('shop.uploads.product_files').' per upload'])</div>
                    </div>
                </div>

                @include('admin.partials.seo-fields', ['model' => $product, 'titleHint' => 'Defaults to the product name', 'descHint' => 'Defaults to the short description (~155 characters)', 'imageHint' => 'Leave empty to use the product thumbnail.'])
            </div>

            <div class="col-xl-4">
                <div class="card mb-4">
                    <div class="card-header"><h5>Pricing & profit</h5></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Regular price (unit price) <span class="text-danger">*</span></label>
                            <div class="input-group"><span class="input-group-text">৳</span><input type="number" step="0.01" min="1" max="9999999" name="unit_price" id="unit_price" class="form-control" value="{{ old('unit_price', $product->unit_price) }}" placeholder="e.g. 1850" required></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Discount price</label>
                            <div class="input-group"><span class="input-group-text">৳</span><input type="number" step="0.01" min="1" max="9999999" name="discount_price" id="discount_price" class="form-control" value="{{ old('discount_price', $product->discount_price) }}" placeholder="Leave empty for no discount"></div><div class="invalid-feedback" id="discountError"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label d-flex justify-content-between">Drop shipping price (vendor cost) <span class="text-danger">*</span></label>
                            <div class="input-group"><span class="input-group-text">৳</span><input type="number" step="0.01" min="0" max="9999999" name="drop_shipping_price" id="drop_shipping_price" class="form-control" value="{{ old('drop_shipping_price', $product->drop_shipping_price) }}" placeholder="What you pay the vendor" required></div>
                            <div class="form-text"><i class="bi bi-eye-slash"></i> Admin only — never shown on the storefront.</div>
                        </div>
                        <div class="profit-box">
                            <div class="row-line"><span class="text-muted">Customer pays</span><strong id="pSell">৳0</strong></div>
                            <div class="row-line"><span class="text-muted">Discount</span><span id="pDisc">—</span></div>
                            <div class="row-line"><span class="text-muted">Vendor cost</span><span id="pCost">৳0</span></div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between align-items-end">
                                <div><div class="small text-muted">Profit per unit</div><div class="big" id="pProfit">৳0</div></div>
                                <span class="badge badge-soft-success fs-6" id="pMargin">0%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header"><h5>Organization</h5></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select name="category_id" id="category_id" class="form-select select2" required data-placeholder="Select category" data-msg-required="Please select a category.">
                                <option></option>
                                @foreach($categories as $c)<option value="{{ $c->id }}" data-uid="{{ $c->unique_id }}" @selected(old('category_id', $product->category_id) == $c->id)>{{ $c->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subcategory</label>
                            <select name="subcategory_id" id="subcategory_id" class="form-select select2" data-placeholder="Select subcategory" data-selected="{{ old('subcategory_id', $product->subcategory_id) }}" data-url="{{ route('admin.categories.subcategories', 'aaaaaaaaaa') }}">
                                <option></option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Brand</label>
                            <select name="brand_id" class="form-select select2" data-placeholder="Select brand (optional)">
                                <option></option>
                                @foreach($brands as $b)<option value="{{ $b->id }}" @selected(old('brand_id', $product->brand_id) == $b->id)>{{ $b->name }}</option>@endforeach
                            </select>
                            <div class="form-text">Shown to customers. <a href="{{ route('admin.brands.create') }}" target="_blank">Add brand</a></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Vendor</label>
                            <select name="vendor_id" class="form-select select2" data-placeholder="Select vendor">
                                <option></option>
                                @foreach($vendors as $v)<option value="{{ $v->id }}" @selected(old('vendor_id', $product->vendor_id) == $v->id)>{{ $v->name }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Vendor product link</label>
                            <input type="url" name="vendor_product_url" class="form-control" value="{{ old('vendor_product_url', $product->vendor_product_url) }}" placeholder="https://vendor.com/item" maxlength="255">
                            <div class="form-text">Admin only. Used when forwarding orders.</div>
                        </div>
                    </div>
                </div>

                <div class="card position-sticky" style="top: 88px">
                    <div class="card-body">
                        @foreach(['is_active' => 'Active (visible on store)', 'in_stock' => 'In stock', 'is_featured' => 'Featured on home page'] as $flag => $label)
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="{{ $flag }}" value="1" id="{{ $flag }}" @checked(old($flag, $product->{$flag}))>
                                <label class="form-check-label" for="{{ $flag }}">{{ $label }}</label>
                            </div>
                        @endforeach
                        <hr>
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary" name="after" value="list"><i class="bi bi-check2 me-1"></i> {{ $editing ? 'Save changes' : 'Save product' }}</button>
                            @unless($editing)
                                <button class="btn btn-soft-primary" name="after" value="new">Save & add another</button>
                            @endunless
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('page-scripts')
    <script>
        (function ($) {
            const fmt = (n) => '৳' + (Math.round(n * 100) / 100).toLocaleString();

            function calc() {
                const unit = parseFloat($('#unit_price').val()) || 0;
                const disc = parseFloat($('#discount_price').val()) || 0;
                const cost = parseFloat($('#drop_shipping_price').val()) || 0;
                // Same rule as the server: discount_price must be lower than unit_price.
                document.getElementById('discount_price').setCustomValidity(
                    disc > 0 && unit > 0 && disc >= unit ? 'Discount price must be lower than the regular price.' : '');
                const sell = disc > 0 && disc < unit ? disc : unit;
                const profit = sell - cost;
                const margin = sell > 0 ? (profit / sell * 100).toFixed(1) : 0;

                $('#pSell').text(fmt(sell));
                $('#pDisc').text(disc > 0 && disc < unit ? '-' + fmt(unit - disc) + ' (' + Math.round(100 - disc / unit * 100) + '%)' : '—');
                $('#pCost').text(fmt(cost));
                $('#pProfit').text(fmt(profit)).toggleClass('text-danger', profit < 0).toggleClass('text-success', profit >= 0);
                $('#pMargin').text(margin + '%').attr('class', 'badge fs-6 ' + (profit < 0 ? 'badge-soft-danger' : 'badge-soft-success'));
            }
            $('#unit_price, #discount_price, #drop_shipping_price').on('input', calc);
            calc();

            // Dependent subcategory dropdown (data served from cache)
            const $sub = $('#subcategory_id');
            // The endpoint is addressed by the category unique_id; the option value stays the numeric id for the form.
            const catUid = (id) => $('#category_id option').filter((_, o) => o.value === String(id)).data('uid');
            function loadSubs(catId, selected) {
                $sub.empty().append('<option></option>');
                if (!catId) { $sub.trigger('change'); return; }
                $.getJSON($sub.data('url').replace('aaaaaaaaaa', catUid(catId)), (rows) => {
                    rows.forEach((r) => $sub.append(new Option(r.text, r.id, false, String(r.id) === String(selected))));
                    $sub.trigger('change');
                });
            }
            $('#category_id').on('change', function () { loadSubs(this.value); });
            loadSubs($('#category_id').val(), $sub.data('selected'));

            // Drag & drop ordering of saved images
            const grid = document.getElementById('imageGrid');
            if (grid.dataset.sortUrl && window.Sortable) {
                Sortable.create(grid, {
                    animation: 150,
                    filter: '.new, .del',
                    onEnd() {
                        const ids = $(grid).find('.item[data-id]').map((_, el) => el.dataset.id).get();
                        $.post(grid.dataset.sortUrl, { ids }).done(() => notify('Image order saved'));
                    },
                });
            }
        })(jQuery);
    </script>
@endpush

@unless($editing)
@push('page-scripts')
    <script>
        // "Check a product code": exact match → taken (with a link to that product), otherwise free (+ similar codes).
        (function ($) {
            const $box = $('#codeCheck');
            const $in = $('#codeCheckInput');
            const $btn = $('#codeCheckBtn');
            const $out = $('#codeCheckResult');
            const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
            let seq = 0;

            function check() {
                const code = $in.val().trim();
                if (!code) { $out.empty(); $in.trigger('focus'); return; }
                const mine = ++seq;
                Busy.start($btn[0], 'Checking…');
                $.getJSON($box.data('url'), { code }).done((r) => {
                    if (mine !== seq) return;
                    const similar = r.similar.length
                        ? `<div class="similar text-muted">Similar codes: ${r.similar.map((p) => `<a href="${esc(p.edit_url)}" target="_blank" title="${esc(p.name)}">${esc(p.code)}</a>`).join(' · ')}</div>` : '';
                    $out.html(r.exists
                        ? `<div class="hit taken"><img src="${esc(r.product.thumb)}" alt="">
                               <div class="min-w-0"><div class="fw-semibold text-danger"><i class="bi bi-x-circle-fill me-1"></i>“${esc(r.product.code)}” already exists${r.product.active ? '' : ' (inactive product)'}</div>
                               <div class="small text-truncate">${esc(r.product.name)}</div></div>
                               <a href="${esc(r.product.edit_url)}" class="btn btn-sm btn-soft-primary flex-shrink-0"><i class="bi bi-pencil me-1"></i>Edit it</a></div>${similar}`
                        : `<div class="hit free"><i class="bi bi-check-circle-fill fs-5"></i>
                               <div class="min-w-0 fw-semibold">“${esc(r.code)}” is not used yet — you can add it.</div>
                               <button type="button" class="btn btn-sm btn-success flex-shrink-0" data-use-code="${esc(r.code)}">Use this code</button></div>${similar}`);
                }).fail((xhr) => { if (mine === seq) $out.html(`<div class="text-danger small">${esc(xhr.responseJSON?.message || 'Could not check the code. Please try again.')}</div>`); })
                  .always(() => Busy.stop($btn[0]));
            }

            $btn.on('click', check);
            $in.on('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); check(); } })
               .on('input', () => { if (!$in.val().trim()) $out.empty(); });
            $out.on('click', '[data-use-code]', function () {
                const $code = $('#productForm [name="code"]');
                $code.val(this.dataset.useCode).trigger('input').trigger('change').trigger('focus');
                notify('Code added to the form');
            });
        })(jQuery);
    </script>
@endpush
@endunless
