@extends('layouts.frontend')

{{-- /complaint — customer complaint form (menu → Complaint). After sending, shows the complaint number (00001 …). --}}
@section('title', 'Complaint')
@section('description', 'Tell us about a problem with your order or our service. Every complaint gets a number and is reviewed by our team.')
@section('robots', 'noindex, follow')

@section('content')
    <div class="container py-4">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Complaint</li>
            </ol>
        </nav>

        <div class="pd-card complaint-card">
            @if(session('complaint_no'))
                <div class="complaint-done">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem"></i>
                    <h1 class="h4 fw-bold mt-2">Your complaint has been received</h1>
                    <div class="text-muted">Your complaint number</div>
                    <div class="complaint-no" id="complaintNo">{{ session('complaint_no') }}</div>
                    <p class="text-muted mb-4">Please keep this number. Our team will review it and contact you on your mobile.</p>
                    <a href="{{ route('home') }}" class="btn btn-primary"><i class="bi bi-house me-1"></i>Back to shopping</a>
                    <a href="{{ route('complaint') }}" class="btn btn-outline-secondary ms-2">Send another</a>
                </div>
            @else
                <h1 class="h4 fw-bold mb-1"><i class="bi bi-megaphone text-primary me-1"></i> Make a complaint</h1>
                <p class="text-muted">Had a problem with an order, a product or our service? Tell us — every complaint gets a number and is reviewed by our team.</p>

                <form method="post" action="{{ route('complaint.store') }}" enctype="multipart/form-data" novalidate>
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="cp-name">Your name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="cp-name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" maxlength="80" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="cp-mobile">Mobile number <span class="text-danger">*</span></label>
                            <input type="tel" name="mobile" id="cp-mobile" class="form-control @error('mobile') is-invalid @enderror" value="{{ old('mobile') }}" placeholder="01XXXXXXXXX" maxlength="11" inputmode="numeric" required>
                            @error('mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="cp-message">Your complaint <span class="text-danger">*</span></label>
                            <textarea name="message" id="cp-message" rows="6" class="form-control @error('message') is-invalid @enderror" maxlength="3000" placeholder="What happened? Add your order number if it is about an order (e.g. NT-00012)." required>{{ old('message') }}</textarea>
                            @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="cp-images">Photos <span class="text-muted fw-normal">(optional, up to 2)</span></label>
                            <input type="file" name="images[]" id="cp-images" class="form-control @error('images') is-invalid @enderror @error('images.*') is-invalid @enderror" accept="image/jpeg,image/png,image/webp" multiple>
                            @error('images')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            @foreach(['images.0', 'images.1'] as $k)@error($k)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror @endforeach
                            <div class="rv-previews" id="cp-previews"></div>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary px-4"><i class="bi bi-send me-1"></i>Send complaint</button>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const input = document.getElementById('cp-images');
            if (!input) return;
            const box = document.getElementById('cp-previews');
            input.addEventListener('change', () => {
                const files = [...input.files];
                box.innerHTML = '';
                if (files.length > 2) { input.value = ''; box.innerHTML = '<div class="text-danger small">Please choose up to 2 photos.</div>'; return; }
                files.forEach((f) => { const img = document.createElement('img'); img.src = URL.createObjectURL(f); img.alt = ''; box.appendChild(img); });
            });
        })();
    </script>
@endpush
