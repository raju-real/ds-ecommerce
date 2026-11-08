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
        const tooBig = files.find((f) => f.size > maxKb * 1024);
        if (!msg && tooBig) msg = `“${tooBig.name}” is ${(tooBig.size / 1048576).toFixed(1)}MB. Maximum size is ${(maxKb / 1024).toFixed(1)}MB.`;

        if (msg) { e.stopImmediatePropagation(); rejectFiles(input, msg); return; }
        showError(input, null);

        const minW = parseInt(input.dataset.minWidth || '0', 10);
        const minH = parseInt(input.dataset.minHeight || '0', 10);
        if (minW || minH) {
            Promise.all(files.map(imageSize)).then((sizes) => {
                const i = sizes.findIndex(([w, h]) => w < minW || h < minH);
                if (i >= 0) rejectFiles(input, `“${files[i].name}” is ${sizes[i][0]}×${sizes[i][1]}px. Minimum is ${minW}×${minH}px.`);
            });
        }
    });

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
