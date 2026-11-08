/* Shared UI behaviour for the storefront and the admin panel (requires jQuery; loaded before page scripts).
 *
 * 1) Busy buttons — "Please wait…" while an action runs
 *    - Every form submit: the clicked submit button shows a spinner + "Please wait…" and all submit
 *      buttons of that form are disabled (opt out with <form data-no-busy>).
 *    - Any <a>/<button> with class "js-busy": same on click (links that start a slow action).
 *    - AJAX code: Busy.start(button) before the request, Busy.stop(button) when it fails or finishes.
 *    Buttons come back on their own when the page is shown again from the back/forward cache.
 *
 * 2) Tooltips for every element with a title (pointer devices only)
 *    One tooltip at a time: shows after a short hover, hides as soon as the pointer leaves —
 *    also if the element was disabled, re-rendered or removed meanwhile — and on click, scroll,
 *    Escape, or when a dropdown/modal opens.
 */
(function ($) {
    'use strict';

    /* ================================================================== Tooltips */
    const Tip = (() => {
        const SELECTOR = '[data-tip], [title]:not(iframe):not(link):not(style):not(title):not(.select2-selection__rendered):not(.select2-selection__choice__remove):not(.note-btn):not(.note-color-btn):not(.note-icon-caret)';
        const canHover = window.matchMedia('(hover: hover)').matches;
        let tip = null;
        let current = null;
        let timer = null;

        function text(el) {
            // Native titles become data-tip so the browser's own tooltip never doubles up.
            if (el.hasAttribute('title')) {
                const t = el.getAttribute('title');
                if (t) el.setAttribute('data-tip', t);
                el.removeAttribute('title');
            }
            return (el.getAttribute('data-tip') || '').trim();
        }

        function build() {
            tip = document.createElement('div');
            tip.className = 'tooltip ui-tip';
            tip.setAttribute('role', 'tooltip');
            tip.innerHTML = '<div class="tooltip-arrow"></div><div class="tooltip-inner"></div>';
            document.body.appendChild(tip);
        }

        function place(el) {
            const r = el.getBoundingClientRect();
            const t = tip.getBoundingClientRect();
            const gap = 8;
            const below = r.top - t.height - gap < 4; // not enough room above → show below
            tip.classList.toggle('bs-tooltip-top', !below);
            tip.classList.toggle('bs-tooltip-bottom', below);
            const center = r.left + r.width / 2;
            const left = Math.min(Math.max(6, center - t.width / 2), document.documentElement.clientWidth - t.width - 6);
            const top = below ? r.bottom + gap : r.top - t.height - gap;
            tip.style.left = `${left + window.scrollX}px`;
            tip.style.top = `${top + window.scrollY}px`;
            tip.querySelector('.tooltip-arrow').style.left = `${Math.min(Math.max(8, center - left - 6), t.width - 20)}px`;
        }

        function show(el) {
            const label = text(el);
            if (!label || !el.isConnected) return;
            if (!tip) build();
            tip.querySelector('.tooltip-inner').textContent = label;
            tip.classList.remove('show');
            tip.style.left = '-9999px';
            tip.style.top = '0px';
            tip.style.display = 'block';
            place(el);
            tip.classList.add('show');
            current = el;
            el.setAttribute('aria-describedby', 'ui-tooltip');
        }

        function hide() {
            clearTimeout(timer);
            timer = null;
            if (current) current.removeAttribute('aria-describedby');
            current = null;
            if (tip) { tip.classList.remove('show'); tip.style.display = 'none'; }
        }

        function schedule(el) {
            if (el === current) return;
            hide();
            text(el); // strip the native title right away
            timer = setTimeout(() => show(el), 200);
        }

        if (canHover) {
            document.addEventListener('mouseover', (e) => {
                const el = e.target.closest && e.target.closest(SELECTOR);
                if (el) schedule(el); else if (current || timer) hide();
            });
            document.addEventListener('mouseout', (e) => {
                const el = e.target.closest && e.target.closest(SELECTOR);
                if (el && !(e.relatedTarget && el.contains(e.relatedTarget))) hide();
            });
            // Safety net: whatever happened to the element, the tooltip goes once the pointer is elsewhere.
            let raf = null;
            document.addEventListener('mousemove', (e) => {
                if (!current || raf) return;
                raf = requestAnimationFrame(() => {
                    raf = null;
                    if (current && (!current.isConnected || !current.contains(document.elementFromPoint(e.clientX, e.clientY)))) hide();
                });
            }, { passive: true });
            document.addEventListener('mouseleave', hide);
        }
        // Keyboard users: show on focus via Tab.
        document.addEventListener('focusin', (e) => {
            const el = e.target.closest && e.target.closest(SELECTOR);
            if (el && el.matches(':focus-visible')) schedule(el);
        });
        document.addEventListener('focusout', hide);
        ['mousedown', 'touchstart', 'wheel'].forEach((ev) => document.addEventListener(ev, hide, { passive: true }));
        window.addEventListener('scroll', hide, { passive: true, capture: true });
        window.addEventListener('resize', hide);
        window.addEventListener('blur', hide);
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') hide(); });
        $(document).on('show.bs.dropdown show.bs.modal show.bs.offcanvas', hide);

        return { hide };
    })();
    window.Tip = Tip;

    /* ================================================================== Same-site AJAX */
    // Links printed by the server may say http:// while the page is https:// (or www./no-www): browsers block
    // such requests. Same-site requests always go to the address the page was opened on.
    $.ajaxPrefilter((options) => {
        try {
            const u = new URL(options.url, location.href);
            const bare = (h) => h.replace(/^www\./, '');
            if (u.origin !== location.origin && bare(u.hostname) === bare(location.hostname)) {
                options.url = location.origin + u.pathname + u.search + u.hash;
            }
        } catch (e) { /* leave unusual URLs alone */ }
    });

    /* ================================================================== Clipboard */
    // navigator.clipboard needs https (or localhost); plain-http sites use the textarea fallback.
    window.copyText = function (text) {
        if (navigator.clipboard && window.isSecureContext) return navigator.clipboard.writeText(text);
        return new Promise((resolve, reject) => {
            const ta = Object.assign(document.createElement('textarea'), { value: text, readOnly: true });
            ta.style.cssText = 'position:fixed;top:0;left:0;opacity:0';
            document.body.appendChild(ta);
            ta.select();
            const ok = document.execCommand('copy');
            ta.remove();
            ok ? resolve() : reject(new Error('copy failed'));
        });
    };

    /* ================================================================== Busy buttons */
    const SPINNER = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>';

    const Busy = {
        start(btn, label = 'Please wait…') {
            btn = btn && (btn.jquery ? btn[0] : btn);
            if (!btn || btn.dataset.busy) return;
            Tip.hide();
            btn.dataset.busy = '1';
            btn.dataset.busyHtml = btn.innerHTML;
            btn.style.minWidth = `${btn.offsetWidth}px`; // no layout jump
            const iconOnly = btn.classList.contains('btn-icon') || btn.textContent.trim() === '';
            btn.innerHTML = iconOnly ? SPINNER : `${SPINNER}<span class="ms-1">${label}</span>`;
            btn.setAttribute('aria-busy', 'true');
            if (btn.tagName === 'A') { btn.classList.add('disabled'); btn.setAttribute('aria-disabled', 'true'); } else { btn.disabled = true; }
        },
        stop(btn) {
            btn = btn && (btn.jquery ? btn[0] : btn);
            if (!btn || !btn.dataset.busy) return;
            btn.innerHTML = btn.dataset.busyHtml;
            delete btn.dataset.busy;
            delete btn.dataset.busyHtml;
            btn.style.minWidth = '';
            btn.removeAttribute('aria-busy');
            if (btn.tagName === 'A') { btn.classList.remove('disabled'); btn.removeAttribute('aria-disabled'); } else { btn.disabled = false; }
        },
        /** Disable all submit buttons of a form; the one used gets the spinner text. */
        form(form, submitter) {
            const buttons = Array.from(form.querySelectorAll('button:not([type]), button[type=submit], input[type=submit]'));
            const main = submitter && buttons.includes(submitter) ? submitter : buttons[0];
            buttons.forEach((b) => (b === main ? Busy.start(b) : (b.disabled = true, b.dataset.busyLock = '1')));
        },
        stopAll(root = document) {
            root.querySelectorAll('[data-busy]').forEach((b) => Busy.stop(b));
            root.querySelectorAll('[data-busy-lock]').forEach((b) => { b.disabled = false; delete b.dataset.busyLock; });
        },
    };
    window.Busy = Busy;

    // Forms: decided after every other submit handler (validation, confirm dialogs) has run,
    // so a blocked submit never leaves a button stuck. The form data is already captured by then,
    // which keeps the clicked button's name/value (e.g. "Save & add another").
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (!(form instanceof HTMLFormElement) || form.hasAttribute('data-no-busy')) return;
        const submitter = e.submitter || null;
        setTimeout(() => { if (!e.defaultPrevented) Busy.form(form, submitter); }, 0);
    });

    // Links/buttons with the class: busy on click (unless something cancelled the click).
    document.addEventListener('click', (e) => {
        const el = e.target.closest && e.target.closest('.js-busy');
        if (!el || el.dataset.busy) return;
        if (el.tagName === 'A' && (e.ctrlKey || e.metaKey || e.shiftKey || el.target === '_blank')) return; // opens elsewhere
        setTimeout(() => { if (!e.defaultPrevented) Busy.start(el); }, 0);
    });

    // Back/forward cache: the page comes back exactly as it was left — re-enable everything.
    window.addEventListener('pageshow', (e) => { if (e.persisted) Busy.stopAll(); });
})(jQuery);
