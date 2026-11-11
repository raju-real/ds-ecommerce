{{-- Meta title, description, keywords (tags with suggestions) and share image. All optional.
     Expects $model; optional $titleHint, $descHint, $imageHint (what is used when a field is left empty),
     $preview (live Google / share preview, see admin/partials/seo-preview). --}}
@php
    $keywords = \App\Support\Seo::list(old('meta_keywords_present') ? old('meta_keywords', []) : $model->meta_keywords);
@endphp
<div class="card seo-card" id="seo">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h5><i class="bi bi-search me-1 text-primary"></i> SEO &amp; sharing <span class="badge badge-soft-secondary ms-1">Optional</span></h5>
        <span class="small text-muted">How this page looks on Google, Facebook and WhatsApp</span>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label" for="meta_title">Meta title</label>
            <input type="text" name="meta_title" id="meta_title" class="form-control @error('meta_title') is-invalid @enderror" value="{{ old('meta_title', $model->meta_title) }}" maxlength="70" placeholder="{{ $titleHint ?? 'Defaults to the name' }}" data-counter>
            @error('meta_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="form-label" for="meta_description">Meta description</label>
            <textarea name="meta_description" id="meta_description" rows="2" class="form-control @error('meta_description') is-invalid @enderror" maxlength="160" placeholder="{{ $descHint ?? 'Short summary shown in Google results (~155 characters)' }}" data-counter>{{ old('meta_description', $model->meta_description) }}</textarea>
            @error('meta_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="form-label" for="meta_keywords">Meta keywords</label>
            <input type="hidden" name="meta_keywords_present" value="1">
            <select name="meta_keywords[]" id="meta_keywords" class="form-select @error('meta_keywords') is-invalid @enderror @error('meta_keywords.*') is-invalid @enderror" multiple
                    data-keywords="{{ route('admin.seo.keywords') }}" data-max="{{ \App\Support\Seo::MAX_KEYWORDS }}" data-placeholder="Type a keyword and press Enter…">
                @foreach($keywords as $word)
                    <option value="{{ $word }}" selected>{{ $word }}</option>
                @endforeach
            </select>
            @error('meta_keywords')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            @error('meta_keywords.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            <div class="form-text">Press <kbd>Enter</kbd> or <kbd>,</kbd> after each keyword. Keywords you used before are suggested. Up to {{ \App\Support\Seo::MAX_KEYWORDS }}.</div>
        </div>
        <div>
            <label class="form-label" for="meta_image">Share image</label>
            <div class="seo-image">
                <div class="seo-image-box">
                    <img id="metaImagePreview" src="{{ $model->meta_image ? img_url($model->meta_image) : '' }}" alt="" class="{{ $model->meta_image ? '' : 'd-none' }}">
                    @unless($model->meta_image)<span class="text-muted small" data-seo-empty><i class="bi bi-image d-block fs-4"></i>1200 × 630</span>@endunless
                </div>
                <div class="flex-grow-1 min-w-0">
                    <input type="file" name="meta_image" id="meta_image" class="form-control @error('meta_image') is-invalid @enderror" {!! upload_attrs('meta_image') !!} data-preview="#metaImagePreview">
                    @error('meta_image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @if($model->meta_image)
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="remove_meta_image" value="1" id="remove_meta_image">
                            <label class="form-check-label small" for="remove_meta_image">Remove this share image</label>
                        </div>
                    @endif
                    <div class="mt-2">@include('admin.partials.upload-hint', ['type' => 'meta_image', 'note' => $imageHint ?? 'Leave empty to use the main image.'])</div>
                </div>
            </div>
        </div>
        @isset($preview)
            @include('admin.partials.seo-preview')
        @endisset
    </div>
</div>
