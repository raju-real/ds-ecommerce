@extends('admin.layouts.app')

@php($editing = $thana->exists)
@section('title', $editing ? 'Edit thana' : 'Add thana')

@section('content')
    <div class="page-header">
        <div>
            <h1>{{ $editing ? 'Edit thana' : 'Add thana' }}</h1>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.thanas.index') }}">Thanas</a></li><li class="breadcrumb-item active">{{ $editing ? $thana->name : 'New' }}</li></ol></nav>
        </div>
        <a href="{{ route('admin.thanas.index') }}" class="btn btn-soft-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <form method="post" class="card" data-validate novalidate action="{{ $editing ? route('admin.thanas.update', $thana->unique_id) : route('admin.thanas.store') }}">
                @csrf
                @if($editing) @method('PUT') @endif
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="district_id">District <span class="text-danger">*</span></label>
                        <select name="district_id" id="district_id" class="form-select select2" required data-placeholder="Select district" data-msg-required="Please select a district.">
                            <option></option>
                            @foreach($districts as $d)<option value="{{ $d->id }}" @selected(old('district_id', $thana->district_id) == $d->id)>{{ $d->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="name">Thana / Upazila name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $thana->name) }}" placeholder="e.g. Dhanmondi" required minlength="2" maxlength="100">
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $thana->is_active))>
                        <label class="form-check-label" for="is_active">Available at checkout</label>
                    </div>
                </div>
                <div class="card-footer text-end"><button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Save</button></div>
            </form>
        </div>
    </div>
@endsection
