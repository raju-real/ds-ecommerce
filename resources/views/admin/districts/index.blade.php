@extends('admin.layouts.app')

@section('title', 'Districts & delivery charges')

@section('content')
    <div class="page-header">
        <div>
            <h1>Districts & delivery charges</h1>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li><li class="breadcrumb-item active">Districts</li></ol></nav>
        </div>
        <a href="{{ route('admin.districts.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Add district</a>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between gap-2 align-items-center">
            <form class="filter-bar">
                <input type="search" name="q" value="{{ request('q') }}" class="form-control search" placeholder="Search district…" maxlength="100">
                <button class="btn btn-soft-primary"><i class="bi bi-search"></i></button>
            </form>
            <span class="small text-muted"><i class="bi bi-info-circle"></i> Edit a charge and press Enter or leave the field to save.</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>District</th><th>বাংলা</th><th class="text-center">Thanas</th><th style="width: 200px">Delivery charge</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    @forelse($districts as $d)
                        <tr>
                            <td class="fw-semibold">{{ $d->name }}</td>
                            <td>{{ $d->bn_name }}</td>
                            <td class="text-center"><a href="{{ route('admin.thanas.index', ['district_id' => $d->id]) }}" class="badge badge-soft-primary">{{ $d->thanas_count }}</a></td>
                            <td>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">৳</span>
                                    <input type="number" min="0" max="9999" step="1" class="form-control" placeholder="0" value="{{ (float) $d->delivery_charge }}" data-charge-url="{{ route('admin.districts.update', $d->unique_id) }}" data-original="{{ (float) $d->delivery_charge }}">
                                </div>
                            </td>
                            <td>{!! $d->is_active ? '<span class="badge badge-soft-success">Active</span>' : '<span class="badge badge-soft-secondary">Disabled</span>' !!}</td>
                            <td class="text-end">
                                <div class="row-actions">
                                    <a href="{{ route('admin.districts.edit', $d->unique_id) }}" class="btn btn-soft-primary btn-icon" title="Edit"><i class="bi bi-pencil"></i></a>
                                    @include('admin.partials.delete', ['action' => route('admin.districts.destroy', $d->unique_id), 'message' => 'All thanas of this district will be deleted too.'])
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="empty"><i class="bi bi-geo-alt"></i>No districts found</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($districts->hasPages())<div class="card-footer">{{ $districts->links() }}</div>@endif
    </div>
@endsection

@push('page-scripts')
    <script>
        $(document).on('change', '[data-charge-url]', function () {
            const $i = $(this);
            if ($i.val() === String($i.data('original'))) return;
            $.post($i.data('charge-url'), { _method: 'PUT', quick: 1, delivery_charge: $i.val() })
                .done(() => { $i.data('original', $i.val()); notify('Delivery charge saved'); })
                .fail(() => notify('Could not save', 'error'));
        }).on('keydown', '[data-charge-url]', function (e) { if (e.key === 'Enter') { e.preventDefault(); $(this).trigger('change'); } });
    </script>
@endpush
