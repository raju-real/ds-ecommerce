@php($siteName = setting('site_name', config('app.name')))
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice #{{ $order->order_number }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Inter, sans-serif; background: #f5f7fb; font-size: 14px; color: #0f172a; }
        .sheet { max-width: 800px; margin: 2rem auto; background: #fff; border-radius: 14px; padding: 2.5rem; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
        .table th { font-size: 12px; text-transform: uppercase; color: #64748b; background: #f8fafc; }
        .brand { font-size: 1.5rem; font-weight: 800; }
        @media print { body { background: #fff; } .sheet { box-shadow: none; margin: 0; padding: 0; } .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                @if(setting('logo'))<img src="{{ img_url(setting('logo')) }}" alt="{{ $siteName }}" style="max-height: 48px">@else<div class="brand">{{ $siteName }}</div>@endif
                <div class="small text-muted mt-2">{{ setting('address') }}<br>{{ setting('phone') }} {{ setting('email') ? '· '.setting('email') : '' }}</div>
            </div>
            <div class="text-end">
                <div class="fs-4 fw-bold">INVOICE</div>
                <div class="text-muted">#{{ $order->order_number }}</div>
                <div class="small text-muted">{{ $order->created_at->format('d M Y') }}</div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-7">
                <div class="small text-muted text-uppercase fw-semibold mb-1">Ship to</div>
                <div class="fw-bold">{{ $order->customer_name }}</div>
                <div>{{ $order->mobile }}</div>
                <div>{{ $order->full_address }}</div>
            </div>
            <div class="col-5 text-end">
                <div class="small text-muted text-uppercase fw-semibold mb-1">Payment</div>
                <div class="fw-bold">Cash on Delivery</div>
                @if($order->courier_name)<div class="small">{{ $order->courier_name }} {{ $order->tracking_code ? '· '.$order->tracking_code : '' }}</div>@endif
            </div>
        </div>

        <table class="table">
            <thead><tr><th>#</th><th>Product</th><th class="text-center">Qty</th><th class="text-end">Price</th><th class="text-end">Total</th></tr></thead>
            <tbody>
                @foreach($order->items as $i => $item)
                    <tr><td>{{ $i + 1 }}</td><td>{{ $item->product_name }}<div class="small text-muted">{{ $item->product_code }}</div></td><td class="text-center">{{ $item->quantity }}</td><td class="text-end">{{ money($item->unit_price) }}</td><td class="text-end">{{ money($item->line_total) }}</td></tr>
                @endforeach
            </tbody>
        </table>

        <div class="row justify-content-end">
            <div class="col-6">
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Subtotal</span><span>{{ money($order->subtotal) }}</span></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Delivery</span><span>{{ money($order->delivery_charge) }}</span></div>
                @if($order->coupon_discount > 0)<div class="d-flex justify-content-between py-1"><span class="text-muted">Coupon ({{ $order->coupon_code }})</span><span class="text-success">-{{ money($order->coupon_discount) }}</span></div>@endif
                @if($order->discount > 0)<div class="d-flex justify-content-between py-1"><span class="text-muted">Extra discount</span><span>-{{ money($order->discount) }}</span></div>@endif
                <div class="d-flex justify-content-between py-2 border-top fw-bold fs-5"><span>Amount to collect</span><span>{{ money($order->total) }}</span></div>
            </div>
        </div>

        <p class="small text-muted text-center mt-5 mb-0">Thank you for shopping with {{ $siteName }}!</p>
        <div class="text-center mt-4 no-print"><button onclick="window.print()" class="btn btn-dark px-4">Print invoice</button></div>
    </div>
</body>
</html>
