@extends('admin.layouts.app')

@php($editing = $category->exists)
@section('title', $editing ? 'Edit category' : 'Add category')

@section('content')
    <div class="page-header">
        <div>
            <h1>{{ $editing ? 'Edit category' : 'Add category' }}</h1>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.categories.index') }}">Categories</a></li><li class="breadcrumb-item active">{{ $editing ? $category->name : 'New' }}</li></ol></nav>
        </div>
        <a href="{{ route('admin.categories.index') }}" class="btn btn-soft-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <form method="post" enctype="multipart/form-data" data-validate novalidate action="{{ $editing ? route('admin.categories.update', $category->unique_id) : route('admin.categories.store') }}">
        @csrf
        @if($editing) @method('PUT') @endif
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header"><h5>Details</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $category->name) }}" placeholder="e.g. Electronics" data-slug-source="#slug" required minlength="2" maxlength="120" data-counter>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="sort_order">Sort order</label>
                                <input type="number" name="sort_order" id="sort_order" class="form-control" value="{{ old('sort_order', $category->sort_order ?? 0) }}" placeholder="0" min="0" max="9999" step="1">
                                <div class="form-text">Lower numbers show first.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="slug">Slug</label>
                                <div class="input-group">
                                    <span class="input-group-text">/category/</span>
                                    <input type="text" name="slug" id="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $category->slug) }}" data-slug placeholder="auto-generated-from-name" maxlength="150" pattern="[A-Za-z0-9_-]+" data-msg-pattern="Use only letters, numbers, dashes and underscores.">
                                </div>
                                @error('slug')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><h5>SEO</h5></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="meta_title">Meta title</label>
                            <input type="text" name="meta_title" id="meta_title" class="form-control" value="{{ old('meta_title', $category->meta_title) }}" placeholder="Defaults to the category name" maxlength="70" data-counter>
                        </div>
                        <div>
                            <label class="form-label" for="meta_description">Meta description</label>
                            <textarea name="meta_description" id="meta_description" rows="3" class="form-control" maxlength="160" placeholder="Short summary shown in Google results (~155 characters)" data-counter>{{ old('meta_description', $category->meta_description) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header"><h5>Image</h5></div>
                    <div class="card-body text-center">
                        <img id="catPreview" src="{{ img_url($category->image) }}" class="preview-img mb-3" style="max-height: 160px" alt="">
                        <input type="file" name="image" class="form-control @error('image') is-invalid @enderror" {!! upload_attrs('category') !!} data-preview="#catPreview">
                        @error('image')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        <div class="mt-2">@include('admin.partials.upload-hint', ['type' => 'category', 'note' => 'Centre-cropped to a square — keep the subject in the middle.'])</div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $category->is_active))>
                            <label class="form-check-label" for="is_active">Visible on storefront</label>
                        </div>
                        <button class="btn btn-primary w-100"><i class="bi bi-check2 me-1"></i> {{ $editing ? 'Save changes' : 'Create category' }}</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
