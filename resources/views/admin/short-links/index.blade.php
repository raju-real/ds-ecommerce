@extends('admin.layouts.app')

@section('title', 'Short links')

@section('content')
    <div class="page-header">
        <div>
            <h1>Short links</h1>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li><li class="breadcrumb-item active">Short links</li></ol></nav>
        </div>
        <span class="badge badge-soft-primary fs-6 py-2 px-3"><i class="bi bi-hand-index-thumb me-1"></i>{{ number_format($totalClicks) }} clicks in total</span>
    </div>

    <div class="alert alert-info border-0 small d-flex gap-2" style="border-radius: 12px">
        <i class="bi bi-info-circle-fill"></i>
        <div>Short links are created from the <strong>Share</strong> button on products and blog posts. They keep working when a product name/link or the domain changes, Facebook/WhatsApp previews show the image, and only clicks by people are counted (not preview bots).</div>
    </div>

    <div class="card">
        <div class="card-header">
            <form class="filter-bar">
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control search" placeholder="Product, blog title or code…" maxlength="100">
                <select name="type" class="form-select" style="max-width: 200px" aria-label="Type">
                    <option value="">All types</option>
                    @foreach(\App\Models\ShortLink::TYPES as $value => $label)<option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>@endforeach
                </select>
                <select name="sort" class="form-select" style="max-width: 180px" aria-label="Sort">
                    <option value="">Newest first</option>
                    <option value="clicks" @selected(($filters['sort'] ?? '') === 'clicks')>Most clicked</option>
                </select>
                <button class="btn btn-soft-primary"><i class="bi bi-funnel me-1"></i> Filter</button>
                @if($filters)<a href="{{ route('admin.short-links.index') }}" class="btn btn-soft-secondary">Clear</a>@endif
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Points to</th><th>Short link</th><th class="text-center">Clicks</th><th>Last click</th><th>Created</th></tr></thead>
                <tbody>
                    @forelse($links as $link)
                        @php
                            $t = $link->target;
                            $isBlog = $link->type === 'blog';
                            $name = $t ? ($isBlog ? $t->title : $t->name) : 'Deleted item';
                            $live = $t && ! $t->trashed() && $t->is_active && (! $isBlog || $t->isPublished());
                        @endphp
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <img src="{{ $t ? ($isBlog ? $t->thumb_url : img_url($t->thumbnail)) : img_url(null) }}" class="thumb" alt="" loading="lazy">
                                    <div class="min-w-0">
                                        <div class="title text-truncate cell-title">{{ $name }}</div>
                                        <div class="small">
                                            <span class="badge badge-soft-{{ $link->type === 'buy' ? 'warning' : ($isBlog ? 'purple' : 'info') }}">{{ \App\Models\ShortLink::TYPES[$link->type] }}</span>
                                            @unless($live)<span class="badge badge-soft-secondary" title="The link opens the shop/home page until the item is visible again">Not live</span>@endunless
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="input-group input-group-sm" style="min-width: 260px">
                                    <input type="text" class="form-control" value="{{ $link->url }}" readonly aria-label="Short link" placeholder="Short link" onclick="this.select()">
                                    <button type="button" class="btn btn-primary" data-copy="{{ $link->url }}" data-copy-msg="Short link copied" title="Copy"><i class="bi bi-clipboard"></i></button>
                                </div>
                            </td>
                            <td class="text-center"><span class="badge badge-soft-primary fs-6">{{ number_format($link->clicks) }}</span></td>
                            <td class="small text-nowrap">{{ $link->last_clicked_at?->diffForHumans() ?? '—' }}</td>
                            <td class="small text-nowrap">{{ $link->created_at->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="empty"><i class="bi bi-scissors"></i>No short links yet. Open the Share button on a product or blog post to create one.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($links->hasPages())<div class="card-footer">{{ $links->links() }}</div>@endif
    </div>
@endsection
