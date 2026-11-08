/* Shared client-side validation for storefront and admin (requires jQuery).
 *
 * <form data-validate novalidate>  checks required / minlength / maxlength / pattern / min / max / type
 *                                  before submit, mirroring the server rules, with inline Bootstrap messages.
 *   data-msg-required / data-msg-pattern on a field override the default text.
 * <input data-digits>              strips non-digits while typing.
 * <input type=file data-max-size="KB" accept=".jpg,..." [data-max-files] [data-min-width] [data-min-height]>
 *                                  rejects bad files immediately (same limits as upload_rules()).
 * <input|textarea data-counter maxlength=N>  live "x / N" character counter.
 *
 * Loaded before page scripts so its submit handler runs first. */
(function ($) {
    'use strict';

    function anchorOf(el) {
        const $el = $(el);
        if ($el.next('.select2').length) return $el.next('.select2');
        if ($el.closest('.dropzone').length) return $el.closest('.dropzone');
        if ($el.next('.note-editor').length) return $el.next('.note-editor');
        if ($el.parent('.input-group').length) return $el.parent();
        return $el;
    }

    function showError(el, msg) {
        const $anchor = anchorOf(el);
        let $fb = $anchor.nextAll('.invalid-feedback').first();
        if (msg) {
            if (!$fb.length) $fb = $('<div class="invalid-feedback d-block"></div>').insertAfter($anchor);
            $fb.addClass('d-block').text(msg);
            $(el).addClass('is-invalid');
        } else {
            $(el).removeClass('is-invalid');
            $fb.removeClass('d-block').text('');
        }
        return !msg;
    }
    window.showFieldError = showError;

    window.validateField = function (el) {
        if (!el.willValidate) return true;
        // Size/type/dimension errors are set by the change handler below; only "required" is checked here.
        if (el.type === 'file') return !(el.required && !el.files.length) || showError(el, el.dataset.msgRequired || 'Please choose a file.');
        const v = el.validity;
        if (v.valid) return showError(el, null);
        return showError(el,
            (v.valueMissing && (el.dataset.msgRequired || (el.tagName === 'SELECT' ? 'Please select an option.' : 'This field is required.'))) ||
            (v.tooShort && `Please enter at least ${el.minLength} characters (currently ${el.value.length}).`) ||
            (v.tooLong && `Please keep this under ${el.maxLength} characters.`) ||
            (v.patternMismatch && (el.dataset.msgPattern || 'Please match the requested format.')) ||
            (v.rangeUnderflow && `Value must be at least ${el.min}.`) ||
            (v.rangeOverflow && `Value must be ${el.max} or less.`) ||
            (v.stepMismatch && 'Please enter a valid number.') ||
            (v.badInput && 'Please enter a valid number.') ||
            (v.typeMismatch && (el.type === 'email' ? 'Please enter a valid email address.' : 'Please enter a valid URL starting with https://')) ||
            el.validationMessage);
    };

    $('form[data-validate]').each(function () {
        const form = this;
        $(form).on('submit', function (e) {
            const invalid = Array.from(form.elements).filter((el) => el.willValidate && !validateField(el))
                .concat($(form).find('input[type=file].is-invalid').get());
            form.dataset.touched = 1;
            if (!invalid.length) return;
            e.preventDefault();
            e.stopImmediatePropagation();
            const first = invalid[0];
            const pane = $(first).closest('.tab-pane:not(.active)');
            if (pane.length && window.bootstrap) bootstrap.Tab.getOrCreateInstance(document.querySelector(`[data-bs-target="#${pane.attr('id')}"]`)).show();
            setTimeout(() => {
                first.scrollIntoView({ behavior: 'smooth', block: 'center' });
                $(first).next('.select2').length ? $(first).select2('open') : first.focus({ preventScroll: true });
            }, pane.length ? 200 : 0);
        }).on('input change', 'input, select, textarea', function () {
            if (form.dataset.touched || this.classList.contains('is-invalid')) validateField(this);
        });
    });

    $(document).on('input', 'input[data-digits]', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, this.maxLength > 0 ? this.maxLength : 20);
    });

    /* ---------------- File inputs */
    const extOk = (file, accept) => !accept || accept.split(',').some((a) => file.name.toLowerCase().endsWith(a.trim().toLowerCase()));
    const imageSize = (file) => new Promise((resolve) => {
        const img = new Image();
        img.onload = () => { resolve([img.naturalWidth, img.naturalHeight]); URL.revokeObjectURL(img.src); };
        img.onerror = () => resolve([0, 0]);
        img.src = URL.createObjectURL(file);
    });

    function rejectFiles(input, msg) {
        const $in = $(input);
        input.value = '';
        $($in.data('grid')).find('.item.new').remove();
        const $prev = $($in.data('preview'));
        if ($prev.length) $prev.data('original') ? $prev.attr('src', $prev.data('original')) : $prev.addClass('d-none');
        showError(input, msg);
    }

    /* Large photos are scaled down in the browser before upload (data-fit="WxH" = the size the server keeps;
     * the image still covers that box, so the server's crop/resize loses nothing). A 5 MB phone photo becomes
     * a few hundred KB: uploads are fast and stay under the web server's request limit (nginx: 1 MB by default).
     * The server still resizes and converts every image. */
    const SHRINK_OVER = 400 * 1024;   // smaller files are sent as they are
    const TARGET = 900 * 1024;        // aim for each image to be under this
    const QUALITIES = [0.9, 0.82, 0.72, 0.6];
    function shrink(file, fit) {
        const [fw, fh] = fit.split('x').map(Number);
        if (!fw || !fh || file.size <= SHRINK_OVER || !window.createImageBitmap) return Promise.resolve(file);
        return createImageBitmap(file).then((bmp) => {
            const scale = Math.min(1, Math.max(fw / bmp.width, fh / bmp.height));
            const canvas = document.createElement('canvas');
            canvas.width = Math.round(bmp.width * scale);
            canvas.height = Math.round(bmp.height * scale);
            canvas.getContext('2d').drawImage(bmp, 0, 0, canvas.width, canvas.height);
            bmp.close && bmp.close();
            const encode = (k) => new Promise((resolve) => canvas.toBlob(resolve, 'image/webp', QUALITIES[k])).then((blob) => {
                // WebP keeps transparency (logos). Browsers that cannot encode WebP hand back PNG — keep the original then.
                if (!blob || blob.type !== 'image/webp') return null;
                return blob.size > TARGET && k + 1 < QUALITIES.length ? encode(k + 1) : blob;
            });
            return encode(0).then((blob) => (!blob || blob.size >= file.size ? file
                : new File([blob], file.name.replace(/\.[^.]+$/, '') + '.webp', { type: 'image/webp', lastModified: Date.now() })));
        }).catch(() => file);
    }

    $(document).on('change', 'input[type=file][data-max-size]', function (e) {
        const input = this;
        const files = Array.from(input.files || []);
        const $prev = $($(input).data('preview'));
        if ($prev.length && $prev.data('original') === undefined) $prev.data('original', $prev.hasClass('d-none') ? '' : $prev.attr('src'));
        if (!files.length) { showError(input, null); return; }

        const maxKb = parseInt(input.dataset.maxSize, 10);
        const maxFiles = parseInt(input.dataset.maxFiles || '0', 10);
        const accept = input.getAttribute('accept');
        let msg = null;
        if (maxFiles && files.length > maxFiles) msg = `You can upload at most ${maxFiles} images at a time (selected ${files.length}).`;
        const badType = files.find((f) => !extOk(f, accept) || !f.type.startsWith('image/'));
        if (!msg && badType) msg = `“${badType.name}” is not allowed. Use ${accept.replace(/\./g, '').toUpperCase().replace(/,/g, ', ')}.`;

        if (msg) { e.stopImmediatePropagation(); rejectFiles(input, msg); return; }
        showError(input, null);

        const minW = parseInt(input.dataset.minWidth || '0', 10);
        const minH = parseInt(input.dataset.minHeight || '0', 10);
        const fit = input.dataset.fit;
        const tooBigMsg = (f) => `“${f.name}” is ${(f.size / 1048576).toFixed(1)}MB. Maximum size is ${(maxKb / 1024).toFixed(1)}MB.`;

        const token = input._token = {};
        input._pending = Promise.all(files.map(imageSize)).then((sizes) => {
            if (input._token !== token) return;
            const i = sizes.findIndex(([w, h]) => w < minW || h < minH);
            if ((minW || minH) && i >= 0) {
                rejectFiles(input, `“${files[i].name}” is ${sizes[i][0]}×${sizes[i][1]}px. Minimum is ${minW}×${minH}px.`);
                return;
            }
            return Promise.all(files.map((f) => (fit ? shrink(f, fit) : f))).then((out) => {
                if (input._token !== token) return; // another file was picked meanwhile
                const tooBig = out.findIndex((f) => f.size > maxKb * 1024);
                if (tooBig >= 0) { rejectFiles(input, tooBigMsg(files[tooBig])); return; }
                if (out.some((f, k) => f !== files[k]) && window.DataTransfer) {
                    try {
                        const dt = new DataTransfer();
                        out.forEach((f) => dt.items.add(f));
                        input.files = dt.files; // does not fire "change" again
                    } catch (err) { /* very old browser: the original files are uploaded */ }
                }
            });
        }).finally(() => { input._pending = null; });
    });

    // A form submitted while its images are still being prepared waits for them, then submits itself.
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) return;
        const pending = Array.from(form.querySelectorAll('input[type=file]')).map((i) => i._pending).filter(Boolean);
        if (!pending.length) return;
        e.preventDefault();
        e.stopImmediatePropagation();
        const submitter = e.submitter && e.submitter.form === form ? e.submitter : undefined;
        Promise.all(pending).then(() => (form.requestSubmit ? form.requestSubmit(submitter) : form.submit()));
    }, true);

    /* ---------------- Character counters */
    function count(el) {
        const max = el.maxLength;
        const n = el.value.length;
        $(el).data('counter').text(`${n} / ${max}`).toggleClass('text-warning', n > max * 0.9);
    }
    $('[data-counter][maxlength]').each(function () {
        const $c = $('<span class="char-count"></span>');
        const $anchor = anchorOf(this);
        $('<div class="d-flex justify-content-end mt-1"></div>').append($c).insertAfter($anchor);
        $(this).data('counter', $c);
        count(this);
    }).on('input', function () { count(this); });
})(jQuery);
