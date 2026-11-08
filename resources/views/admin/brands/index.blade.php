@extends('admin.layouts.app')

@section('title', 'Brands')

@section('content')
    <div class="page-header">
        <div>
            <h1>Brands</h1>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li><li class="breadcrumb-item active">Brands</li></ol></nav>
        </div>
        <a href="{{ route('admin.brands.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Add brand</a>
    </div>

    <div class="card">
        <div class="card-header">
            <form class="filter-bar">
                <input type="search" name="q" value="{{ request('q') }}" class="form-control search" placeholder="Search brands…" maxlength="100">
                <button class="btn btn-soft-primary"><i class="bi bi-search"></i></button>
                @if(request('q'))<a href="{{ route('admin.brands.index') }}" class="btn btn-soft-secondary">Clear</a>@endif
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Brand</th><th>Slug</th><th class="text-center">Products</th><th class="text-center">Order</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    @forelse($brands as $brand)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <img src="{{ img_url($brand->logo) }}" class="thumb" style="object-fit: contain" alt="" loading="lazy">
                                    <a href="{{ route('admin.brands.edit', $brand->unique_id) }}" class="title">{{ $brand->name }}</a>
                                </div>
                            </td>
                            <td class="text-muted small">{{ $brand->slug }}</td>
                            <td class="text-center"><a href="{{ route('admin.products.index', ['brand_id' => $brand->id]) }}" class="badge badge-soft-indigo">{{ $brand->products_count }}</a></td>
                            <td class="text-center">{{ $brand->sort_order }}</td>
                            <td>{!! $brand->is_active ? '<span class="badge badge-soft-success">Active</span>' : '<span class="badge badge-soft-secondary">Hidden</span>' !!}</td>
                            <td class="text-end">
                                <div class="row-actions">
                                    <a href="{{ route('brand.show', $brand->slug) }}" target="_blank" class="btn btn-soft-secondary btn-icon" title="View"><i class="bi bi-eye"></i></a>
                                    <a href="{{ route('admin.brands.edit', $brand->unique_id) }}" class="btn btn-soft-primary btn-icon" title="Edit"><i class="bi bi-pencil"></i></a>
                                    @include('admin.partials.delete', ['action' => route('admin.brands.destroy', $brand->unique_id), 'message' => 'Products of this brand will be kept without a brand.'])
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="empty"><i class="bi bi-award"></i>No brands found</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($brands->hasPages())<div class="card-footer">{{ $brands->links() }}</div>@endif
    </div>
@endsection
