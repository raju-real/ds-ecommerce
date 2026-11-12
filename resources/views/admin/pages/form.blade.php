@extends('admin.layouts.app')

@php($editing = $page->exists)
@section('title', $editing ? 'Edit page' : 'Add page')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
@endpush
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
@endpush

@section('content')
    <div class="page-header">
        <div>
            <h1>{{ $editing ? 'Edit page' : 'Add page' }}</h1>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.pages.index') }}">Pages</a></li><li class="breadcrumb-item active">{{ $editing ? \Illuminate\Support\Str::limit($page->name, 40) : 'New' }}</li></ol></nav>
        </div>
        <div class="d-flex gap-2">
            @if($editing && $page->is_active)<a href="{{ $page->url }}" target="_blank" class="btn btn-soft-secondary"><i class="bi bi-box-arrow-up-right me-1"></i> View on site</a>@endif
            <a href="{{ route('admin.pages.index') }}" class="btn btn-soft-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
        </div>
    </div>

    <form method="post" enctype="multipart/form-data" data-validate novalidate action="{{ $editing ? route('admin.pages.update', $page->unique_id) : route('admin.pages.store') }}">
        @csrf
        @if($editing) @method('PUT') @endif

        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    {{-- Name --}}
                    <div class="col-md-4">
                        <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $page->name) }}"
                               placeholder="e.g. About us, Privacy policy" required minlength="2" maxlength="160" data-counter data-slug-source="#slug" data-msg-required="Please enter the page name.">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Shown as the page heading and as the footer link.</div>
                    </div>

                    {{-- Image (optional) --}}
                    <div class="col-md-4">
                        <label class="form-label" for="image">Image <span class="text-muted fw-normal">(optional)</span></label>
                        <div class="d-flex gap-2 align-items-start">
                            <div class="page-img-box">
                                <img id="pageImagePreview" src="{{ $page->image ? $page->image_url : '' }}" alt="" class="{{ $page->image ? '' : 'd-none' }}">
                                @unless($page->image)<i class="bi bi-image text-muted" data-img-empty></i>@endunless
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <input type="file" name="image" id="image" class="form-control @error('image') is-invalid @enderror" {!! upload_attrs('page') !!} data-preview="#pageImagePreview">
                                @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                @if($page->image)
                                    <div class="form-check mt-1">
                                        <input class="form-check-input" type="checkbox" name="remove_image" value="1" id="remove_image">
                                        <label class="form-check-label small" for="remove_image">Remove image</label>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="form-text">Banner at the top of the page, 1200 × 630 (cropped from the centre).</div>
                    </div>

                    {{-- Status --}}
                    <div class="col-md-4">
                        <label class="form-label" for="is_active">Status <span class="text-danger">*</span></label>
                        <select name="is_active" id="is_active" class="form-select @error('is_active') is-invalid @enderror">
                            <option value="1" @selected((string) old('is_active', $page->is_active ? '1' : '0') === '1')>Active — shown on the site and in the footer</option>
                            <option value="0" @selected((string) old('is_active', $page->is_active ? '1' : '0') === '0')>Inactive — hidden</option>
                        </select>
                        @error('is_active')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">The footer order is set by dragging on the <a href="{{ route('admin.pages.index') }}">Pages</a> list.</div>
                    </div>

                    {{-- Slug --}}
                    <div class="col-12">
                        <div class="slug-label-row">
                            <label class="form-label" for="slug">Slug (URL)</label>
                            @include('admin.partials.slug-generator', ['type' => 'page', 'id' => $page->id])
                        </div>
                        <div class="input-group">
                            <span class="input-group-text small">{{ url('/') }}/</span>
                            <input type="text" name="slug" id="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $page->slug) }}"
                                   placeholder="{{ $page->slug ?: 'generated-from-the-name' }}" maxlength="180" pattern="[A-Za-z0-9_-]+" data-msg-pattern="Use letters, numbers, dashes and underscores only." data-slug>
                        </div>
                        <div class="form-text">Opens at <strong>{{ url('/') }}/your-slug</strong>. Leave empty to create it from the name. Names the shop already uses (blog, products, checkout …) get a number added. Changing it later breaks links that were already shared.</div>
                        @error('slug')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    {{-- Content --}}
                    <div class="col-12">
                        <label class="form-label" for="content">Content <span class="text-danger">*</span></label>
                        <textarea name="content" id="content" class="editor" data-height="420" maxlength="200000" data-upload-url="{{ route('admin.editor.image') }}" data-upload-max="{{ config('shop.uploads.editor') }}"
                                  placeholder="Write the page… Use the toolbar for headings, lists, links and images.">{{ old('content', $page->content) }}</textarea>
                        @error('content')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        <div class="mt-2">@include('admin.partials.upload-hint', ['type' => 'editor', 'note' => 'Images added in the text are uploaded and saved as WebP.'])</div>
                    </div>
                </div>
            </div>
        </div>

        @include('admin.partials.seo-fields', ['model' => $page,
            'preview' => ['type' => 'page', 'path' => url('/').'/', 'name' => '#name', 'desc' => ['[name="content"]'], 'image' => '#pageImagePreview'],
            'titleHint' => 'Defaults to the page name', 'descHint' => 'Defaults to the first lines of the content (~155 characters)', 'imageHint' => 'Leave empty to use the page image (or the site share image).'])

        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('admin.pages.index') }}" class="btn btn-soft-secondary">Cancel</a>
            @unless($editing)<button class="btn btn-soft-primary" name="after" value="new">Save &amp; add another</button>@endunless
            <button class="btn btn-primary px-4" name="after" value="list"><i class="bi bi-check2 me-1"></i> {{ $editing ? 'Save changes' : 'Create page' }}</button>
        </div>
    </form>
@endsection

@push('styles')
    <style>
        .page-img-box { flex: 0 0 76px; width: 76px; height: 40px; border-radius: 8px; border: 1px dashed #cbd5e1; background: #f8fafc; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .page-img-box img { width: 100%; height: 100%; object-fit: cover; }
    </style>
@endpush

@push('page-scripts')
    <script>
        // Hide the empty icon once an image is picked (the preview itself is handled by admin.js data-preview).
        document.getElementById('image').addEventListener('change', function () { const i = document.querySelector('[data-img-empty]'); if (i && this.files.length) i.remove(); });
    </script>
@endpush
