/* Admin panel interactions. Requires jQuery, Bootstrap bundle, SweetAlert2, Select2. */
(function ($) {
    'use strict';

    const csrf = $('meta[name="csrf-token"]').attr('content');
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } });

    const Toast = Swal.mixin({ toast: true, position: 'bottom-end', showConfirmButton: false, timer: 3000, timerProgressBar: true, returnFocus: false, focusConfirm: false, customClass: { container: 'admin-toast' } });
    window.notify = (title, icon = 'success') => Toast.fire({ icon, title });

    /* Sidebar: off-canvas on mobile, icon-only collapse on desktop (remembered per browser) */
    const isDesktop = () => window.matchMedia('(min-width: 992px)').matches;
    const isCollapsed = () => isDesktop() && document.body.classList.contains('sidebar-collapsed');
    requestAnimationFrame(() => document.body.classList.remove('no-anim'));

    $('[data-toggle-sidebar]').on('click', function () {
        Tip.hide();
        if (!isDesktop()) { $('.sidebar, .backdrop').toggleClass('show'); return; }
        const collapsed = document.body.classList.toggle('sidebar-collapsed');
        try { localStorage.setItem('admin.sidebar', collapsed ? 'collapsed' : 'expanded'); } catch (e) { /* storage blocked */ }
        hideFlyout(true);
    });
    $('.backdrop').on('click', () => { $('.sidebar, .backdrop').removeClass('show'); });

    /* Collapsed sidebar flyout: menu name, or the submenu links of a group */
    const $fly = $('<div class="nav-flyout" role="menu"></div>').appendTo(document.body);
    let flyTimer = null;
    let $flyOwner = $();
    function hideFlyout(now) {
        clearTimeout(flyTimer);
        const hide = () => { $fly.removeClass('show'); $flyOwner.removeClass('hovered'); $flyOwner = $(); };
        now ? hide() : (flyTimer = setTimeout(hide, 140));
    }
    function showFlyout(item) {
        if (!isCollapsed()) return;
        clearTimeout(flyTimer);
        const $item = $(item);
        const $link = $item.is('.nav-group') ? $item.children('.nav-link') : $item;
        const $subs = $item.find('.sub .nav-link');
        const badge = $link.find('.badge').prop('outerHTML') || '';
        const $head = $('<div class="fly-head"></div>').text($link.data('label')).append(badge);
        $fly.empty().append($head).toggleClass('is-group', $subs.length > 0);
        $subs.each(function () { $fly.append($('<a></a>').attr('href', this.href).text($(this).text()).toggleClass('active', $(this).hasClass('active'))); });

        $flyOwner.removeClass('hovered');
        $flyOwner = $link.addClass('hovered');
        const r = $link[0].getBoundingClientRect();
        const h = $fly.outerHeight();
        const top = $subs.length ? Math.min(r.top, window.innerHeight - h - 8) : r.top + r.height / 2 - h / 2;
        $fly.css({ top: Math.max(8, top), left: r.right + 12, '--arrow-top': (r.top + r.height / 2 - Math.max(8, top)) + 'px' }).addClass('show');
    }
    $('.sidebar nav')
        .on('mouseenter focusin', '.nav-link[data-label]:not(.has-sub), .nav-group', function () { showFlyout(this); })
        .on('mouseleave focusout', '.nav-link[data-label]:not(.has-sub), .nav-group', () => hideFlyout())
        .on('scroll', () => hideFlyout(true))
        // Collapsed: a group icon opens its first page instead of expanding an invisible submenu.
        .on('click', '.nav-link.has-sub', function (e) {
            if (!isCollapsed()) return;
            e.preventDefault();
            e.stopPropagation();
            window.location = this.href;
        });
    $fly.on('mouseenter', () => clearTimeout(flyTimer)).on('mouseleave', () => hideFlyout());
    $(window).on('resize', () => hideFlyout(true));

    /* Tooltips and "Please wait…" buttons: see ui.js */

    /* Select2 */
    const initSelect2 = (ctx) => $(ctx).find('select.select2').each(function () {
        $(this).select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: $(this).data('placeholder') || 'Select',
            allowClear: !this.required,
            dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal') : $(document.body),
        });
    });
    initSelect2(document);

    /* Confirm before destructive submit: <form data-confirm="Delete this?"> */
    $(document).on('submit', 'form[data-confirm]', function (e) {
        const form = this;
        if (form.dataset.confirmed) return;
        e.preventDefault();
        const submitter = e.originalEvent && e.originalEvent.submitter;
        Swal.fire({
            title: 'Are you sure?',
            text: $(form).data('confirm'),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, continue',
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            reverseButtons: true,
        }).then((r) => {
            if (!r.isConfirmed) return;
            form.dataset.confirmed = 1;
            // form.submit() fires no submit event, so mark the button busy here ("Please wait…").
            if (submitter && submitter.name) $('<input type="hidden">').attr({ name: submitter.name, value: submitter.value }).appendTo(form);
            Busy.form(form, submitter);
            form.submit();
        });
    });

    /* Copy to clipboard: <button data-copy="text" [data-copy-msg="Copied"]> (copyText() lives in ui.js). */
    $(document).on('click', '[data-copy]', function (e) {
        e.preventDefault();
        e.stopPropagation(); // keep the dropdown open
        const $btn = $(this);
        const text = $btn.attr('data-copy'); // attr, not .data(): short links are filled in after the page loads
        if (!text) return;
        copyText(text).then(() => {
            notify($btn.data('copy-msg') || 'Copied');
            const html = $btn.html();
            $btn.html('<i class="bi bi-check2"></i>' + ($btn.text().trim() ? ' Copied' : ''));
            setTimeout(() => $btn.html(html), 1500);
        }).catch(() => notify('Could not copy — select the link and press Ctrl+C', 'error'));
    });

    /* Share menus (admin/partials/share-menu): short links are created on first open, click counts refresh on every open. */
    const plural = (n, word) => `${n} ${word}${n === 1 ? '' : 's'}`;
    $(document).on('show.bs.dropdown', '.share-menu', function () {
        const $menu = $(this);
        $.ajax({ url: $menu.data('short-url'), method: 'POST', contentType: 'application/json', data: JSON.stringify({ targets: $menu.data('targets') }) })
            .done((rows) => {
                rows.forEach((r) => {
                    const $row = $menu.find(`[data-share-type="${r.type}"]`);
                    $row.find('[data-short-input]').val(r.url);
                    $row.find('[data-short-copy]').attr('data-copy', r.url).prop('disabled', false);
                    $row.find('[data-clicks] span').text(plural(r.clicks, 'click'));
                    $row.find('[data-short-meta]').text(r.last_clicked ? `Last clicked ${r.last_clicked}` : 'Not clicked yet');
                });
                const primary = rows.find((r) => r.type === $menu.data('primary')) || rows[0];
                if (primary) {
                    const text = `${$menu.data('share-text')} ${primary.url}`;
                    $menu.find('[data-share-fb]').attr('href', `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(primary.url)}`);
                    $menu.find('[data-share-wa]').attr('href', `https://wa.me/?text=${encodeURIComponent(text)}`);
                    $menu.find('[data-share-post]').attr('data-copy', text);
                }
            })
            .fail(() => $menu.find('[data-short-input]').attr('placeholder', 'Could not create the short link — reopen to retry'));
    });
    // Not ready yet: share buttons wait for the short link.
    $(document).on('click', '.share-menu [data-share-fb], .share-menu [data-share-wa]', function (e) {
        if ($(this).attr('href') === '#') { e.preventDefault(); notify('Creating the short link — try again in a second', 'info'); }
    });
    $(document).on('click', '.share-menu [data-share-close]', function () {
        bootstrap.Dropdown.getOrCreateInstance($(this).closest('.share-menu').find('[data-bs-toggle="dropdown"]')[0]).hide();
    });

    /* Inline toggles: <input type=checkbox data-toggle-url="..."> */
    $(document).on('change', '[data-toggle-url]', function () {
        const $el = $(this);
        $el.prop('disabled', true); // one change at a time
        $.ajax({ url: $el.data('toggle-url'), method: 'PATCH' })
            .done(() => notify('Updated'))
            .fail(() => { $el.prop('checked', !$el.prop('checked')); notify('Update failed', 'error'); })
            .always(() => $el.prop('disabled', false));
    });

    /* Single image preview: <input type=file data-preview="#img"> */
    $(document).on('change', 'input[type=file][data-preview]', function () {
        const file = this.files && this.files[0];
        if (file) $($(this).data('preview')).attr('src', URL.createObjectURL(file)).removeClass('d-none');
    });

    /* Multi image dropzone preview */
    $(document).on('change', '.dropzone input[type=file]', function () {
        const $grid = $($(this).data('grid'));
        $grid.find('.item.new').remove();
        Array.from(this.files).forEach((f) => $grid.append(`<div class="item new"><img src="${URL.createObjectURL(f)}" alt=""></div>`));
    });
    $(document).on('dragenter dragover', '.dropzone', function () { $(this).addClass('drag'); })
        .on('dragleave drop', '.dropzone', function () { $(this).removeClass('drag'); });

    /* Delete existing gallery image */
    $(document).on('click', '[data-delete-image]', function () {
        const $item = $(this).closest('.item');
        Swal.fire({ title: 'Remove this image?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc2626', confirmButtonText: 'Remove' })
            .then((r) => {
                if (!r.isConfirmed) return;
                Busy.start(this);
                $.ajax({ url: $(this).data('delete-image'), method: 'DELETE' })
                    .done(() => { $item.remove(); notify('Image removed'); })
                    .fail(() => { Busy.stop(this); notify('Could not remove the image', 'error'); });
            });
    });

    /* Rich text: <textarea class="editor" [data-upload-url] [data-upload-max="KB"] [data-blog-links-url] [data-blog-except]>
     * Images are uploaded as WebP files (never base64 inside the HTML); the server sanitises the HTML on save. */
    const escHtml = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    function uploadEditorImage($ta, file) {
        const maxKb = parseInt($ta.data('upload-max'), 10) || 4096;
        if (!/^image\/(jpeg|png|webp)$/.test(file.type)) return notify(`“${file.name}” is not allowed. Use JPG, PNG or WebP.`, 'error');
        if (file.size > maxKb * 1024) return notify(`“${file.name}” is ${(file.size / 1048576).toFixed(1)}MB. Maximum is ${(maxKb / 1024).toFixed(1)}MB.`, 'error');
        const data = new FormData();
        data.append('image', file);
        notify('Uploading image…', 'info');
        $.ajax({ url: $ta.data('upload-url'), method: 'POST', data, processData: false, contentType: false })
            .done((res) => $ta.summernote('insertImage', res.url, ($img) => $img.attr('alt', file.name.replace(/\.[^.]+$/, '').replace(/[-_]+/g, ' ')).css('max-width', '100%')))
            .fail((xhr) => notify(xhr.responseJSON?.errors?.image?.[0] || 'Image upload failed', 'error'));
    }

    /* "Blog" toolbar button: pick another post and insert a related-post card (title + link). */
    let $blogPicker = null;
    function openBlogPicker(context, url, except) {
        context.invoke('editor.saveRange');
        if (!$blogPicker) {
            $blogPicker = $(`
                <div class="modal fade" id="blogLinkModal" tabindex="-1" aria-labelledby="blogLinkTitle" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-scrollable"><div class="modal-content" style="border-radius: 16px">
                        <div class="modal-header"><h5 class="modal-title" id="blogLinkTitle"><i class="bi bi-link-45deg me-1"></i>Link another blog post</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                        <div class="modal-body">
                            <input type="search" class="form-control mb-3" placeholder="Search posts by title…" maxlength="100" aria-label="Search posts" data-blog-q>
                            <div class="list-group" data-blog-results></div>
                        </div>
                    </div></div>
                </div>`).appendTo('body');
        }
        const $q = $blogPicker.find('[data-blog-q]').val('');
        const $list = $blogPicker.find('[data-blog-results]');
        let timer = null;
        const load = () => $.getJSON(url, { q: $q.val(), except }).done((rows) => {
            $list.html(rows.length ? rows.map((r) => `
                <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-2" data-slug="${escHtml(r.slug)}" data-url="${escHtml(r.url)}" data-title="${escHtml(r.title)}">
                    <span class="text-truncate">${escHtml(r.title)}</span>${r.published ? '' : '<span class="badge badge-soft-secondary">not live</span>'}
                </button>`).join('') : '<div class="text-muted small text-center py-3">No posts found.</div>');
        });
        $q.off('input').on('input', () => { clearTimeout(timer); timer = setTimeout(load, 200); });
        $list.off('click').on('click', '[data-slug]', function () {
            const d = this.dataset;
            bootstrap.Modal.getOrCreateInstance($blogPicker[0]).hide();
            context.invoke('editor.restoreRange');
            context.invoke('editor.pasteHTML', `<p><a class="blog-ref" href="${escHtml(d.url)}" data-slug="${escHtml(d.slug)}">${escHtml(d.title)}</a></p><p><br></p>`);
        });
        load();
        bootstrap.Modal.getOrCreateInstance($blogPicker[0]).show();
        $blogPicker.one('shown.bs.modal', () => $q.trigger('focus'));
    }

    if ($.fn.summernote) {
        $('.editor').each(function () {
            const $ta = $(this);
            const linksUrl = $ta.data('blog-links-url');
            const buttons = {};
            if (linksUrl) {
                buttons.blogLink = (context) => $.summernote.ui.button({
                    contents: '<i class="bi bi-journal-plus"></i> Blog',
                    tooltip: 'Link another blog post',
                    click: () => openBlogPicker(context, linksUrl, $ta.data('blog-except')),
                }).render();
            }
            $ta.summernote({
                height: $ta.data('height') || 280,
                placeholder: $ta.attr('placeholder'),
                styleTags: ['p', { title: 'Heading', tag: 'h2', value: 'h2' }, { title: 'Subheading', tag: 'h3', value: 'h3' }, { title: 'Small heading', tag: 'h4', value: 'h4' }, 'blockquote', 'pre'],
                fontSizes: ['12', '14', '16', '18', '20', '24', '28', '32'],
                // Code view: strip scripts/event handlers and only allow video frames from known hosts (the server sanitises again on save).
                codeviewFilter: true,
                codeviewIframeFilter: true,
                codeviewIframeWhitelistSrc: ['www.youtube.com', 'www.youtube-nocookie.com', 'player.vimeo.com', 'www.facebook.com'],
                buttons,
                toolbar: [
                    ['style', ['style']], ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']], ['fontsize', ['fontsize']], ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link', 'picture', 'video', 'table', 'hr'].concat(linksUrl ? ['blogLink'] : [])],
                    ['view', ['codeview', 'fullscreen']],
                ],
                callbacks: $ta.data('upload-url') ? { onImageUpload: (files) => Array.from(files).forEach((f) => uploadEditorImage($ta, f)) } : {},
            });
            // Saving while in code view would drop the last edits: switch back first so the textarea is in sync.
            $ta.closest('form').on('submit', () => {
                if ($ta.summernote('codeview.isActivated')) $ta.summernote('codeview.deactivate');
                $ta.val($ta.summernote('code'));
            });
        });
    }

    /* Slug preview from name: <input data-slug-source="#slug"> */
    const slugify = (s) => s.toString().toLowerCase().trim().replace(/[^\w\s-]/g, '').replace(/[\s_-]+/g, '-').replace(/^-+|-+$/g, '');
    $(document).on('input', '[data-slug-source]', function () {
        const $slug = $($(this).data('slug-source'));
        if (!$slug.data('touched')) $slug.attr('placeholder', slugify(this.value));
    });
    $(document).on('input', '[data-slug]', function () { $(this).data('touched', true); });

    /* Flash */
    $(function () {
        const f = window.ADMIN_FLASH || {};
        if (f.success) notify(f.success);
        if (f.error) Swal.fire({ icon: 'error', title: 'Oops', text: f.error });
    });
})(jQuery);

