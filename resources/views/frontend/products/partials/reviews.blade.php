{{-- Ratings & reviews on the product page. Expects $product and $reviews (ReviewRepository::forProduct).
     Anyone with a DELIVERED order of this product can review it once (order no + the order's mobile). --}}
@php
    $err = $errors->getBag('review');
    $count = $reviews['count'];
    $old = fn ($k, $d = '') => $err->any() ? old($k, $d) : $d;
@endphp
<div class="pd-card mt-4" id="reviews">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h2 class="h4 fw-bold mb-0">Ratings &amp; Reviews</h2>
        <a href="#write-review" class="btn btn-outline-primary btn-sm" data-bs-toggle="collapse" role="button" aria-expanded="{{ $err->any() ? 'true' : 'false' }}" aria-controls="write-review"><i class="bi bi-pencil-square me-1"></i>Write a review</a>
    </div>

    @if(session('review_success'))
        <div class="alert alert-success border-0 rounded-3"><i class="bi bi-check-circle me-1"></i>{{ session('review_success') }}</div>
    @endif

    {{-- Summary --}}
    @if($count)
        <div class="rv-summary mb-4">
            <div class="rv-score">
                <div class="rv-avg">{{ number_format($reviews['average'], 1) }}</div>
                @include('frontend.partials.stars', ['rating' => $reviews['average'], 'class' => 'fs-5'])
                <div class="small text-muted">{{ $count }} {{ \Illuminate\Support\Str::plural('review', $count) }}</div>
            </div>
            <div class="rv-bars">
                @foreach($reviews['stars'] as $star => $n)
                    <div class="rv-bar"><span>{{ $star }} <i class="bi bi-star-fill"></i></span><div class="rv-track"><div class="rv-fill" style="width: {{ $count ? round($n / $count * 100) : 0 }}%"></div></div><span class="text-muted">{{ $n }}</span></div>
                @endforeach
            </div>
        </div>
    @else
        <p class="text-muted mb-3">No reviews yet. Bought this product? Be the first to share your experience.</p>
    @endif

    {{-- Write a review --}}
    <div class="collapse {{ $err->any() ? 'show' : '' }}" id="write-review">
        <form method="post" action="{{ route('product.reviews.store', $product->slug) }}" enctype="multipart/form-data" class="rv-form mb-4" novalidate>
            @csrf
            <div class="small text-muted mb-3"><i class="bi bi-shield-check me-1"></i>Reviews are from customers who received this product: enter your order number and the mobile used for the order.</div>
            <div class="mb-3">
                <label class="form-label fw-semibold d-block">Your rating <span class="text-danger">*</span></label>
                <div class="rv-stars-input" role="radiogroup" aria-label="Your rating">
                    @for($i = 5; $i >= 1; $i--)
                        <input type="radio" name="rating" value="{{ $i }}" id="rv-star-{{ $i }}" @checked((int) $old('rating') === $i)>
                        <label for="rv-star-{{ $i }}" title="{{ $i }} {{ \Illuminate\Support\Str::plural('star', $i) }}"><i class="bi bi-star-fill"></i></label>
                    @endfor
                </div>
                @if($err->has('rating'))<div class="text-danger small mt-1">{{ $err->first('rating') }}</div>@endif
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="rv-name">Your name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="rv-name" class="form-control {{ $err->has('name') ? 'is-invalid' : '' }}" value="{{ $old('name') }}" maxlength="80" required>
                    @if($err->has('name'))<div class="invalid-feedback">{{ $err->first('name') }}</div>@endif
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="rv-order">Order number <span class="text-danger">*</span></label>
                    <input type="text" name="order_no" id="rv-order" class="form-control {{ $err->has('order_no') ? 'is-invalid' : '' }}" value="{{ $old('order_no') }}" placeholder="e.g. NT-00012" maxlength="20" required>
                    @if($err->has('order_no'))<div class="invalid-feedback">{{ $err->first('order_no') }}</div>@endif
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="rv-mobile">Mobile number <span class="text-danger">*</span></label>
                    <input type="tel" name="mobile" id="rv-mobile" class="form-control {{ $err->has('mobile') ? 'is-invalid' : '' }}" value="{{ $old('mobile') }}" placeholder="01XXXXXXXXX" maxlength="11" inputmode="numeric" required>
                    @if($err->has('mobile'))<div class="invalid-feedback">{{ $err->first('mobile') }}</div>@endif
                </div>
                <div class="col-12">
                    <label class="form-label" for="rv-text">Your review <span class="text-danger">*</span></label>
                    <textarea name="review" id="rv-text" rows="4" class="form-control {{ $err->has('review') ? 'is-invalid' : '' }}" maxlength="2000" placeholder="How is the product? Quality, delivery, packaging…" required>{{ $old('review') }}</textarea>
                    @if($err->has('review'))<div class="invalid-feedback">{{ $err->first('review') }}</div>@endif
                </div>
                <div class="col-12">
                    <label class="form-label" for="rv-images">Photos <span class="text-muted fw-normal">(optional, up to 3)</span></label>
                    <input type="file" name="images[]" id="rv-images" class="form-control {{ $err->has('images') || $err->has('images.*') ? 'is-invalid' : '' }}" accept="image/jpeg,image/png,image/webp" multiple data-max-files="3">
                    @foreach(['images', 'images.0', 'images.1', 'images.2'] as $k)@if($err->has($k))<div class="invalid-feedback d-block">{{ $err->first($k) }}</div>@endif @endforeach
                    <div class="rv-previews" id="rv-previews"></div>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary px-4"><i class="bi bi-send me-1"></i>Submit review</button>
                </div>
            </div>
        </form>
    </div>

    {{-- Reviews --}}
    @foreach($reviews['reviews'] as $rv)
        <article class="rv-item">
            <div class="d-flex justify-content-between flex-wrap gap-2">
                <div>
                    <span class="rv-name">{{ $rv->name }}</span>
                    <span class="rv-verified"><i class="bi bi-patch-check-fill"></i> Verified purchase</span>
                </div>
                <time class="small text-muted" datetime="{{ $rv->created_at->toDateString() }}">{{ $rv->created_at->format('d M Y') }}</time>
            </div>
            @include('frontend.partials.stars', ['rating' => $rv->rating])
            <p class="mb-2 mt-1">{!! nl2br(e($rv->review)) !!}</p>
            @if($rv->images)
                <div class="rv-photos">
                    @foreach($rv->images as $img)
                        <a href="{{ img_url($img) }}" target="_blank" rel="noopener"><img src="{{ img_url($img) }}" alt="Photo from {{ $rv->name }}'s review of {{ $product->name }}" width="84" height="84" loading="lazy"></a>
                    @endforeach
                </div>
            @endif
        </article>
    @endforeach
</div>

@push('scripts')
    <script>
        (function () {
            const input = document.getElementById('rv-images');
            if (!input) return;
            const box = document.getElementById('rv-previews');
            input.addEventListener('change', () => {
                const files = [...input.files];
                box.innerHTML = '';
                if (files.length > 3) {
                    input.value = '';
                    box.innerHTML = '<div class="text-danger small">Please choose up to 3 photos.</div>';
                    return;
                }
                files.forEach((f) => { const img = document.createElement('img'); img.src = URL.createObjectURL(f); img.alt = ''; box.appendChild(img); });
            });
            // Coming back with an error: open the form and scroll to it.
            if (location.hash === '#reviews' && document.querySelector('#write-review.show')) document.getElementById('write-review').scrollIntoView({ block: 'start' });
        })();
    </script>
@endpush
