{{-- "Place order with vendor": everything needed to fill the vendor's checkout form, laid out like it,
     each value with a copy button (numbers are copied as plain digits, e.g. 440). Expects $order (items, district, thana). --}}
@php
    $districtName = optional($order->district)->name;
    $thanaName = optional($order->thana)->name;
    // Vendor forms ask "Inside Dhaka / Outside Dhaka": decided by the order's district.
    $location = $districtName && strcasecmp(trim($districtName), 'Dhaka') === 0 ? 'Inside Dhaka' : 'Outside Dhaka';
    $num = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.'); // 440.00 → "440"
    $cod = $num($order->total);
    $noteForVendor = trim((string) $order->note);

    $lines = ['Order '.$order->order_number, ''];
    foreach ($order->items as $i => $item) {
        $lines[] = ($i + 1).'. '.$item->product_code.' — '.$item->product_name.' × '.$item->quantity;
    }
    $lines = array_merge($lines, [
        '',
        'Full name: '.$order->customer_name,
        'Phone: '.$order->mobile,
        'Delivery location: '.$location,
        'District: '.$districtName,
        'City / Thana: '.$thanaName,
        'Street address: '.$order->address,
        'Transaction: COD',
        'Cash on delivery: '.$cod,
    ]);
    if ($noteForVendor !== '') {
        $lines[] = 'Note: '.$noteForVendor;
    }
    $copyAll = implode("\n", $lines);
@endphp
<div class="card mb-4 place-order" id="place-order">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5><i class="bi bi-bag-check me-1 text-primary"></i> Place order with vendor</h5>
        <div class="d-flex gap-2 flex-wrap">
            @foreach($order->items->filter(fn ($i) => $i->product?->vendor_product_url)->unique(fn ($i) => $i->product->vendor_product_url) as $item)
                <a href="{{ $item->product->vendor_product_url }}" target="_blank" rel="noopener" class="btn btn-soft-secondary btn-sm" title="Open {{ $item->product_code }} on the vendor site"><i class="bi bi-box-arrow-up-right me-1"></i>{{ $item->product_code }} at vendor</a>
            @endforeach
            <button type="button" class="btn btn-primary btn-sm" data-copy="{{ $copyAll }}" data-copy-msg="Order details copied"><i class="bi bi-clipboard-check me-1"></i>Copy all</button>
        </div>
    </div>

    {{-- Products --}}
    <div class="table-responsive">
        <table class="table mb-0 po-table">
            <thead><tr><th>Product code</th><th>Product name</th><th class="text-center">Qty</th><th class="text-end">Order price</th><th class="text-end">Total price</th><th class="text-end">Vendor price</th><th class="text-end">Profit</th></tr></thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td class="text-nowrap">@include('admin.orders.partials.copy-value', ['value' => $item->product_code, 'class' => 'fw-semibold'])</td>
                        <td>@include('admin.orders.partials.copy-value', ['value' => $item->product_name])</td>
                        <td class="text-center">@include('admin.orders.partials.copy-value', ['value' => (string) $item->quantity, 'class' => 'fw-semibold'])</td>
                        <td class="text-end text-nowrap">@include('admin.orders.partials.copy-value', ['value' => $num($item->unit_price), 'show' => money($item->unit_price)])</td>
                        <td class="text-end text-nowrap fw-semibold">{{ money($item->line_total) }}</td>
                        <td class="text-end text-nowrap" title="{{ money($item->drop_shipping_price) }} × {{ $item->quantity }}">@include('admin.orders.partials.copy-value', ['value' => $num($item->line_cost), 'show' => money($item->line_cost)])</td>
                        <td class="text-end text-nowrap fw-semibold {{ $item->line_total - $item->line_cost >= 0 ? 'text-success' : 'text-danger' }}">{{ money($item->line_total - $item->line_cost) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="card-body border-top">
        {{-- Money summary --}}
        <div class="po-summary mb-4">
            <div><span>Products</span><strong>{{ money($order->subtotal) }}</strong></div>
            <div><span>Delivery charge <small class="text-muted">({{ $districtName ?? '—' }})</small></span><strong>{{ money($order->delivery_charge) }}</strong></div>
            @if(($order->coupon_discount ?? 0) + $order->discount > 0)<div><span>Discount</span><strong class="text-success">-{{ money(($order->coupon_discount ?? 0) + $order->discount) }}</strong></div>@endif
            <div><span>Vendor price</span><strong>{{ money($order->cost_total) }}</strong></div>
            @if($order->courier_cost > 0)<div><span>Courier cost</span><strong>{{ money($order->courier_cost) }}</strong></div>@endif
            <div class="po-profit"><span>Profit</span><strong class="{{ $order->profit >= 0 ? 'text-success' : 'text-danger' }}">{{ money($order->profit) }}</strong></div>
            <div class="po-cod"><span>Total price (COD)</span><strong>{{ money($order->total) }}</strong></div>
        </div>

        {{-- Customer — same order as the vendor's checkout form --}}
        <div class="row g-3 po-fields">
            <div class="col-md-8">@include('admin.orders.partials.copy-field', ['label' => 'Full name', 'value' => $order->customer_name])</div>
            <div class="col-md-4">@include('admin.orders.partials.copy-field', ['label' => 'Phone number', 'value' => $order->mobile])</div>
            <div class="col-md-6">@include('admin.orders.partials.copy-field', ['label' => 'Delivery location', 'value' => $location, 'hint' => 'from the district'])</div>
            <div class="col-md-6">@include('admin.orders.partials.copy-field', ['label' => 'District', 'value' => $districtName])</div>
            <div class="col-md-6">@include('admin.orders.partials.copy-field', ['label' => 'Street address', 'value' => $order->address])</div>
            <div class="col-md-6">@include('admin.orders.partials.copy-field', ['label' => 'City / Thana', 'value' => $thanaName])</div>
            <div class="col-md-4">@include('admin.orders.partials.copy-field', ['label' => 'Transaction', 'value' => 'COD'])</div>
            <div class="col-md-4">@include('admin.orders.partials.copy-field', ['label' => 'Cash on delivery (COD)', 'value' => $cod, 'show' => money($order->total), 'strong' => true])</div>
            <div class="col-md-4">@include('admin.orders.partials.copy-field', ['label' => 'Note / remarks', 'value' => $noteForVendor, 'hint' => $noteForVendor === '' ? 'customer left no note' : null])</div>
        </div>
        <div class="small text-muted mt-3"><i class="bi bi-info-circle me-1"></i>Click <i class="bi bi-copy"></i> to copy a value, or <strong>Copy all</strong> for everything at once. After placing it, save the vendor's order ID below under <a href="#forward">Forward to vendor / courier</a>.</div>
    </div>
</div>
