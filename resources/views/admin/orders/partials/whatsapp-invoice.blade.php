{{-- "Send invoice on WhatsApp" (admin order page): a mini invoice in the shop design, made into PNG or PDF in the browser
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
    $website = preg_replace('#^www\.#', '', (string) parse_url(url('/'), PHP_URL_HOST)); // e.g. neatlybd.com
    $supportEmail = setting('email');
    $supportPhone = setting('phone');
    $districtName = optional($order->district)->name;
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
                            {{-- Same look as the shop: Inter, brand indigo, rounded soft boxes --}}
                            <div class="mini-invoice" id="miniInvoice">
                                <div class="mi-top">
                                    @if(setting('logo'))
                                        <img src="{{ img_url(setting('logo')) }}" alt="{{ $siteName }}" class="mi-logo" crossorigin="anonymous">
                                    @else
                                        <div class="mi-brand">{{ $siteName }}</div>
                                    @endif
                                    <div class="mi-doc">
                                        <span class="mi-badge">INVOICE</span>
                                        <div class="mi-no">#{{ $order->order_number }}</div>
                                        <div class="mi-date">{{ $order->created_at->format('d M Y') }}</div>
                                    </div>
                                </div>

                                <div class="mi-box">
                                    <div class="mi-cap">Invoice to</div>
                                    <div class="mi-cust">{{ $order->customer_name }}</div>
                                    <div class="mi-line">{{ $order->mobile }}</div>
                                    <div class="mi-line">{{ $order->full_address }}</div>
                                </div>

                                <div class="mi-items">
                                    <div class="mi-items-head"><span>Item</span><span>Amount</span></div>
                                    @foreach($order->items as $item)
                                        <div class="mi-item">
                                            <div class="mi-item-main">
                                                <div class="mi-item-name">{{ $item->product_name }}</div>
                                                <div class="mi-item-sub">{{ $item->quantity }} × Tk {{ $plain($item->unit_price) }}</div>
                                            </div>
                                            <div class="mi-item-amt">Tk {{ $plain($item->line_total) }}</div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="mi-sum">
                                    <div><span>Subtotal</span><span>Tk {{ $plain($order->subtotal) }}</span></div>
                                    <div><span>Delivery charge{{ $districtName ? ' ('.$districtName.')' : '' }}</span><span>Tk {{ $plain($order->delivery_charge) }}</span></div>
                                    @if($discount > 0)<div class="mi-disc"><span>Discount</span><span>− Tk {{ $plain($discount) }}</span></div>@endif
                                </div>
                                <div class="mi-total">
                                    <div>
                                        <div class="mi-total-cap">Total payable</div>
                                        <div class="mi-total-sub">Cash on delivery</div>
                                    </div>
                                    <div class="mi-total-amt">Tk {{ $plain($order->total) }}</div>
                                </div>

                                <div class="mi-thanks">Thank you for shopping with {{ $siteName }}!</div>
                                <div class="mi-support">
                                    @if($supportEmail)<div><span class="mi-k">Support</span>{{ $supportEmail }}</div>@endif
                                    <div><span class="mi-k">Website</span>{{ $website }}</div>
                                    @if($supportPhone)<div><span class="mi-k">Hotline</span>{{ $supportPhone }}</div>@endif
                                </div>
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
        /* Mini invoice in the shop's design (Inter, brand indigo #4f46e5, rounded soft boxes), ~320 px wide; saved 3× sharper */
        /* .mini-invoice-wrap { background: #eef2ff; border-radius: 14px; padding: 14px; } */
        .mini-invoice { width: 320px; background: #fff; color: #0f172a; font-family: "Inter", "Segoe UI", Arial, sans-serif; font-size: 12.5px; line-height: 1.45; border-radius: 16px; overflow: hidden; padding: 16px 16px 14px; }
        .mini-invoice .mi-top { display: flex; justify-content: space-between; align-items: center; gap: 10px; padding-bottom: 12px; border-bottom: 1px solid #eef0f4; }
        .mini-invoice .mi-logo { max-width: 140px; max-height: 46px; object-fit: contain; display: block; }
        .mini-invoice .mi-brand { font-size: 20px; font-weight: 800; color: #4f46e5; letter-spacing: -.02em; }
        .mini-invoice .mi-doc { text-align: right; flex: 0 0 auto; }
        .mini-invoice .mi-badge { display: inline-block; background: #eef2ff; color: #4f46e5; font-weight: 700; font-size: 9.5px; letter-spacing: .12em; padding: 3px 8px; border-radius: 999px; }
        .mini-invoice .mi-no { font-weight: 700; font-size: 13px; margin-top: 4px; }
        .mini-invoice .mi-date { color: #64748b; font-size: 11px; }
        .mini-invoice .mi-box { background: #f8fafc; border-radius: 12px; padding: 10px 12px; margin: 12px 0; }
        .mini-invoice .mi-cap { font-size: 9.5px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: #94a3b8; margin-bottom: 2px; }
        .mini-invoice .mi-cust { font-weight: 700; font-size: 13.5px; }
        .mini-invoice .mi-line { color: #475569; overflow-wrap: anywhere; }
        .mini-invoice .mi-items-head { display: flex; justify-content: space-between; font-size: 9.5px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: #94a3b8; padding: 0 2px 6px; border-bottom: 1px solid #eef0f4; }
        .mini-invoice .mi-item { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; padding: 8px 2px; border-bottom: 1px dashed #e2e8f0; }
        .mini-invoice .mi-item-main { min-width: 0; }
        .mini-invoice .mi-item-name { font-weight: 600; overflow-wrap: anywhere; }
        .mini-invoice .mi-item-sub { color: #64748b; font-size: 11.5px; }
        .mini-invoice .mi-item-amt { font-weight: 600; white-space: nowrap; }
        .mini-invoice .mi-sum { padding: 8px 2px 0; }
        .mini-invoice .mi-sum div { display: flex; justify-content: space-between; gap: 10px; color: #475569; padding: 2px 0; }
        .mini-invoice .mi-sum span:last-child { color: #0f172a; white-space: nowrap; }
        .mini-invoice .mi-sum .mi-disc span:last-child { color: #16a34a; }
        .mini-invoice .mi-total { display: flex; justify-content: space-between; align-items: center; gap: 10px; background: #4f46e5; color: #fff; border-radius: 12px; padding: 10px 12px; margin-top: 10px; }
        .mini-invoice .mi-total-cap { font-weight: 700; font-size: 13px; }
        .mini-invoice .mi-total-sub { font-size: 10.5px; opacity: .85; }
        .mini-invoice .mi-total-amt { font-weight: 800; font-size: 18px; white-space: nowrap; }
        .mini-invoice .mi-thanks { text-align: center; font-weight: 600; color: #4f46e5; margin: 12px 0 8px; }
        .mini-invoice .mi-support { border-top: 1px solid #eef0f4; padding-top: 8px; font-size: 11px; color: #475569; }
        .mini-invoice .mi-support div { display: flex; gap: 8px; padding: 1px 0; overflow-wrap: anywhere; }
        .mini-invoice .mi-k { flex: 0 0 52px; color: #94a3b8; font-weight: 600; }
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
                if (document.fonts && document.fonts.ready) await document.fonts.ready; // Inter, like the shop
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
