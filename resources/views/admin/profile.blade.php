@extends('admin.layouts.app')

@section('title', 'Profile')

@section('content')
    <div class="page-header">
        <div>
            <h1>My profile</h1>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li><li class="breadcrumb-item active">Profile</li></ol></nav>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-7">
            <form method="post" action="{{ route('admin.profile.update') }}" class="card" data-validate novalidate>
                @csrf
                <div class="card-header"><h5>Account</h5></div>
                <div class="card-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $user->name) }}" placeholder="Your name" required minlength="2" maxlength="100">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="email">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $user->email) }}" placeholder="you@example.com" required maxlength="150" autocomplete="username">
                    </div>
                    <div class="col-12"><hr class="my-1"><div class="fw-semibold mt-2">Change password <span class="text-muted fw-normal small">(leave blank to keep current)</span></div></div>
                    <div class="col-12">
                        <label class="form-label" for="current_password">Current password</label>
                        <input type="password" name="current_password" id="current_password" class="form-control" placeholder="Required only when changing password" maxlength="100" autocomplete="current-password">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="password">New password</label>
                        <input type="password" name="password" id="password" class="form-control" placeholder="At least 8 characters" minlength="8" maxlength="100" autocomplete="new-password">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="password_confirmation">Confirm new password</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="Repeat new password" minlength="8" maxlength="100" autocomplete="new-password">
                    </div>
                </div>
                <div class="card-footer text-end"><button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Update profile</button></div>
            </form>
        </div>
    </div>
@endsection
