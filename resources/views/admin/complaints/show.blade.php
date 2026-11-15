@extends('admin.layouts.app')

@section('title', 'Complaint #'.$complaint->complaint_no)

@section('content')
    <div class="page-header">
        <div>
            <h1 class="d-flex align-items-center gap-2 flex-wrap">Complaint #{{ $complaint->complaint_no }} <span class="badge badge-soft-{{ $complaint->status_color }} fs-6">{{ $complaint->status_label }}</span></h1>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.complaints.index') }}">Complaints</a></li><li class="breadcrumb-item active">Received {{ $complaint->created_at->format('d M Y, h:i A') }}</li></ol></nav>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.complaints.index') }}" class="btn btn-soft-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
            @include('admin.partials.delete', ['action' => route('admin.complaints.destroy', $complaint->unique_id), 'message' => 'This complaint will be removed from the list.'])
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header"><h5><i class="bi bi-chat-left-text me-1"></i> Complaint</h5></div>
                <div class="card-body">
                    <p class="mb-0" style="white-space: pre-line">{{ $complaint->message }}</p>
                    @if($complaint->images)
                        <div class="d-flex flex-wrap gap-2 mt-3">
                            @foreach($complaint->images as $img)
                                <a href="{{ img_url($img) }}" target="_blank" rel="noopener"><img src="{{ img_url($img) }}" alt="Complaint photo" style="width: 180px; height: 180px; object-fit: cover" class="rounded-3 border"></a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header"><h5>Customer</h5></div>
                <div class="card-body">
                    <div class="fw-bold">{{ $complaint->name }}</div>
                    <div class="text-muted small mb-3">{{ $complaint->mobile }}</div>
                    <div class="d-flex gap-2">
                        <a href="tel:{{ $complaint->mobile }}" class="btn btn-soft-primary btn-sm flex-fill"><i class="bi bi-telephone me-1"></i>Call</a>
                        <a href="https://wa.me/{{ \App\Support\WhatsappInvoice::number($complaint->mobile) }}?text={{ rawurlencode('About your complaint #'.$complaint->complaint_no.': ') }}" target="_blank" rel="noopener" class="btn btn-soft-success btn-sm flex-fill"><i class="bi bi-whatsapp me-1"></i>WhatsApp</a>
                        <a href="{{ route('admin.orders.index', ['q' => $complaint->mobile]) }}" class="btn btn-soft-secondary btn-sm flex-fill" title="Orders with this mobile"><i class="bi bi-receipt me-1"></i>Orders</a>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h5>Status</h5></div>
                <form method="post" action="{{ route('admin.complaints.update', $complaint->unique_id) }}" class="card-body">
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <select name="status" id="cmpStatus" class="form-select">
                            @foreach(\App\Models\Complaint::STATUSES as $key => $st)<option value="{{ $key }}" @selected($complaint->status === $key)>{{ $st['label'] }}</option>@endforeach
                        </select>
                        @if($complaint->status_changed_at)<div class="form-text">Last changed {{ $complaint->status_changed_at->diffForHumans() }}</div>@endif
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="cmpNote">Admin note</label>
                        <textarea name="admin_note" id="cmpNote" rows="3" class="form-control" maxlength="2000" placeholder="What was done (only visible to admins)">{{ old('admin_note', $complaint->admin_note) }}</textarea>
                    </div>
                    <button class="btn btn-primary w-100"><i class="bi bi-check2 me-1"></i> Save</button>
                </form>
            </div>
        </div>
    </div>
@endsection
