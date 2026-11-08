@extends('admin.layouts.app')

@php($editing = $slider->exists)
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
                            <div class="col-12">
                                <label class="form-label" for="title">Title</label>
                                <input type="text" name="title" id="title" class="form-control" value="{{ old('title', $slider->title) }}" placeholder="Big headline, e.g. Mega Gadget Sale" maxlength="80" data-counter>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="subtitle">Subtitle</label>
                                <input type="text" name="subtitle" id="subtitle" class="form-control" value="{{ old('subtitle', $slider->subtitle) }}" placeholder="Supporting line, e.g. Up to 30% off this week" maxlength="150" data-counter>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="button_text">Button text</label>
                                <input type="text" name="button_text" id="button_text" class="form-control" value="{{ old('button_text', $slider->button_text) }}" placeholder="Shop now" maxlength="30">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="sort_order">Sort order</label>
                                <input type="number" name="sort_order" id="sort_order" class="form-control" value="{{ old('sort_order', $slider->sort_order ?? 0) }}" placeholder="0" min="0" max="9999" step="1">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="link">Link</label>
                                <input type="text" name="link" id="link" class="form-control" value="{{ old('link', $slider->link) }}" placeholder="category/electronics or https://…" maxlength="255">
                                <div class="form-text">Relative path inside the store, or a full URL. A button text is required when a link is set.</div>
                            </div>
                            <div class="col-12">
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
                        <img id="slidePreview" src="{{ $editing ? img_url($slider->image) : '' }}" class="w-100 rounded-3 border mb-3 {{ $editing ? '' : 'd-none' }}" style="aspect-ratio: 16/6; object-fit: cover" alt="">
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
