@extends('admin.layouts.app')

@php($editing = $coupon->exists)
@section('title', $editing ? 'Edit coupon' : 'Add coupon')

@section('content')
    <div class="page-header">
        <div>
            <h1>{{ $editing ? 'Edit coupon' : 'Add coupon' }}</h1>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.coupons.index') }}">Coupons</a></li><li class="breadcrumb-item active">{{ $editing ? $coupon->code : 'New' }}</li></ol></nav>
        </div>
        <a href="{{ route('admin.coupons.index') }}" class="btn btn-soft-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <form method="post" action="{{ $editing ? route('admin.coupons.update', $coupon->unique_id) : route('admin.coupons.store') }}" data-validate novalidate id="couponForm">
        @csrf
        @if($editing) @method('PUT') @endif
        <div class="row g-4">
            <div class="col-xl-8">
                <div class="card mb-4">
                    <div class="card-body row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="code">Coupon code <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" name="code" id="code" class="form-control font-monospace text-uppercase @error('code') is-invalid @enderror" value="{{ old('code', $coupon->code) }}"
                                       placeholder="e.g. EID100" required minlength="3" maxlength="30" pattern="[A-Za-z0-9_-]+" data-msg-pattern="Use letters, numbers, dashes or underscores only (no spaces).">
                                <button type="button" class="btn btn-soft-secondary" data-generate-code title="Generate a random code"><i class="bi bi-shuffle"></i></button>
                            </div>
                            @error('code')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="type">Who can use it <span class="text-danger">*</span></label>
                            <select name="type" id="type" class="form-select" required>
                                @foreach(\App\Models\Coupon::TYPES as $value => $label)<option value="{{ $value }}" @selected(old('type', $coupon->type) === $value)>{{ $label }}</option>@endforeach
                            </select>
                            <div class="form-text" data-type-help></div>
                        </div>

                        <div class="col-12" data-mobiles-wrap>
                            <label class="form-label" for="mobiles">Allowed mobile numbers <span class="text-danger">*</span></label>
                            <textarea name="mobiles" id="mobiles" rows="4" class="form-control font-monospace @error('mobiles') is-invalid @enderror" maxlength="20000"
                                      placeholder="01711000000&#10;01811000000&#10;(one per line, or separated by commas)">{{ old('mobiles', implode("\n", $coupon->mobiles ?? [])) }}</textarea>
                            @error('mobiles')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text"><span data-mobile-count>0</span> valid number(s). Only these customers can use the coupon.</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="discount_type">Discount type <span class="text-danger">*</span></label>
                            <select name="discount_type" id="discount_type" class="form-select" required>
                                @foreach(\App\Models\Coupon::DISCOUNT_TYPES as $value => $label)<option value="{{ $value }}" @selected(old('discount_type', $coupon->discount_type) === $value)>{{ $label }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="value">Discount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text" data-value-unit>৳</span>
                                <input type="number" name="value" id="value" class="form-control @error('value') is-invalid @enderror" value="{{ old('value', $coupon->value) }}" placeholder="e.g. 100" required min="1" max="9999999" step="0.01">
                            </div>
                            @error('value')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4" data-max-wrap>
                            <label class="form-label" for="max_discount">Maximum discount</label>
                            <div class="input-group"><span class="input-group-text">৳</span><input type="number" name="max_discount" id="max_discount" class="form-control" value="{{ old('max_discount', $coupon->max_discount) }}" placeholder="No limit" min="1" max="9999999" step="0.01"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="min_order">Minimum order</label>
                            <div class="input-group"><span class="input-group-text">৳</span><input type="number" name="min_order" id="min_order" class="form-control" value="{{ old('min_order', $coupon->min_order) }}" placeholder="No minimum" min="0" max="9999999" step="0.01"></div>
                            <div class="form-text">Product subtotal, before delivery.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="usage_limit">Total uses</label>
                            <input type="number" name="usage_limit" id="usage_limit" class="form-control" value="{{ old('usage_limit', $coupon->usage_limit) }}" placeholder="Unlimited" min="1" max="1000000" step="1">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="per_mobile_limit">Uses per mobile <span class="text-danger">*</span></label>
                            <input type="number" name="per_mobile_limit" id="per_mobile_limit" class="form-control" value="{{ old('per_mobile_limit', $coupon->per_mobile_limit) }}" placeholder="1" required min="1" max="1000" step="1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="starts_at">Valid from</label>
                            <div class="input-group input-daterange">
                                <input type="text" name="starts_at" id="starts_at" class="form-control" value="{{ old('starts_at', $coupon->starts_at?->format('Y-m-d')) }}" placeholder="Today" autocomplete="off" maxlength="10" pattern="\d{4}-\d{2}-\d{2}" data-msg-pattern="Use YYYY-MM-DD.">
                                <span class="input-group-text">to</span>
                                <input type="text" name="ends_at" id="ends_at" class="form-control @error('ends_at') is-invalid @enderror" value="{{ old('ends_at', $coupon->ends_at?->format('Y-m-d')) }}" placeholder="No end date" autocomplete="off" maxlength="10" pattern="\d{4}-\d{2}-\d{2}" data-msg-pattern="Use YYYY-MM-DD.">
                            </div>
                            @error('ends_at')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="note">Internal note</label>
                            <input type="text" name="note" id="note" class="form-control" value="{{ old('note', $coupon->note) }}" placeholder="e.g. Eid campaign on Facebook" maxlength="255">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card position-sticky" style="top: 88px">
                    <div class="card-body">
                        <div class="coupon-preview mb-3">
                            <div class="small text-muted">Customer sees</div>
                            <div class="fs-4 fw-bold font-monospace" data-preview-code>{{ $coupon->code ?: 'CODE' }}</div>
                            <div class="fw-semibold text-success" data-preview-label>{{ $coupon->exists ? $coupon->label : '' }}</div>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $coupon->is_active))>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                        <button class="btn btn-primary w-100"><i class="bi bi-check2 me-1"></i> {{ $editing ? 'Save changes' : 'Create coupon' }}</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('page-scripts')
    <script>
        (function ($) {
            const help = {
                all_users: 'Anyone can use it (within the limits below).',
                new_user: 'Only mobile numbers that have never placed an order.',
                fixed_user: 'Only the mobile numbers listed below.',
            };
            const MOBILE = /^01[3-9]\d{8}$/;
            function sync() {
                const type = $('#type').val();
                const percent = $('#discount_type').val() === 'percent';
                $('[data-type-help]').text(help[type] || '');
                $('[data-mobiles-wrap]').toggle(type === 'fixed_user');
                $('#mobiles').prop('required', type === 'fixed_user');
                $('[data-max-wrap]').toggle(percent);
                $('[data-value-unit]').text(percent ? '%' : '৳');
                $('#value').attr('max', percent ? 100 : 9999999);
                const v = parseFloat($('#value').val()) || 0;
                const max = parseFloat($('#max_discount').val()) || 0;
                $('[data-preview-code]').text(($('#code').val() || 'CODE').toUpperCase());
                $('[data-preview-label]').text(v ? (percent ? `${v}% off${max ? ` (max ৳${max})` : ''}` : `৳${v} off`) : '');
                const nums = ($('#mobiles').val() || '').split(/[\s,;]+/).filter(Boolean).map((m) => m.replace(/^(\+?88)(?=01)/, ''));
                $('[data-mobile-count]').text(new Set(nums.filter((m) => MOBILE.test(m))).size);
            }
            $('#couponForm').on('input change', 'input, select, textarea', sync);
            $('[data-generate-code]').on('click', () => {
                const chars = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
                let code = '';
                for (let i = 0; i < 8; i++) code += chars[Math.floor(Math.random() * chars.length)];
                $('#code').val(code).trigger('input');
            });
            $('#code').on('input', function () { const p = this.selectionStart; this.value = this.value.toUpperCase().replace(/\s/g, ''); this.setSelectionRange(p, p); });
            sync();
        })(jQuery);
    </script>
@endpush
