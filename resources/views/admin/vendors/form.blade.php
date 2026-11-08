@extends('admin.layouts.app')

@php($editing = $vendor->exists)
@section('title', $editing ? 'Edit vendor' : 'Add vendor')

@section('content')
    <div class="page-header">
        <div>
            <h1>{{ $editing ? 'Edit vendor' : 'Add vendor' }}</h1>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.vendors.index') }}">Vendors</a></li><li class="breadcrumb-item active">{{ $editing ? $vendor->name : 'New' }}</li></ol></nav>
        </div>
        <a href="{{ route('admin.vendors.index') }}" class="btn btn-soft-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <form method="post" class="card" data-validate novalidate action="{{ $editing ? route('admin.vendors.update', $vendor->unique_id) : route('admin.vendors.store') }}">
                @csrf
                @if($editing) @method('PUT') @endif
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="name">Vendor / shop name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $vendor->name) }}" placeholder="e.g. Gadget Hub BD" required minlength="2" maxlength="150">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="contact_person">Contact person</label>
                            <input type="text" name="contact_person" id="contact_person" class="form-control" value="{{ old('contact_person', $vendor->contact_person) }}" placeholder="Full name" maxlength="120">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="phone">Phone</label>
                            <input type="tel" name="phone" id="phone" class="form-control" value="{{ old('phone', $vendor->phone) }}" placeholder="01XXXXXXXXX" maxlength="20" pattern="\+?[0-9 \-]{6,20}" data-msg-pattern="Enter a valid phone number (digits, spaces, dashes).">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="email">Email</label>
                            <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $vendor->email) }}" placeholder="vendor@example.com" maxlength="150">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="website">Website / store URL</label>
                            <input type="url" name="website" id="website" class="form-control" value="{{ old('website', $vendor->website) }}" placeholder="https://vendor-store.com" maxlength="255">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="address">Address</label>
                            <input type="text" name="address" id="address" class="form-control" value="{{ old('address', $vendor->address) }}" placeholder="Shop no, market, area, city" maxlength="500">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="note">Internal note</label>
                            <textarea name="note" id="note" rows="3" class="form-control" placeholder="Payment terms, bKash number, ordering process…" maxlength="2000" data-counter>{{ old('note', $vendor->note) }}</textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $vendor->is_active))>
                                <label class="form-check-label" for="is_active">Active vendor</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> {{ $editing ? 'Save changes' : 'Create vendor' }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
