@extends('admin.layouts.app')

@php($editing = $blog->exists)
@section('title', $editing ? 'Edit post' : 'Write a post')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
@endpush
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
@endpush

@section('content')
    <div class="page-header">
        <div>
            <h1>{{ $editing ? 'Edit post' : 'Write a post' }}</h1>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.blogs.index') }}">Blog</a></li><li class="breadcrumb-item active">{{ $editing ? \Illuminate\Support\Str::limit($blog->title, 40) : 'New' }}</li></ol></nav>
        </div>
        <div class="d-flex gap-2">
            @if($editing && $blog->isPublished())@include('admin.partials.share-menu', array_filter(['title' => $blog->title, 'image' => $blog->thumb_url, 'targets' => [['type' => 'blog', 'uid' => $blog->unique_id, 'label' => 'Blog post', 'url' => $blog->url]], 'primary' => 'blog', 'shareText' => $blog->title.' — read more:', 'label' => 'Share links']))@endif
            @if($editing && $blog->isPublished())<a href="{{ $blog->url }}" target="_blank" class="btn btn-soft-secondary"><i class="bi bi-box-arrow-up-right me-1"></i> View on site</a>@endif
            <a href="{{ route('admin.blogs.index') }}" class="btn btn-soft-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
        </div>
    </div>

    <form method="post" enctype="multipart/form-data" data-validate novalidate action="{{ $editing ? route('admin.blogs.update', $blog->unique_id) : route('admin.blogs.store') }}">
        @csrf
        @if($editing) @method('PUT') @endif
        <div class="row g-4">
            <div class="col-xl-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="title">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="title" class="form-control form-control-lg @error('title') is-invalid @enderror" value="{{ old('title', $blog->title) }}"
                                   placeholder="e.g. How to choose the right smart watch" required minlength="3" maxlength="160" data-counter data-slug-source="#slug">
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <div class="slug-label-row">
                                <label class="form-label" for="slug">URL slug</label>
                                @include('admin.partials.slug-generator', ['type' => 'blog', 'id' => $blog->id])
                            </div>
                            <div class="input-group">
                                <span class="input-group-text small">{{ url('/blog') }}/</span>
                                <input type="text" name="slug" id="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $blog->slug) }}"
                                       placeholder="{{ $blog->slug ?: 'generated-from-the-title' }}" maxlength="180" pattern="[A-Za-z0-9_-]+" data-msg-pattern="Use letters, numbers, dashes and underscores only." data-slug>
                            </div>
                            <div class="form-text">Leave empty to create it from the title. Changing it later breaks links that were already shared.</div>
                            @error('slug')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="form-label" for="description">Description <span class="text-danger">*</span></label>
                            <textarea name="description" id="description" class="editor" data-height="460" placeholder="Write your post… Use the toolbar for headings, text size, links, images and the Blog button to link another post."
                                      data-upload-url="{{ route('admin.editor.image') }}" data-upload-max="{{ config('shop.uploads.editor') }}"
                                      data-blog-links-url="{{ route('admin.blogs.link-search') }}" data-blog-except="{{ $blog->unique_id }}">{{ old('description', $blog->description) }}</textarea>
                            @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            <div class="mt-2">@include('admin.partials.upload-hint', ['type' => 'editor', 'note' => 'Images added in the text are uploaded and saved as WebP. Use the Blog button to add a "related post" link card.'])</div>
                        </div>
                    </div>
                </div>

                @include('admin.partials.seo-fields', ['model' => $blog,
                    'preview' => ['type' => 'blog', 'path' => url('/blog').'/', 'name' => '#title', 'desc' => ['[name="description"]'], 'image' => '#coverPreview'],
                    'titleHint' => 'Defaults to the post title', 'descHint' => 'Defaults to the first lines of the post (~155 characters)', 'imageHint' => 'Leave empty to use the cover image.'])
            </div>

            <div class="col-xl-4">
                <div class="card mb-4">
                    <div class="card-header"><h5>Cover image @unless($blog->image)<span class="text-danger">*</span>@endunless</h5></div>
                    <div class="card-body">
                        <label class="thumb-box blog-cover-box w-100 @error('image') is-invalid @enderror" for="image">
                            <img id="coverPreview" src="{{ $blog->image ? $blog->image_url : '' }}" alt="" class="{{ $blog->image ? '' : 'd-none' }}">
                            <span class="thumb-empty"><i class="bi bi-image"></i>Choose cover image<small>1200 × 630 px</small></span>
                            <span class="thumb-change"><i class="bi bi-arrow-repeat"></i> Replace</span>
                        </label>
                        <input type="file" name="image" id="image" class="form-control mt-2 @error('image') is-invalid @enderror" {!! upload_attrs('blog') !!} data-preview="#coverPreview"
                               {{ $blog->image ? '' : 'required' }} data-msg-required="Please choose a cover image.">
                        @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="mt-2">@include('admin.partials.upload-hint', ['type' => 'blog', 'note' => 'Cropped to 1200 × 630 from the centre. Shown on the blog cards and when the post is shared on Facebook/WhatsApp.'])</div>
                    </div>
                </div>

                <div class="card position-sticky" style="top: 88px">
                    <div class="card-header"><h5>Publish</h5></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="published_at">Publish date</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                                <input type="text" name="published_at" id="published_at" class="form-control datepicker-input" value="{{ old('published_at', $blog->published_at?->format('Y-m-d')) }}"
                                       placeholder="YYYY-MM-DD" maxlength="10" autocomplete="off" pattern="\d{4}-\d{2}-\d{2}" data-msg-pattern="Use YYYY-MM-DD.">
                            </div>
                            <div class="form-text">A future date schedules the post — it appears on the site on that day.</div>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $blog->is_active))>
                            <label class="form-check-label" for="is_active">Visible on the site</label>
                        </div>
                        <hr>
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary" name="after" value="list"><i class="bi bi-check2 me-1"></i> {{ $editing ? 'Save changes' : 'Publish post' }}</button>
                            @unless($editing)
                                <button class="btn btn-soft-primary" name="after" value="new">Save &amp; write another</button>
                            @endunless
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
