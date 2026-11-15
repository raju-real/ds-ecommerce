<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Customers → Complaints: list, open one, move it through received → on review → solved / rejected, add a note. */
class ComplaintController extends Controller
{
    public function index(Request $request)
    {
        $status = array_key_exists((string) $request->query('status'), Complaint::STATUSES) ? $request->query('status') : null;
        $q = is_string($request->query('q')) ? mb_substr(trim($request->query('q')), 0, 100) : null;

        $complaints = Complaint::query()
            ->when($status, fn ($w) => $w->where('status', $status))
            ->when($q, fn ($w) => $w->where(fn ($x) => $x->where('complaint_no', 'like', '%'.like_escape(ltrim($q, '#')).'%')
                ->orWhere('name', 'like', '%'.like_escape($q).'%')->orWhere('mobile', 'like', '%'.like_escape($q).'%')))
            ->latest('id')->paginate(20)->withQueryString();

        return view('admin.complaints.index', [
            'complaints' => $complaints,
            'counts' => Complaint::query()->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status')->all(),
            'filters' => array_filter(['status' => $status, 'q' => $q]),
        ]);
    }

    public function show(string $complaint)
    {
        return view('admin.complaints.show', ['complaint' => Complaint::findByUidOrFail($complaint)]);
    }

    public function update(Request $request, string $complaint)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Complaint::STATUSES))],
            'admin_note' => 'nullable|string|max:2000',
        ]);
        $model = Complaint::findByUidOrFail($complaint);
        if ($model->status !== $data['status']) {
            $data['status_changed_at'] = now();
        }
        $model->update($data);

        return back()->with('success', "Complaint #{$model->complaint_no} updated.");
    }

    public function destroy(string $complaint)
    {
        Complaint::findByUidOrFail($complaint)->delete();

        return redirect()->route('admin.complaints.index')->with('success', 'Complaint deleted.');
    }
}
