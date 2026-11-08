{{--
    Share menu: full + short links (created on first open), click counts, copy and share buttons.
    Expects: $title, $image (thumbnail URL), $targets = [['type' => 'product|buy|blog', 'uid' => …, 'label' => …, 'url' => full URL], …],
             $primary (type whose short link is used by Facebook/WhatsApp), $shareText (text before the link).
    Optional: $warning, $label (button text; icon-only when missing).
--}}
<div class="dropdown d-inline-block share-menu" data-short-url="{{ route('admin.short-links.ensure') }}"
     data-targets='@json(collect($targets)->map(fn ($t) => ['type' => $t['type'], 'uid' => $t['uid']])->values())' data-primary="{{ $primary }}" data-share-text="{{ $shareText }}">
    <button type="button" class="btn btn-soft-success {{ isset($label) ? '' : 'btn-icon' }}" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
            data-bs-popper-config='{"strategy":"fixed"}' title="Share & short links" aria-label="Share links for {{ $title }}">
        <i class="bi bi-share {{ isset($label) ? 'me-1' : '' }}"></i>{{ $label ?? '' }}
    </button>
    <div class="dropdown-menu dropdown-menu-end share-panel shadow border-0 p-0">
        <div class="share-head">
            <img src="{{ $image }}" alt="" width="40" height="40">
            <div class="min-w-0 flex-grow-1">
                <div class="fw-semibold small text-truncate">{{ $title }}</div>
                <div class="text-muted share-sub">Share links · previews show this image</div>
            </div>
            <button type="button" class="btn-close" data-share-close aria-label="Close"></button>
        </div>
        <div class="p-3">
            @isset($warning)
                <div class="alert alert-warning py-1 px-2 small mb-3">{!! $warning !!}</div>
            @endisset
            @foreach($targets as $t)
                <div class="share-row" data-share-type="{{ $t['type'] }}">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="share-label">{{ $t['label'] }}</span>
                        <span class="badge badge-soft-primary" data-clicks title="Clicks on the short link (people only, not link-preview bots)"><i class="bi bi-hand-index-thumb me-1"></i><span>…</span></span>
                    </div>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" value="{{ $t['url'] }}" readonly aria-label="{{ $t['label'] }} link" placeholder="Link" onclick="this.select()">
                        <button type="button" class="btn btn-soft-secondary" data-copy="{{ $t['url'] }}" data-copy-msg="Link copied" title="Copy the full link"><i class="bi bi-clipboard"></i></button>
                    </div>
                    <div class="input-group input-group-sm mt-1">
                        <span class="input-group-text" title="Short link"><i class="bi bi-scissors"></i></span>
                        <input type="text" class="form-control fw-semibold" value="" readonly aria-label="{{ $t['label'] }} short link" placeholder="Creating short link…" data-short-input onclick="this.select()">
                        <button type="button" class="btn btn-primary" data-copy="" data-copy-msg="Short link copied" data-short-copy disabled><i class="bi bi-clipboard me-1"></i>Copy</button>
                    </div>
                    <div class="share-meta" data-short-meta></div>
                </div>
            @endforeach
            <div class="d-flex gap-2 pt-1">
                <a class="btn btn-sm btn-soft-primary flex-fill" target="_blank" rel="noopener" data-share-fb href="#"><i class="bi bi-facebook me-1"></i>Facebook</a>
                <a class="btn btn-sm btn-soft-success flex-fill" target="_blank" rel="noopener" data-share-wa href="#"><i class="bi bi-whatsapp me-1"></i>WhatsApp</a>
                <button type="button" class="btn btn-sm btn-soft-secondary" data-copy="" data-share-post data-copy-msg="Post text copied" title="Copy text for a post (with the short link)"><i class="bi bi-chat-left-text"></i></button>
            </div>
        </div>
    </div>
</div>
