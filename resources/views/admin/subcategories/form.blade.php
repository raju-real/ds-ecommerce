@extends('admin.layouts.app')

@php($editing = $subcategory->exists)
@section('title', $editing ? 'Edit subcategory' : 'Add subcategory')

@section('content')
    <div class="page-header">
        <div>
            <h1>{{ $editing ? 'Edit subcategory' : 'Add subcategory' }}</h1>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.subcategories.index') }}">Subcategories</a></li><li class="breadcrumb-item active">{{ $editing ? $subcategory->name : 'New' }}</li></ol></nav>
        </div>
        <a href="{{ route('admin.subcategories.index') }}" class="btn btn-soft-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-7">
            <form method="post" class="card" data-validate novalidate action="{{ $editing ? route('admin.subcategories.update', $subcategory->unique_id) : route('admin.subcategories.store') }}">
                @csrf
                @if($editing) @method('PUT') @endif
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="category_id">Parent category <span class="text-danger">*</span></label>
                            <select name="category_id" id="category_id" class="form-select select2" required data-placeholder="Select parent category" data-msg-required="Please select a parent category.">
                                <option></option>
                                @foreach($categories as $c)<option value="{{ $c->id }}" @selected(old('category_id', $subcategory->category_id) == $c->id)>{{ $c->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $subcategory->name) }}" placeholder="e.g. Smart Watch" data-slug-source="#slug" required minlength="2" maxlength="120" data-counter>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="sort_order">Sort order</label>
                            <input type="number" name="sort_order" id="sort_order" class="form-control" value="{{ old('sort_order', $subcategory->sort_order ?? 0) }}" placeholder="0" min="0" max="9999" step="1">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="slug">Slug</label>
                            <input type="text" name="slug" id="slug" class="form-control" value="{{ old('slug', $subcategory->slug) }}" data-slug placeholder="auto-generated-from-name" maxlength="150" pattern="[A-Za-z0-9_-]+" data-msg-pattern="Use only letters, numbers, dashes and underscores.">
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $subcategory->is_active))>
                                <label class="form-check-label" for="is_active">Visible on storefront</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> {{ $editing ? 'Save changes' : 'Create subcategory' }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
