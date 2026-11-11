/* Storefront interactions. Requires jQuery + Bootstrap bundle; Select2 on checkout. */
(function ($) {
    'use strict';

    const S = window.SHOP || {};
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), Accept: 'application/json' } });

    const esc = (s) => $('<div>').text(s == null ? '' : s).html();
    const money = (n) => S.currency + Number(n).toLocaleString('en-US', { maximumFractionDigits: 2 });

    /* ---------------------------------------------------------- Toasts */
    window.toast = function (message, type = 'success') {
        if (!message) return;
        let $stack = $('.toast-stack');
        if (!$stack.length) $stack = $('<div class="toast-stack"></div>').appendTo('body');
        const icon = type === 'error' ? 'bi-x-circle-fill' : 'bi-check-circle-fill';
        const $t = $(`<div class="toast-item ${type}"><i class="bi ${icon}"></i><div>${esc(message)}</div></div>`).appendTo($stack);
        setTimeout(() => $t.fadeOut(300, () => $t.remove()), 3200);
    };

    /* ---------------------------------------------------------- Cart */
    function setCount(n) {
        $('[data-cart-count]').text(n).toggleClass('d-none', !n);
    }

    function renderMiniCart(data) {
        setCount(data.count);
        const $body = $('#miniCartBody');
        if (!data.items.length) {
            $body.html('<div class="empty-state"><i class="bi bi-bag-x"></i><p class="mb-3">Your cart is empty</p><a href="' + S.productsUrl + '" class="btn btn-primary btn-sm">Start shopping</a></div>');
            $('#miniCartFooter').addClass('d-none');
            return;
        }
        $body.html(data.items.map((i) => `
            <div class="cart-line" data-id="${i.id}">
                <img src="${esc(i.image)}" alt="${esc(i.name)}" width="64" height="64">
                <div class="flex-grow-1 min-w-0">
                    <a href="${esc(i.url)}" class="name d-block text-truncate">${esc(i.name)}</a>
                    <div class="d-flex align-items-center justify-content-between mt-1">
                        <div class="qty qty-sm">
                            <button type="button" data-cart-step="-1" aria-label="Decrease">−</button>
                            <input type="number" value="${i.qty}" min="1" placeholder="1" readonly aria-label="Quantity">
                            <button type="button" data-cart-step="1" aria-label="Increase">+</button>
                        </div>
                        <strong class="text-nowrap">${esc(i.total)}</strong>
                    </div>
                </div>
                <button class="btn btn-link remove p-1" data-cart-remove aria-label="Remove"><i class="bi bi-trash3"></i></button>
            </div>`).join(''));
        $('#miniCartSubtotal').text(data.subtotal_formatted);
        $('#miniCartFooter').removeClass('d-none');
    }

    function loadMiniCart() {
        $.getJSON(S.cartUrl, renderMiniCart);
    }

    $('#miniCart').on('show.bs.offcanvas', loadMiniCart);

    $(document).on('click', '#miniCart [data-cart-step]', function () {
        const $line = $(this).closest('.cart-line');
        const qty = Math.max(0, parseInt($line.find('input').val(), 10) + parseInt($(this).data('cart-step'), 10));
        $.post(S.cartUpdateUrl, { product_id: $line.data('id'), qty }, renderMiniCart);
    });

    $(document).on('click', '#miniCart [data-cart-remove]', function () {
        $.post(S.cartRemoveUrl, { product_id: $(this).closest('.cart-line').data('id') }, renderMiniCart);
    });

    /** Add-to-cart / Buy-now buttons: data-add-to-cart="ID" [data-buy-now] [data-qty-from="#selector"] */
    $(document).on('click', '[data-add-to-cart]', function (e) {
        e.preventDefault();
        const $btn = $(this);
        const buyNow = $btn.is('[data-buy-now]');
        const qtySel = $btn.data('qty-from');
        const qty = qtySel ? parseInt($(qtySel).val(), 10) || 1 : 1;
        if (this.dataset.busy) return; // already working on it
        Busy.start(this, buyNow ? 'Please wait…' : 'Adding…');
        $.post(S.cartAddUrl, { product_id: $btn.data('add-to-cart'), qty, buy_now: buyNow ? 1 : 0 })
            .done((res) => {
                if (res.redirect) { window.location = res.redirect; return; } // stays busy until checkout opens
                Busy.stop(this);
                setCount(res.count);
                toast(res.message);
                if (window.fbPixelFire) window.fbPixelFire(res.pixel); // AddToCart with product, price and event ID
            })
            .fail((xhr) => {
                Busy.stop(this);
                toast((xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong.', 'error');
            });
    });

    /* ---------------------------------------------------------- Quantity steppers */
    $(document).on('click', '.qty [data-step]', function () {
        const $input = $(this).closest('.qty').find('input');
        const max = parseInt($input.attr('max'), 10) || 20;
        $input.val(Math.min(max, Math.max(1, (parseInt($input.val(), 10) || 1) + parseInt($(this).data('step'), 10)))).trigger('change');
    });

    /* ---------------------------------------------------------- Quick view */
    $(document).on('click', '[data-quick-view]', function (e) {
        e.preventDefault();
        const $modal = $('#quickViewModal');
        $modal.find('.modal-body').html('<div class="row g-4 p-2"><div class="col-md-6"><div class="skeleton" style="aspect-ratio:1"></div></div><div class="col-md-6"><div class="skeleton mb-3" style="height:32px"></div><div class="skeleton mb-3" style="height:24px;width:40%"></div><div class="skeleton" style="height:120px"></div></div></div>');
        bootstrap.Modal.getOrCreateInstance($modal[0]).show();
        const url = $(this).data('quick-view');
        const productUrl = url.replace(/\/quick-view(\?.*)?$/, '');
        // One silent retry (a dropped connection, a server busy for a moment) before showing the message.
        const load = (attempt) => $.ajax({ url, timeout: 15000 })
            .done((html) => $modal.find('.modal-body').html(html))
            .fail((xhr) => {
                if (attempt < 2 && xhr.status !== 404) return load(attempt + 1);
                const gone = xhr.status === 404;
                $modal.find('.modal-body').html(`
                    <div class="empty-state py-4">
                        <i class="bi bi-exclamation-circle"></i>
                        <div class="fw-semibold mb-1">${gone ? 'This product is no longer available.' : 'Could not load the product preview.'}</div>
                        <div class="small text-muted mb-3">${gone ? 'Please browse our other products.' : 'Please check your connection and try again.'}</div>
                        <div class="d-flex flex-wrap justify-content-center gap-2">
                            ${gone ? '' : '<button type="button" class="btn btn-outline-primary" data-qv-retry><i class="bi bi-arrow-clockwise me-1"></i>Try again</button>'}
                            <a href="${gone ? S.productsUrl || '#' : productUrl}" class="btn btn-primary">${gone ? 'Browse products' : 'Open product page'}</a>
                        </div>
                    </div>`);
                $modal.find('[data-qv-retry]').one('click', () => { $modal.find('.modal-body').html('<div class="p-5 text-center"><span class="spinner-border text-primary"></span></div>'); load(1); });
            });
        load(1);
    });

    /* ---------------------------------------------------------- Gallery */
    $(document).on('click', '.gallery-thumbs button', function () {
        const $g = $(this).closest('[data-gallery]');
        $g.find('.gallery-thumbs button').removeClass('active');
        $(this).addClass('active');
        $g.find('.gallery-main img').attr('src', $(this).data('src'));
    });

    $(document).on('mousemove', '.gallery-main', function (e) {
        const r = this.getBoundingClientRect();
        $(this).find('img').css({ transformOrigin: `${((e.clientX - r.left) / r.width) * 100}% ${((e.clientY - r.top) / r.height) * 100}%`, transform: 'scale(1.8)' });
    }).on('mouseleave', '.gallery-main', function () {
        $(this).find('img').css({ transform: 'none' });
    });

    /* ---------------------------------------------------------- Listing filters */
    $(document).on('change', '[data-auto-submit]', function () { this.form.submit(); });

    // Form, file and counter validation lives in validate.js (shared with admin).

    /* ---------------------------------------------------------- Checkout */
    const $checkout = $('#checkoutForm');
    if ($checkout.length) {
        const $district = $('#district_id');
        const $thana = $('#thana_id');
        let subtotal = parseFloat($checkout.data('subtotal')) || 0;
        let delivery = 0;
        let deliveryReady = false; // district chosen and its delivery charge loaded
        const coupon = { code: null, discount: 0 };
        const $placeBtn = $('#placeOrderBtn');
        const $notice = $('#districtNotice');

        const s2 = (el, placeholder) => $(el).select2({ theme: 'bootstrap-5', width: '100%', placeholder, dropdownParent: $(el).parent() });
        s2($district, 'Select district');
        s2($thana, 'Select thana / upazila');

        function updateTotals() {
            const total = Math.max(0, subtotal + delivery - coupon.discount);
            $('#sumSubtotal').text(money(subtotal));
            $('#sumDelivery').text(deliveryReady ? money(delivery) : 'Select district');
            $('#couponRow').toggleClass('d-none', !coupon.code);
            $('#couponRow [data-coupon-code]').text(coupon.code || '');
            $('#sumCoupon').text(coupon.code ? '−' + money(coupon.discount) : '');
            $('#sumTotal').text(money(total));
            $('#placeOrderTotal').text(money(total));
        }

        /* Confirm Order stays disabled until the district (and so the delivery charge) is known. */
        function gate(message) {
            $placeBtn.prop('disabled', !deliveryReady);
            $notice.toggle(!deliveryReady);
            if (!deliveryReady) $notice.html(`<i class="bi bi-geo-alt me-1"></i>${message || 'Please select district first'}`);
        }

        function loadThanas(districtId, selected) {
            $thana.prop('disabled', true).empty().append('<option></option>').trigger('change');
            deliveryReady = false;
            if (!districtId) { delivery = 0; gate(); updateTotals(); return; }
            gate('Loading delivery charge…');
            $.getJSON(S.thanasUrl.replace('__ID__', districtId))
                .done((res) => {
                    delivery = parseFloat(res.delivery_charge) || 0;
                    res.thanas.forEach((t) => $thana.append(new Option(t.text, t.id, false, String(t.id) === String(selected))));
                    $thana.prop('disabled', false).trigger('change');
                    deliveryReady = true;
                    gate();
                    updateTotals();
                })
                .fail(() => { gate('Could not load the delivery charge — please select the district again.'); updateTotals(); });
        }

        /* ---- Coupon: checked against the mobile number; re-checked when the mobile or the cart changes. */
        const MOBILE = /^01[3-9]\d{8}$/;
        const $couponInput = $('#coupon_input');
        const $couponMsg = $('#couponMsg');
        const $couponHidden = $('#coupon_code');
        const couponMsg = (text, ok) => $couponMsg.text(text || '').toggleClass('text-success', ok === true).toggleClass('text-danger', ok === false);
        const clearCoupon = () => { coupon.code = null; coupon.discount = 0; $couponHidden.val(''); $couponInput.prop('readonly', false).removeClass('is-valid'); $('[data-coupon-apply]').removeClass('d-none'); updateTotals(); };

        function applyCoupon(code, { silent = false } = {}) {
            code = (code || '').trim().toUpperCase();
            const mobile = $('#mobile').val().trim();
            if (!code) { couponMsg('Please enter a coupon code.', false); $couponInput.trigger('focus'); return; }
            if (!MOBILE.test(mobile)) {
                clearCoupon();
                couponMsg('Enter your 11 digit mobile number first — coupons are checked against it.', false);
                if (!silent) $('#mobile').trigger('focus');
                return;
            }
            const btn = $('[data-coupon-apply]')[0];
            Busy.start(btn, 'Checking…');
            $.post(S.couponUrl, { code, mobile })
                .done((res) => {
                    coupon.code = res.code;
                    coupon.discount = parseFloat(res.discount) || 0;
                    $couponHidden.val(res.code);
                    $couponInput.val(res.code).prop('readonly', true).addClass('is-valid');
                    $(btn).addClass('d-none');
                    couponMsg(res.message, true);
                    updateTotals();
                })
                .fail((xhr) => { clearCoupon(); couponMsg((xhr.responseJSON && xhr.responseJSON.message) || 'Could not check the coupon. Please try again.', false); })
                .always(() => Busy.stop(btn));
        }

        $checkout.on('click', '[data-coupon-apply]', () => applyCoupon($couponInput.val()));
        $couponInput.on('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); applyCoupon($couponInput.val()); } }); // never submits the order
        $checkout.on('click', '[data-coupon-remove]', () => { clearCoupon(); $couponInput.val(''); couponMsg(''); $couponInput.trigger('focus'); });

        // Mobile typed/changed after a coupon was applied (or entered): check it again for the new number.
        let mobileTimer = null;
        $('#mobile').on('input', function () {
            clearTimeout(mobileTimer);
            const code = coupon.code || ($couponInput.val().trim() && $couponMsg.hasClass('text-danger') && /mobile/i.test($couponMsg.text()) ? $couponInput.val() : null);
            if (!code) return;
            if (!MOBILE.test(this.value)) {
                if (coupon.code) { clearCoupon(); couponMsg('Coupon removed — finish your mobile number to apply it again.', false); }
                return;
            }
            mobileTimer = setTimeout(() => applyCoupon(code, { silent: true }), 400);
        });
        const recheckCoupon = () => { if (coupon.code) applyCoupon(coupon.code, { silent: true }); };

        // Coming back from a server error (e.g. another field): re-apply the code the customer had entered.
        if ($couponInput.val().trim() && !$couponMsg.hasClass('text-danger')) applyCoupon($couponInput.val(), { silent: true });
        gate();

        $district.on('change', function () { loadThanas(this.value); });
        if ($district.val()) loadThanas($district.val(), $thana.data('old'));
        updateTotals();

        $checkout.on('change', '.cart-line input', function () {
            const $line = $(this).closest('.cart-line');
            $.post(S.cartUpdateUrl, { product_id: $line.data('id'), qty: this.value }, (res) => {
                const item = res.items.find((i) => String(i.id) === String($line.data('id')));
                if (item) $line.find('[data-line-total]').text(item.total);
                subtotal = parseFloat(res.subtotal) || 0;
                setCount(res.count);
                updateTotals();
                recheckCoupon(); // minimum order / percentage depends on the subtotal
            });
        });

        $checkout.on('click', '[data-checkout-remove]', function () {
            const $line = $(this).closest('.cart-line');
            $.post(S.cartRemoveUrl, { product_id: $line.data('id') }, (res) => {
                if (!res.count) { window.location.reload(); return; }
                $line.slideUp(200, () => $line.remove());
                subtotal = parseFloat(res.subtotal) || 0;
                setCount(res.count);
                updateTotals();
                recheckCoupon();
            });
        });

        // "Please wait…" while the order is placed: ui.js (all forms).
    }

    /* ---------------------------------------------------------- OTP */
    const $otp = $('.otp-inputs input');
    if ($otp.length) {
        const sync = () => $('#otpValue').val($otp.map((_, el) => el.value).get().join(''));
        $otp.first().trigger('focus');
        $otp.on('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(-1);
            if (this.value) $(this).next('input').trigger('focus');
            sync();
            if ($('#otpValue').val().length === $otp.length) $('#otpForm').trigger('submit');
        }).on('keydown', function (e) {
            if (e.key === 'Backspace' && !this.value) $(this).prev('input').trigger('focus');
        }).on('paste', function (e) {
            const digits = (e.originalEvent.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, $otp.length);
            if (!digits) return;
            e.preventDefault();
            $otp.each((i, el) => { el.value = digits[i] || ''; });
            sync();
            if (digits.length === $otp.length) $('#otpForm').trigger('submit');
        });

        const $resend = $('#resendBtn');
        let left = parseInt($resend.data('wait'), 10) || 0;
        const tick = () => {
            if (left > 0) { $resend.prop('disabled', true).text(`Resend code in ${left}s`); left--; setTimeout(tick, 1000); }
            else { $resend.prop('disabled', false).text('Resend code'); }
        };
        tick();
    }

    /* ---------------------------------------------------------- Live search suggestions
     * <form data-suggest>: as the user types, shows Products (default) / Categories / Brands tabs. */
    const TABS = [
        { key: 'products', label: 'Products', icon: 'bi-box-seam' },
        { key: 'categories', label: 'Categories', icon: 'bi-grid' },
        { key: 'brands', label: 'Brands', icon: 'bi-award' },
    ];
    const reEscape = (s) => s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const highlight = (text, q) => esc(text).replace(new RegExp('(' + reEscape(esc(q)) + ')', 'ig'), '<mark>$1</mark>');

    $('form[data-suggest]').each(function () {
        const $form = $(this);
        const $input = $form.find('input[name="q"]');
        const $panel = $('<div class="ss-panel" role="listbox" aria-label="Search suggestions"></div>').appendTo($form);
        const cache = {};
        let timer = null, req = null, data = null, tab = 'products', current = '';

        $input.attr({ autocomplete: 'off', 'aria-autocomplete': 'list', spellcheck: 'false' });

        const open = () => $form.addClass('ss-open');
        const close = () => { $form.removeClass('ss-open'); $panel.find('.ss-item.active').removeClass('active'); };

        function itemHtml(type, r, q) {
            if (type === 'products') {
                const price = `<span class="ss-price">${esc(r.price)}</span>` +
                    (r.old_price ? `<span class="ss-old">${esc(r.old_price)}</span><span class="ss-off">-${r.discount}%</span>` : '');
                const cart = r.in_stock
                    ? `<button type="button" class="ss-act" data-add-to-cart="${r.id}" title="Add to cart" aria-label="Add ${esc(r.name)} to cart"><i class="bi bi-bag-plus"></i></button>`
                    : '<span class="badge text-bg-light">Out of stock</span>';
                return `<div class="ss-item" role="option">
                    <img src="${esc(r.image)}" alt="${esc(r.name)}" width="52" height="52" loading="lazy">
                    <div class="ss-body"><a class="ss-title" href="${esc(r.url)}">${highlight(r.name, q)}</a>
                        <div class="ss-meta">${price}</div></div>
                    <div class="ss-actions">${cart}<a class="ss-act ghost" href="${esc(r.url)}" title="View details" aria-label="View details"><i class="bi bi-chevron-right"></i></a></div>
                </div>`;
            }
            return `<div class="ss-item ${type === 'categories' ? 'round' : ''}" role="option">
                <img src="${esc(r.image)}" alt="${esc(r.name)}" width="52" height="52" loading="lazy">
                <div class="ss-body"><a class="ss-title" href="${esc(r.url)}">${highlight(r.name, q)}</a><div class="ss-meta">${esc(r.meta)}</div></div>
                <div class="ss-actions"><a class="ss-act ghost" href="${esc(r.url)}" title="Open" aria-label="Open ${esc(r.name)}"><i class="bi bi-chevron-right"></i></a></div>
            </div>`;
        }

        function render() {
            const q = data.query;
            const tabs = TABS.map((t) => `<button type="button" class="${t.key === tab ? 'active' : ''}" data-tab="${t.key}" role="tab" aria-selected="${t.key === tab}">
                <i class="bi ${t.icon}"></i>${t.label}<span class="n">${data[t.key].length}</span></button>`).join('');
            const rows = data[tab];
            const label = TABS.find((t) => t.key === tab).label.toLowerCase();
            const list = rows.length
                ? rows.map((r) => itemHtml(tab, r, q)).join('')
                : `<div class="ss-empty"><i class="bi bi-search"></i>No ${label} found for “${esc(q)}”</div>`;
            $panel.html(`<div class="ss-tabs" role="tablist">${tabs}</div><div class="ss-list">${list}</div>
                <div class="ss-footer"><a href="${esc(data.all_url)}">See all results for “${esc(q)}” <i class="bi bi-arrow-right"></i></a>
                <span class="text-muted small"><kbd>↑</kbd> <kbd>↓</kbd> to navigate · <kbd>Enter</kbd> to open</span></div>`);
            open();
        }

        function loading() {
            $panel.html('<div class="ss-list">' + '<div class="ss-skel"><div class="skeleton"></div><div class="flex-grow-1"><div class="skeleton mb-2" style="height:14px;width:70%"></div><div class="skeleton" style="height:12px;width:35%"></div></div></div>'.repeat(3) + '</div>');
            open();
        }

        function search(q) {
            current = q;
            const done = (res) => {
                if (res.query !== current) return;
                data = res;
                // Products open by default; jump to the first tab with results if products is empty.
                tab = res.products.length ? 'products' : (TABS.find((t) => res[t.key].length) || TABS[0]).key;
                render();
            };
            if (cache[q]) { done(cache[q]); return; }
            if (!data) loading();
            if (req) req.abort();
            req = $.getJSON(S.suggestUrl, { q }).done((res) => { cache[q] = res; done(res); });
        }

        $input.on('input', function () {
            clearTimeout(timer);
            const q = this.value.trim();
            if (q.length < S.searchMin) { data = null; close(); return; }
            timer = setTimeout(() => search(q), 220);
        }).on('focus', function () {
            if (data && this.value.trim() === data.query) open();
        }).on('keydown', function (e) {
            if (!$form.hasClass('ss-open')) return;
            const $items = $panel.find('.ss-item');
            let i = $items.index($items.filter('.active'));
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                i = e.key === 'ArrowDown' ? Math.min($items.length - 1, i + 1) : Math.max(-1, i - 1);
                $items.removeClass('active');
                if (i >= 0) $items.eq(i).addClass('active')[0].scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'Enter' && i >= 0) {
                e.preventDefault();
                window.location = $items.eq(i).find('.ss-title').attr('href');
            } else if (e.key === 'Escape') {
                close();
            }
        });

        // Keep focus in the input while switching tabs.
        $panel.on('mousedown', '[data-tab]', (e) => e.preventDefault())
            .on('click', '[data-tab]', function () { tab = $(this).data('tab'); render(); });

        // Ignore targets detached by a re-render (e.g. the tab button that was just clicked).
        $(document).on('click', (e) => { if (document.contains(e.target) && !$form[0].contains(e.target)) close(); });
        $form.on('submit', function (e) { if (!$input.val().trim()) { e.preventDefault(); $input.trigger('focus'); } });
    });

    /* ---------------------------------------------------------- Home "Load more" (featured products, 12 per click) */
    $(document).on('click', '#featuredMore [data-load-more]', function () {
        const $bar = $('#featuredMore');
        const $btn = $(this);
        if (this.dataset.busy) return; // ignore double clicks while loading
        Busy.start(this, 'Loading…');
        $.getJSON($bar.data('url'), { page: $bar.data('next') })
            .done((res) => {
                const $cards = $(res.html).addClass('card-in');
                $('#featuredGrid').append($cards);
                $bar.data('next', res.next_page).find('[data-loaded]').text(res.loaded);
                $btn.toggleClass('d-none', !res.has_more);
                $bar.find('[data-view-all]').toggleClass('d-none', !res.reached_max);
                // Keep keyboard users in place: focus the first new product link.
                $cards.first().find('a').first().trigger('focus', { preventScroll: true });
            })
            .fail(() => toast('Could not load more products. Please try again.', 'error'))
            .always(() => Busy.stop(this));
    });

    /* Copy buttons: <button data-copy="text"> (copyText() lives in ui.js) */
    $(document).on('click', '[data-copy]', function () {
        const text = this.getAttribute('data-copy');
        if (!text) return;
        copyText(text).then(() => toast('Link copied')).catch(() => toast('Could not copy — please copy the address bar instead', 'error'));
    });

    /* Tooltips and "Please wait…" buttons: see ui.js */

    /* ---------------------------------------------------------- Flash messages */
    $(function () {
        if (S.flash && S.flash.success) toast(S.flash.success);
        if (S.flash && S.flash.error) toast(S.flash.error, 'error');
    });
})(jQuery);
