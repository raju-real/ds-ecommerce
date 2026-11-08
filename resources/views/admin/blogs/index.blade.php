@extends('admin.layouts.app')

@section('title', 'Blog')

@section('content')
    <div class="page-header">
        <div>
            <h1>Blog</h1>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li><li class="breadcrumb-item active">Blog</li></ol></nav>
        </div>
        <a href="{{ route('admin.blogs.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Write a post</a>
    </div>

    <div class="card">
        <div class="card-header">
            <form class="filter-bar">
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control search" placeholder="Search by title…" maxlength="100">
                <select name="status" class="form-select" style="max-width: 180px" aria-label="Status">
                    <option value="">Any status</option>
                    @foreach(['published' => 'Published', 'scheduled' => 'Scheduled', 'hidden' => 'Hidden'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="btn btn-soft-primary"><i class="bi bi-funnel me-1"></i> Filter</button>
                @if($filters)<a href="{{ route('admin.blogs.index') }}" class="btn btn-soft-secondary">Clear</a>@endif
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Post</th><th>Published</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    @forelse($blogs as $blog)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <img src="{{ $blog->thumb_url }}" class="thumb" style="width: 84px; object-fit: cover" alt="" loading="lazy">
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.blogs.edit', $blog->unique_id) }}" class="title d-block text-truncate cell-title" style="max-width: 420px">{{ $blog->title }}</a>
                                        <div class="small text-muted text-truncate" style="max-width: 420px">{{ $blog->excerpt }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="small text-nowrap">{{ $blog->published_at?->format('d M Y') ?? '—' }}</td>
                            <td>
                                @if(! $blog->is_active)
                                    <span class="badge badge-soft-secondary">Hidden</span>
                                @elseif($blog->isPublished())
                                    <span class="badge badge-soft-success">Published</span>
                                @else
                                    <span class="badge badge-soft-info" title="Goes live on {{ $blog->published_at?->format('d M Y') }}">Scheduled</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="row-actions">
                                    @if($blog->isPublished())@include('admin.partials.share-menu', array_filter(['title' => $blog->title, 'image' => $blog->thumb_url, 'targets' => [['type' => 'blog', 'uid' => $blog->unique_id, 'label' => 'Blog post', 'url' => $blog->url]], 'primary' => 'blog', 'shareText' => $blog->title.' — read more:', 'label' => null]))@endif
                                    @if($blog->isPublished())<a href="{{ $blog->url }}" target="_blank" class="btn btn-soft-secondary btn-icon" title="View on site"><i class="bi bi-box-arrow-up-right"></i></a>@endif
                                    <a href="{{ route('admin.blogs.edit', $blog->unique_id) }}" class="btn btn-soft-primary btn-icon" title="Edit"><i class="bi bi-pencil"></i></a>
                                    @include('admin.partials.delete', ['action' => route('admin.blogs.destroy', $blog->unique_id), 'message' => 'The post will be removed from the site. Links to it inside other posts are hidden automatically.'])
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><div class="empty"><i class="bi bi-journal-text"></i>No blog posts yet. The blog section appears on the site after the first post is published.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($blogs->hasPages())<div class="card-footer">{{ $blogs->links() }}</div>@endif
    </div>
@endsection
