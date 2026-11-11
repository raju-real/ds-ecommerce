{{-- "Generate" for a slug field: type or paste any text → the slug exactly as saving makes it (server: Admin\SeoController@slug,
     same conversion and a -2 ending when the address is already used). Expects $type (product|blog), $target ('#slug'), $id (editing).
     The text box has no name, so it is never sent with the form. Script: admin.js [data-slug-gen]. --}}
<div class="slug-gen input-group input-group-sm" data-slug-gen data-url="{{ route('admin.seo.slug') }}" data-type="{{ $type }}" data-target="{{ $target ?? '#slug' }}" data-id="{{ $id ?? '' }}">
    <input type="text" class="form-control" placeholder="Write or paste text for the URL…" maxlength="250" aria-label="Text to turn into the URL slug" data-slug-gen-text data-no-validate autocomplete="off">
    <button type="button" class="btn btn-soft-primary" data-slug-gen-btn><i class="bi bi-magic me-1"></i>Generate</button>
</div>
