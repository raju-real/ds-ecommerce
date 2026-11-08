@extends('admin.layouts.app')

@section('title', 'Categories')

@section('content')
    <div class="page-header">
        <div>
            <h1>Categories</h1>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li><li class="breadcrumb-item active">Categories</li></ol></nav>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.categories.sort') }}" class="btn btn-soft-primary"><i class="bi bi-arrows-move me-1"></i> Sort order</a>
            <a href="{{ route('admin.categories.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Add category</a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <form class="filter-bar">
                <input type="search" name="q" value="{{ request('q') }}" class="form-control search" placeholder="Search categories…" maxlength="100">
                <button class="btn btn-soft-primary"><i class="bi bi-search"></i></button>
                @if(request('q'))<a href="{{ route('admin.categories.index') }}" class="btn btn-soft-secondary">Clear</a>@endif
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Category</th><th>Slug</th><th class="text-center">Subcategories</th><th class="text-center">Products</th><th class="text-center">Order</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    @forelse($categories as $cat)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <img src="{{ img_url($cat->image) }}" class="thumb" alt="" loading="lazy">
                                    <a href="{{ route('admin.categories.edit', $cat->unique_id) }}" class="title">{{ $cat->name }}</a>
                                </div>
                            </td>
                            <td class="text-muted small">{{ $cat->slug }}</td>
                            <td class="text-center"><a href="{{ route('admin.subcategories.index', ['category_id' => $cat->id]) }}" class="badge badge-soft-primary">{{ $cat->subcategories_count }}</a></td>
                            <td class="text-center"><a href="{{ route('admin.products.index', ['category_id' => $cat->id]) }}" class="badge badge-soft-indigo">{{ $cat->products_count }}</a></td>
                            <td class="text-center">{{ $cat->sort_order }}</td>
                            <td>{!! $cat->is_active ? '<span class="badge badge-soft-success">Active</span>' : '<span class="badge badge-soft-secondary">Hidden</span>' !!}</td>
                            <td class="text-end">
                                <div class="row-actions">
                                    <a href="{{ route('category.show', $cat->slug) }}" target="_blank" class="btn btn-soft-secondary btn-icon" title="View"><i class="bi bi-eye"></i></a>
                                    <a href="{{ route('admin.categories.edit', $cat->unique_id) }}" class="btn btn-soft-primary btn-icon" title="Edit"><i class="bi bi-pencil"></i></a>
                                    @include('admin.partials.delete', ['action' => route('admin.categories.destroy', $cat->unique_id), 'message' => 'Subcategories of this category will also be deleted.'])
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="empty"><i class="bi bi-collection"></i>No categories found</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($categories->hasPages())<div class="card-footer">{{ $categories->links() }}</div>@endif
    </div>
@endsection
