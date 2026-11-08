@extends('admin.layouts.app')

@php($editing = $district->exists)
@section('title', $editing ? 'Edit district' : 'Add district')

@section('content')
    <div class="page-header">
        <div>
            <h1>{{ $editing ? 'Edit district' : 'Add district' }}</h1>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.districts.index') }}">Districts</a></li><li class="breadcrumb-item active">{{ $editing ? $district->name : 'New' }}</li></ol></nav>
        </div>
        <a href="{{ route('admin.districts.index') }}" class="btn btn-soft-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <form method="post" class="card" data-validate novalidate action="{{ $editing ? route('admin.districts.update', $district->unique_id) : route('admin.districts.store') }}">
                @csrf
                @if($editing) @method('PUT') @endif
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="name">Name (English) <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $district->name) }}" placeholder="e.g. Dhaka" required minlength="2" maxlength="100">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="bn_name">Name (বাংলা)</label>
                            <input type="text" name="bn_name" id="bn_name" class="form-control" value="{{ old('bn_name', $district->bn_name) }}" placeholder="যেমন: ঢাকা" maxlength="100">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="delivery_charge">Delivery charge <span class="text-danger">*</span></label>
                            <div class="input-group"><span class="input-group-text">৳</span><input type="number" min="0" max="9999" step="0.01" name="delivery_charge" id="delivery_charge" class="form-control" value="{{ old('delivery_charge', $district->delivery_charge) }}" placeholder="e.g. 130" required></div>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $district->is_active))>
                                <label class="form-check-label" for="is_active">Available at checkout</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end"><button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Save</button></div>
            </form>
        </div>
    </div>
@endsection
