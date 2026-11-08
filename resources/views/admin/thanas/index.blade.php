@extends('admin.layouts.app')

@section('title', 'Thanas')

@section('content')
    <div class="page-header">
        <div>
            <h1>Thanas / Upazilas</h1>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.districts.index') }}">Districts</a></li><li class="breadcrumb-item active">Thanas</li></ol></nav>
        </div>
        <a href="{{ route('admin.thanas.create', ['district_id' => request('district_id')]) }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Add thana</a>
    </div>

    <div class="card">
        <div class="card-header">
            <form class="filter-bar">
                <input type="search" name="q" value="{{ request('q') }}" class="form-control search" placeholder="Search thana…" maxlength="100">
                <div style="min-width: 220px">
                    <select name="district_id" class="form-select select2" data-placeholder="All districts" onchange="this.form.submit()">
                        <option></option>
                        @foreach($districts as $d)<option value="{{ $d->id }}" @selected(request('district_id') == $d->id)>{{ $d->name }}</option>@endforeach
                    </select>
                </div>
                <button class="btn btn-soft-primary"><i class="bi bi-search"></i></button>
                @if(request()->hasAny(['q', 'district_id']))<a href="{{ route('admin.thanas.index') }}" class="btn btn-soft-secondary">Clear</a>@endif
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Thana / Upazila</th><th>District</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    @forelse($thanas as $t)
                        <tr>
                            <td class="fw-semibold">{{ $t->name }}</td>
                            <td>{{ optional($t->district)->name }}</td>
                            <td>{!! $t->is_active ? '<span class="badge badge-soft-success">Active</span>' : '<span class="badge badge-soft-secondary">Disabled</span>' !!}</td>
                            <td class="text-end">
                                <div class="row-actions">
                                    <a href="{{ route('admin.thanas.edit', $t->unique_id) }}" class="btn btn-soft-primary btn-icon" title="Edit"><i class="bi bi-pencil"></i></a>
                                    @include('admin.partials.delete', ['action' => route('admin.thanas.destroy', $t->unique_id)])
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><div class="empty"><i class="bi bi-pin-map"></i>No thanas found</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($thanas->hasPages())<div class="card-footer">{{ $thanas->links() }}</div>@endif
    </div>
@endsection
