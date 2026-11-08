@extends('admin.layouts.app')

@section('title', 'Subcategories')

@section('content')
    <div class="page-header">
        <div>
            <h1>Subcategories</h1>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li><li class="breadcrumb-item active">Subcategories</li></ol></nav>
        </div>
        <a href="{{ route('admin.subcategories.create', ['category_id' => request('category_id')]) }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Add subcategory</a>
    </div>

    <div class="card">
        <div class="card-header">
            <form class="filter-bar">
                <input type="search" name="q" value="{{ request('q') }}" class="form-control search" placeholder="Search subcategories…" maxlength="100">
                <div style="min-width: 220px">
                    <select name="category_id" class="form-select select2" data-placeholder="All categories" onchange="this.form.submit()">
                        <option></option>
                        @foreach($categories as $c)<option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>{{ $c->name }}</option>@endforeach
                    </select>
                </div>
                <button class="btn btn-soft-primary"><i class="bi bi-search"></i></button>
                @if(request()->hasAny(['q', 'category_id']))<a href="{{ route('admin.subcategories.index') }}" class="btn btn-soft-secondary">Clear</a>@endif
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Subcategory</th><th>Category</th><th>Slug</th><th class="text-center">Products</th><th class="text-center">Order</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    @forelse($subcategories as $sub)
                        <tr>
                            <td><a href="{{ route('admin.subcategories.edit', $sub->unique_id) }}" class="title">{{ $sub->name }}</a></td>
                            <td><span class="badge badge-soft-primary">{{ optional($sub->category)->name }}</span></td>
                            <td class="text-muted small">{{ $sub->slug }}</td>
                            <td class="text-center">{{ $sub->products_count }}</td>
                            <td class="text-center">{{ $sub->sort_order }}</td>
                            <td>{!! $sub->is_active ? '<span class="badge badge-soft-success">Active</span>' : '<span class="badge badge-soft-secondary">Hidden</span>' !!}</td>
                            <td class="text-end">
                                <div class="row-actions">
                                    <a href="{{ route('admin.subcategories.edit', $sub->unique_id) }}" class="btn btn-soft-primary btn-icon" title="Edit"><i class="bi bi-pencil"></i></a>
                                    @include('admin.partials.delete', ['action' => route('admin.subcategories.destroy', $sub->unique_id)])
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="empty"><i class="bi bi-diagram-3"></i>No subcategories found</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($subcategories->hasPages())<div class="card-footer">{{ $subcategories->links() }}</div>@endif
    </div>
@endsection
