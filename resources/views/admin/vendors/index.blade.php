@extends('admin.layouts.app')

@section('title', 'Vendors')

@section('content')
    <div class="page-header">
        <div>
            <h1>Vendors</h1>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li><li class="breadcrumb-item active">Vendors</li></ol></nav>
        </div>
        <a href="{{ route('admin.vendors.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Add vendor</a>
    </div>

    <div class="card">
        <div class="card-header">
            <form class="filter-bar">
                <input type="search" name="q" value="{{ request('q') }}" class="form-control search" placeholder="Search name, phone, contact…" maxlength="100">
                <button class="btn btn-soft-primary"><i class="bi bi-search"></i></button>
                @if(request('q'))<a href="{{ route('admin.vendors.index') }}" class="btn btn-soft-secondary">Clear</a>@endif
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Vendor</th><th>Contact</th><th>Address</th><th class="text-center">Products</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    @forelse($vendors as $vendor)
                        <tr>
                            <td>
                                <a href="{{ route('admin.vendors.edit', $vendor->unique_id) }}" class="title">{{ $vendor->name }}</a>
                                @if($vendor->website)<div class="small"><a href="{{ $vendor->website }}" target="_blank" rel="noopener" class="text-muted">{{ parse_url($vendor->website, PHP_URL_HOST) }} <i class="bi bi-box-arrow-up-right"></i></a></div>@endif
                            </td>
                            <td>
                                {{ $vendor->contact_person ?: '—' }}
                                <div class="small text-muted">
                                    @if($vendor->phone)<a href="tel:{{ $vendor->phone }}" class="text-muted"><i class="bi bi-telephone"></i> {{ $vendor->phone }}</a>@endif
                                    @if($vendor->email) · {{ $vendor->email }}@endif
                                </div>
                            </td>
                            <td class="small text-muted" style="max-width: 240px">{{ $vendor->address }}</td>
                            <td class="text-center"><a href="{{ route('admin.products.index', ['vendor_id' => $vendor->id]) }}" class="badge badge-soft-indigo">{{ $vendor->products_count }}</a></td>
                            <td>{!! $vendor->is_active ? '<span class="badge badge-soft-success">Active</span>' : '<span class="badge badge-soft-secondary">Inactive</span>' !!}</td>
                            <td class="text-end">
                                <div class="row-actions">
                                    <a href="{{ route('admin.vendors.edit', $vendor->unique_id) }}" class="btn btn-soft-primary btn-icon" title="Edit"><i class="bi bi-pencil"></i></a>
                                    @include('admin.partials.delete', ['action' => route('admin.vendors.destroy', $vendor->unique_id), 'message' => 'Products from this vendor will become unassigned.'])
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="empty"><i class="bi bi-shop"></i>No vendors found</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($vendors->hasPages())<div class="card-footer">{{ $vendors->links() }}</div>@endif
    </div>
@endsection
