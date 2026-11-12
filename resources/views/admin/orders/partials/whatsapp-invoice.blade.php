{{-- "Send invoice on WhatsApp" (admin order page): a mini receipt like a shop slip, made into PNG or PDF in the browser
     (html2canvas / jsPDF, loaded only when the window opens) and sent with a message.
     Sending: the share menu of the phone / WhatsApp app with the file and message; where that is not available, the file is
     downloaded (and a PNG copied), and the customer's WhatsApp chat opens with the message — paste (Ctrl+V) or attach the file.
     Expects $order (items, district, thana). --}}
@php
    $siteName = setting('site_name', config('app.name'));
    $plain = fn ($v) => money($v, false);
    $waNumber = \App\Support\WhatsappInvoice::number($order->mobile);
    $fileBase = 'invoice-'.$order->order_number;
    $discount = (float) ($order->coupon_discount ?? 0) + (float) $order->discount;
@endphp

<div class="modal fade" id="waInvoiceModal" tabindex="-1" aria-labelledby="waInvoiceTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="waInvoiceTitle"><i class="bi bi-whatsapp text-success me-1"></i> Send invoice on WhatsApp</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4">
                    {{-- The mini invoice (this exact block becomes the PNG / PDF) --}}
                    <div class="col-md-5 d-flex justify-content-center">
                        <div class="mini-invoice-wrap">
                            <div class="mini-invoice" id="miniInvoice">
                                <div class="mi-head">
                                    @if(setting('logo'))
                                        <img src="{{ img_url(setting('logo')) }}" alt="{{ $siteName }}" class="mi-logo" crossorigin="anonymous">
                                    @else
                                        <div class="mi-brand">{{ $siteName }}</div>
                                    @endif
                                    <div class="mi-name">{{ $siteName }}</div>
                                    @if(setting('phone'))<div>Contact: {{ setting('phone') }}</div>@endif
                                    <div class="mi-order">Order {{ $order->order_number }} · {{ $order->created_at->format('d M Y') }}</div>
                                </div>
                                <div class="mi-dash"></div>
                                <div class="mi-customer">
                                    <div>Name: {{ $order->customer_name }}</div>
                                    <div>Number: {{ $order->mobile }}</div>
                                    <div>Address: {{ $order->full_address }}</div>
                                </div>
                                <div class="mi-dash"></div>
                                <table class="mi-table">
                                    <thead><tr><th>Item</th><th>Qty</th><th>Price</th></tr></thead>
                                    <tbody>
                                        @foreach($order->items as $item)
                                            <tr><td>{{ $item->product_name }}</td><td class="c">{{ $item->quantity }}</td><td class="r">{{ $plain($item->line_total) }}</td></tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <div class="mi-totals">
                                    <div><span>Subtotal</span><span>{{ $plain($order->subtotal) }}</span></div>
                                    <div><span>Delivery Charge</span><span>{{ $plain($order->delivery_charge) }}</span></div>
                                    @if($discount > 0)<div><span>Discount</span><span>-{{ $plain($discount) }}</span></div>@endif
                                    <div class="mi-grand"><span>Grand Total</span><span>Tk.{{ $plain($order->total) }}</span></div>
                                </div>
                                <div class="mi-foot">Cash on delivery · Thank you for shopping with us</div>
                            </div>
                        </div>
                    </div>

                    {{-- Message + format + send --}}
                    <div class="col-md-7">
                        <div class="mb-3">
                            <div class="small text-muted mb-1">To</div>
                            <div class="fw-semibold">{{ $order->customer_name }} · <span class="font-monospace">+{{ $waNumber }}</span></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="waInvoiceMessage">Message</label>
                            <textarea id="waInvoiceMessage" class="form-control" rows="4" maxlength="1000" data-no-validate>{{ \App\Support\WhatsappInvoice::message($order) }}</textarea>
                            <div class="form-text">Default text: Settings → Contact &amp; social → <em>WhatsApp invoice message</em>.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Invoice as</label>
                            <div class="btn-group w-100" role="group" aria-label="Invoice format">
                                <input type="radio" class="btn-check" name="waInvoiceFormat" id="waFmtPng" value="png" checked>
                                <label class="btn btn-outline-secondary" for="waFmtPng"><i class="bi bi-file-earmark-image me-1"></i>PNG image</label>
                                <input type="radio" class="btn-check" name="waInvoiceFormat" id="waFmtPdf" value="pdf">
                                <label class="btn btn-outline-secondary" for="waFmtPdf"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</label>
                            </div>
                        </div>
                        <div class="small mb-3" id="waInvoiceStatus" role="status" aria-live="polite"></div>
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-success" id="waInvoiceSend" disabled><i class="bi bi-whatsapp me-1"></i> Send on WhatsApp</button>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-soft-success flex-fill" id="waInvoiceChat" disabled title="Downloads the file, copies a PNG and opens the customer's chat"><i class="bi bi-chat-dots me-1"></i> Open chat + get file</button>
                                <button type="button" class="btn btn-soft-secondary flex-fill" id="waInvoiceDownload" disabled><i class="bi bi-download me-1"></i> Download</button>
                            </div>
                        </div>
                        <div class="small text-muted mt-3" id="waInvoiceHelp">
                            <strong>Send on WhatsApp</strong> opens the share menu with the invoice and message — choose WhatsApp and the customer.
                            Where the share menu is not available, the customer's chat opens with the message: then press <kbd>Ctrl</kbd>+<kbd>V</kbd> to paste the PNG, or attach the downloaded file.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
    <style>
        /* Mini invoice: a shop slip, ~300 px wide; made 3× sharper as PNG / PDF */
        .mini-invoice-wrap { background: #f1f5f9; border-radius: 12px; padding: 14px; }
        .mini-invoice { width: 300px; background: #fff; color: #111; font-family: Arial, Helvetica, sans-serif; font-size: 13px; line-height: 1.4; padding: 18px 16px 14px; }
        .mini-invoice .mi-head { text-align: center; }
        .mini-invoice .mi-logo { max-width: 170px; max-height: 64px; object-fit: contain; display: block; margin: 0 auto 6px; }
        .mini-invoice .mi-brand { font-size: 30px; font-weight: 800; color: #f26b21; letter-spacing: -.5px; margin-bottom: 4px; }
        .mini-invoice .mi-name { font-weight: 600; }
        .mini-invoice .mi-order { font-size: 11px; color: #555; margin-top: 2px; }
        .mini-invoice .mi-dash { border-top: 1.5px dashed #333; margin: 10px 0; }
        .mini-invoice .mi-customer div { overflow-wrap: anywhere; }
        .mini-invoice .mi-table { width: 100%; border-collapse: collapse; margin: 4px 0 6px; }
        .mini-invoice .mi-table th, .mini-invoice .mi-table td { border: 1px solid #333; padding: 4px 6px; vertical-align: top; }
        .mini-invoice .mi-table th { text-align: center; font-weight: 700; }
        .mini-invoice .mi-table td { border-top: 0; border-bottom: 0; }
        .mini-invoice .mi-table tbody tr:last-child td { border-bottom: 1px solid #333; }
        .mini-invoice .mi-table .c { text-align: center; white-space: nowrap; }
        .mini-invoice .mi-table .r { text-align: right; white-space: nowrap; }
        .mini-invoice .mi-totals { border-top: 1px solid #333; padding-top: 4px; }
        .mini-invoice .mi-totals div { display: flex; justify-content: space-between; padding: 2px 4px; }
        .mini-invoice .mi-grand { border-top: 1px solid #333; margin-top: 4px; padding-top: 6px !important; font-weight: 700; font-size: 14px; }
        .mini-invoice .mi-foot { text-align: center; font-size: 10.5px; color: #666; margin-top: 10px; }
    </style>
@endpush

@push('page-scripts')
    <script>
        (function () {
            const modal = document.getElementById('waInvoiceModal');
            if (!modal) return;
            const WA = @json($waNumber), BASE = @json($fileBase), TITLE = @json('Invoice '.$order->order_number);
            const $status = document.getElementById('waInvoiceStatus');
            const btns = ['waInvoiceSend', 'waInvoiceChat', 'waInvoiceDownload'].map((id) => document.getElementById(id));
            const message = () => document.getElementById('waInvoiceMessage').value.trim();
            const format = () => document.querySelector('[name="waInvoiceFormat"]:checked').value;
            const files = {}; // format → File, made once per format
            const status = (html, cls = 'text-muted') => { $status.className = 'small mb-3 ' + cls; $status.innerHTML = html; };

            const loadScript = (src) => new Promise((ok, bad) => {
                if (document.querySelector(`script[src="${src}"]`)) return ok();
                const s = document.createElement('script'); s.src = src; s.onload = ok; s.onerror = () => bad(new Error('Could not load ' + src)); document.head.appendChild(s);
            });
            const libs = () => Promise.all([
                window.html2canvas ? null : loadScript('https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js'),
                window.jspdf ? null : loadScript('https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js'),
            ]);

            async function make(fmt) {
                if (files[fmt]) return files[fmt];
                await libs();
                const canvas = await html2canvas(document.getElementById('miniInvoice'), { scale: 3, backgroundColor: '#ffffff', useCORS: true, logging: false });
                let blob;
                if (fmt === 'png') {
                    blob = await new Promise((r) => canvas.toBlob(r, 'image/png'));
                } else {
                    const w = 80, h = Math.round((canvas.height / canvas.width) * w * 10) / 10; // 80 mm wide slip
                    const pdf = new window.jspdf.jsPDF({ unit: 'mm', format: [w, h], orientation: 'portrait' });
                    pdf.addImage(canvas.toDataURL('image/png'), 'PNG', 0, 0, w, h);
                    blob = pdf.output('blob');
                }
                return (files[fmt] = new File([blob], `${BASE}.${fmt}`, { type: fmt === 'png' ? 'image/png' : 'application/pdf' }));
            }

            async function prepare() {
                btns.forEach((b) => (b.disabled = true));
                status('<span class="spinner-border spinner-border-sm me-1"></span>Preparing the invoice…');
                try {
                    const f = await make(format());
                    btns.forEach((b) => (b.disabled = false));
                    status(`<i class="bi bi-check-circle text-success me-1"></i>${f.name} ready (${Math.max(1, Math.round(f.size / 1024))} KB)`);
                } catch (e) {
                    status('<i class="bi bi-x-circle me-1"></i>Could not make the invoice: ' + e.message, 'text-danger');
                }
            }

            function download(file) {
                const a = document.createElement('a');
                a.href = URL.createObjectURL(file); a.download = file.name;
                document.body.appendChild(a); a.click(); a.remove();
                setTimeout(() => URL.revokeObjectURL(a.href), 4000);
            }

            async function openChat(file) {
                // Open the chat first (still inside the click, so the browser allows the new tab).
                window.open(`https://wa.me/${WA}?text=${encodeURIComponent(message())}`, '_blank', 'noopener');
                download(file);
                let copied = false;
                if (file.type === 'image/png' && navigator.clipboard && window.ClipboardItem) {
                    try { await navigator.clipboard.write([new ClipboardItem({ 'image/png': file })]); copied = true; } catch (e) { /* not allowed here */ }
                }
                status(copied
                    ? '<i class="bi bi-clipboard-check text-success me-1"></i>Chat opened with the message. The invoice is copied — press <kbd>Ctrl</kbd>+<kbd>V</kbd> in the chat, then send. (Also downloaded.)'
                    : `<i class="bi bi-download text-success me-1"></i>Chat opened with the message. Attach the downloaded <strong>${file.name}</strong> with the 📎 button, then send.`, 'text-body');
            }

            document.getElementById('waInvoiceSend').addEventListener('click', async () => {
                const file = files[format()];
                if (!file) return;
                const data = { files: [file], text: message(), title: TITLE };
                if (navigator.canShare && navigator.canShare(data)) {
                    try { await navigator.share(data); status('<i class="bi bi-check-circle text-success me-1"></i>Shared. Pick the customer in WhatsApp if you have not yet.', 'text-body'); return; }
                    catch (e) { if (e.name === 'AbortError') return; }
                }
                openChat(file); // no share menu here: chat + file instead
            });
            document.getElementById('waInvoiceChat').addEventListener('click', () => { const f = files[format()]; if (f) openChat(f); });
            document.getElementById('waInvoiceDownload').addEventListener('click', () => { const f = files[format()]; if (f) { download(f); status(`<i class="bi bi-download text-success me-1"></i>${f.name} downloaded.`, 'text-body'); } });
            document.querySelectorAll('[name="waInvoiceFormat"]').forEach((r) => r.addEventListener('change', prepare));
            modal.addEventListener('shown.bs.modal', prepare);
        })();
    </script>
@endpush
