@extends('admin.layouts.app')

@section('title', 'Order #'.$order->order_number)

@php
    $vendors = $order->items->pluck('vendor')->filter()->unique('id');
    $wa = preg_replace('/^0/', '880', $order->mobile);
@endphp

@section('content')
    <div class="page-header">
        <div>
            <h1 class="d-flex align-items-center gap-2 flex-wrap">
                Order #{{ $order->order_number }} {!! order_status_badge($order->status) !!}
                @if($order->otp_verified)
                    <span class="badge badge-soft-success fs-6"><i class="bi bi-patch-check-fill me-1"></i>OTP verified</span>
                @else
                    <span class="badge badge-soft-danger fs-6"><i class="bi bi-x-octagon me-1"></i>OTP not verified</span>
                @endif
            </h1>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.orders.index') }}">Orders</a></li><li class="breadcrumb-item active">Placed {{ $order->created_at->format('d M Y, h:i A') }}</li></ol></nav>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.orders.invoice', $order->unique_id) }}" target="_blank" class="btn btn-soft-secondary"><i class="bi bi-printer me-1"></i> Invoice</a>
            @include('admin.partials.delete', ['action' => route('admin.orders.destroy', $order->unique_id), 'message' => 'This order will be deleted and removed from all lists.'])
        </div>
    </div>

    @unless($order->otp_verified)
        <div class="alert border-0 d-flex flex-wrap align-items-center gap-3 mb-4" style="background:#fef2f2;border-radius:12px">
            <i class="bi bi-shield-exclamation fs-4 text-danger"></i>
            <div class="flex-grow-1"><strong>Mobile not verified.</strong> The customer did not complete OTP verification. Call to confirm before forwarding this order.</div>
            <a href="tel:{{ $order->mobile }}" class="btn btn-sm btn-soft-danger"><i class="bi bi-telephone me-1"></i>Call {{ $order->mobile }}</a>
            <form method="post" action="{{ route('admin.orders.verify', $order->unique_id) }}" data-confirm="Mark this order's mobile as verified (confirmed by phone)?">
                @csrf<button class="btn btn-sm btn-success"><i class="bi bi-check2-circle me-1"></i>Mark verified</button>
            </form>
        </div>
    @endunless

    <div class="row g-4">
        <div class="col-xl-8">
            {{-- Items --}}
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between"><h5>Items</h5><span class="small text-muted">{{ $order->items->sum('quantity') }} unit(s)</span></div>
                <div class="table-responsive">
                    <table class="table">
                        <thead><tr><th>Product</th><th>Vendor</th><th class="text-center">Qty</th><th class="text-end">Price</th><th class="text-end">Cost</th><th class="text-end">Line total</th><th class="text-end">Profit</th></tr></thead>
                        <tbody>
                            @foreach($order->items as $item)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <img src="{{ img_url($item->thumbnail) }}" class="thumb" alt="">
                                            <div>
                                                @if($item->product && ! $item->product->trashed())
                                                    <a href="{{ route('admin.products.show', $item->product->unique_id) }}" class="title">{{ $item->product_name }}</a>
                                                @else
                                                    <span class="title">{{ $item->product_name }}</span>
                                                @endif
                                                <div class="small text-muted">{{ $item->product_code }}
                                                    @if($item->product?->vendor_product_url) · <a href="{{ $item->product->vendor_product_url }}" target="_blank" rel="noopener">vendor link <i class="bi bi-box-arrow-up-right"></i></a>@endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="small">{{ optional($item->vendor)->name ?? '—' }}</td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-end">{{ money($item->unit_price) }}</td>
                                    <td class="text-end text-muted">{{ money($item->drop_shipping_price) }}</td>
                                    <td class="text-end fw-semibold">{{ money($item->line_total) }}</td>
                                    <td class="text-end fw-semibold text-success">{{ money($item->line_total - $item->line_cost) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-body border-top">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between py-1"><span class="text-muted">Subtotal</span><span>{{ money($order->subtotal) }}</span></div>
                            <div class="d-flex justify-content-between py-1"><span class="text-muted">Delivery charge</span><span>{{ money($order->delivery_charge) }}</span></div>
                            @if($order->coupon_discount > 0)<div class="d-flex justify-content-between py-1"><span class="text-muted">Coupon ({{ $order->coupon_code }})</span><span class="text-success">-{{ money($order->coupon_discount) }}</span></div>@endif
                            @if($order->discount > 0)<div class="d-flex justify-content-between py-1"><span class="text-muted">Extra discount</span><span>-{{ money($order->discount) }}</span></div>@endif
                            <div class="d-flex justify-content-between py-2 border-top mt-1 fw-bold fs-5"><span>Customer pays (COD)</span><span>{{ money($order->total) }}</span></div>
                        </div>
                        <div class="col-md-6">
                            <div class="profit-box">
                                <div class="row-line"><span class="text-muted">Collected from customer</span><span>{{ money($order->total) }}</span></div>
                                <div class="row-line"><span class="text-muted">Vendor cost (drop shipping)</span><span>-{{ money($order->cost_total) }}</span></div>
                                <div class="row-line"><span class="text-muted">Courier cost</span><span>-{{ money($order->courier_cost) }}</span></div>
                                <hr class="my-2">
                                <div class="d-flex justify-content-between align-items-end">
                                    <span class="small text-muted">Net profit</span>
                                    <span class="big {{ $order->profit >= 0 ? 'text-success' : 'text-danger' }}">{{ money($order->profit) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Forward --}}
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="bi bi-send me-1"></i> Forward to vendor / courier</h5>
                    @if($order->forwarded_at)<span class="small text-muted">Forwarded {{ $order->forwarded_at->diffForHumans() }}</span>@endif
                </div>
                <div class="card-body">
                    @if($vendors->isNotEmpty())
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            @foreach($vendors as $v)
                                <div class="border rounded-3 px-3 py-2 small">
                                    <strong>{{ $v->name }}</strong>
                                    @if($v->contact_person)· {{ $v->contact_person }}@endif
                                    @if($v->phone)· <a href="tel:{{ $v->phone }}">{{ $v->phone }}</a>@endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <form method="post" action="{{ route('admin.orders.forward', $order->unique_id) }}" data-validate novalidate>
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Vendor order ref</label>
                                <input type="text" name="vendor_order_ref" class="form-control" value="{{ $order->vendor_order_ref }}" placeholder="Order ID at vendor" maxlength="100">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Courier</label>
                                <input type="text" name="courier_name" class="form-control" value="{{ $order->courier_name }}" list="couriers" placeholder="Steadfast, Pathao, RedX…" maxlength="60">
                                <datalist id="couriers"><option value="Steadfast"><option value="Pathao"><option value="RedX"><option value="Paperfly"><option value="Sundarban"><option value="eCourier"></datalist>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tracking / consignment ID</label>
                                <input type="text" name="tracking_code" class="form-control" value="{{ $order->tracking_code }}" placeholder="e.g. SF123456789" maxlength="100">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Courier cost (paid by you)</label>
                                <div class="input-group"><span class="input-group-text">৳</span><input type="number" step="0.01" min="0" max="99999" name="courier_cost" class="form-control" value="{{ $order->courier_cost }}" placeholder="e.g. 60"></div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Note</label>
                                <input type="text" name="note" class="form-control" placeholder="Optional — appears in the order timeline" maxlength="1000">
                            </div>
                            <div class="col-12 d-flex gap-2 flex-wrap">
                                <button class="btn btn-primary" name="status" value="forwarded"><i class="bi bi-shop me-1"></i> Forward to vendor</button>
                                <button class="btn btn-soft-primary" name="status" value="shipped"><i class="bi bi-truck me-1"></i> Handed to courier</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Customer details --}}
            <div class="card">
                <div class="card-header"><h5>Customer & shipping</h5></div>
                <form method="post" action="{{ route('admin.orders.details', $order->unique_id) }}" class="card-body" data-validate novalidate>
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Name</label><input type="text" name="customer_name" class="form-control" value="{{ $order->customer_name }}" placeholder="Customer full name" required minlength="3" maxlength="120"></div>
                        <div class="col-md-6"><label class="form-label">Mobile</label><input type="tel" name="mobile" class="form-control" value="{{ $order->mobile }}" placeholder="01XXXXXXXXX" required minlength="11" maxlength="11" pattern="01[3-9][0-9]{8}" data-digits data-msg-pattern="Enter a valid 11 digit mobile number (01XXXXXXXXX)."></div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control" value="{{ $order->address }}" placeholder="House no, road, area, landmark" required minlength="10" maxlength="500">
                            <div class="form-text"><i class="bi bi-geo-alt"></i> {{ optional($order->thana)->name }}, {{ optional($order->district)->name }}</div>
                        </div>
                        <div class="col-md-6"><label class="form-label">Delivery charge</label><div class="input-group"><span class="input-group-text">৳</span><input type="number" step="0.01" min="0" max="9999" name="delivery_charge" class="form-control" value="{{ $order->delivery_charge }}" placeholder="0" required></div></div>
                        <div class="col-md-6"><label class="form-label">Discount</label><div class="input-group"><span class="input-group-text">৳</span><input type="number" step="0.01" min="0" max="9999999" name="discount" class="form-control" value="{{ $order->discount }}" placeholder="0" required></div></div>
                        <div class="col-12"><label class="form-label">Admin note</label><textarea name="admin_note" rows="2" class="form-control" placeholder="Internal note (not visible to the customer)" maxlength="1000" data-counter>{{ $order->admin_note }}</textarea></div>
                        @if($order->note)<div class="col-12"><div class="p-3 rounded-3 bg-light small"><strong>Customer note:</strong> {{ $order->note }}</div></div>@endif
                        <div class="col-12 text-end"><button class="btn btn-soft-primary"><i class="bi bi-check2 me-1"></i> Save details</button></div>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header"><h5>Update status</h5></div>
                <form method="post" action="{{ route('admin.orders.status', $order->unique_id) }}" class="card-body" data-validate novalidate>
                    @csrf
                    <div class="mb-3">
                        <select name="status" class="form-select">
                            @foreach(config('shop.order_statuses') as $key => $s)
                                <option value="{{ $key }}" @selected($order->status === $key)>{{ $s['label'] }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Customer sees: Pending → Received → Processing.</div>
                    </div>
                    <div class="mb-3"><textarea name="note" rows="2" class="form-control" placeholder="Note for the timeline (optional)" maxlength="1000"></textarea></div>
                    <button class="btn btn-primary w-100">Update status</button>
                </form>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5>Customer</h5></div>
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="avatar d-inline-flex align-items-center justify-content-center rounded-3 bg-soft-primary fw-bold" style="width:44px;height:44px">{{ strtoupper(substr($order->customer_name, 0, 1)) }}</span>
                        <div><div class="fw-bold">{{ $order->customer_name }}</div><div class="small text-muted">{{ $order->mobile }}</div></div>
                    </div>
                    <p class="small mb-3"><i class="bi bi-geo-alt me-1 text-muted"></i>{{ $order->full_address }}</p>
                    <div class="d-flex gap-2">
                        <a href="tel:{{ $order->mobile }}" class="btn btn-soft-primary btn-sm flex-fill"><i class="bi bi-telephone me-1"></i>Call</a>
                        <a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener" class="btn btn-soft-success btn-sm flex-fill"><i class="bi bi-whatsapp me-1"></i>WhatsApp</a>
                    </div>
                    <hr>
                    <dl class="kv small mb-0" style="grid-template-columns: 100px 1fr">
                        <dt>Payment</dt><dd>Cash on delivery</dd>
                        <dt>OTP</dt><dd>{{ $order->otp_verified ? 'Verified '.optional($order->otp_verified_at)->format('d M, h:i A') : 'Not verified' }}</dd>
                        @if($order->courier_name)<dt>Courier</dt><dd>{{ $order->courier_name }}</dd>@endif
                        @if($order->tracking_code)<dt>Tracking</dt><dd>{{ $order->tracking_code }}</dd>@endif
                        @if($order->vendor_order_ref)<dt>Vendor ref</dt><dd>{{ $order->vendor_order_ref }}</dd>@endif
                        <dt>IP</dt><dd class="text-muted">{{ $order->ip }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5>Timeline</h5></div>
                <div class="card-body">
                    <ul class="timeline">
                        @foreach($order->histories as $h)
                            <li>
                                <span class="dot"></span>
                                <div class="d-flex justify-content-between gap-2">{!! order_status_badge($h->status) !!}<span class="small text-muted">{{ $h->created_at->format('d M, h:i A') }}</span></div>
                                @if($h->note)<div class="small mt-1">{{ $h->note }}</div>@endif
                                <div class="small text-muted">{{ $h->user ? 'by '.$h->user->name : 'System' }}</div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