/* Notifications: every 30 s (while the tab is visible) refresh the bell, the pending-order counts and the
 * tab title; a toast announces each new order. The summary endpoint is served from cache. */
(function ($) {
    const cfg = window.ADMIN_NOTIFY;
    if (!cfg) return;
    const baseTitle = document.title.replace(/^\(\d+\+?\)\s*/, '');
    let lastId = cfg.latestId || 0;
    const setTitle = (n) => { document.title = (n ? `(${n > 99 ? '99+' : n}) ` : '') + baseTitle; };

    function apply(res) {
        const unseen = res.unseen > 99 ? '99+' : res.unseen;
        $('[data-notify-count]').text(unseen).toggleClass('d-none', !res.unseen);
        $('[data-notify-mark-all]').toggleClass('d-none', !res.unseen);
        $('[data-pending-count]').text(res.pending);
        $('.sidebar [data-pending-count]').toggleClass('d-none', !res.pending);
        // Don't swap the list under the cursor while the dropdown is open.
        if (!$('.notify-bell .dropdown-menu').hasClass('show')) $('[data-notify-list]').html(res.html);
        setTitle(res.unseen);
        if (res.latest_id && res.latest_id > lastId) {
            lastId = res.latest_id;
            notify(res.latest_title, 'info');
        }
    }
    const poll = () => { if (!document.hidden) $.getJSON(cfg.url).done(apply); };
    setTitle(parseInt($('.notify-bell [data-notify-count]').text(), 10) || 0);
    setInterval(poll, 30000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });
    window.pollNotifications = poll; // handy for testing / manual refresh
})(jQuery);

/* Bootstrap Datepicker: <div class="input-daterange"> for from/to ranges, .datepicker-input for single dates.
 * Format matches the server rule date_format:Y-m-d. */
(function ($) {
    if (!$.fn.datepicker) return;
    const opts = { format: 'yyyy-mm-dd', autoclose: true, todayHighlight: true, clearBtn: true, todayBtn: 'linked', weekStart: 6, orientation: 'bottom auto' };
    $('.input-daterange').datepicker($.extend({}, opts, { inputs: $('.input-daterange input.form-control') }));
    $('.datepicker-input').datepicker(opts);
})(jQuery);
