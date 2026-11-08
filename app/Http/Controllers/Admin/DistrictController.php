<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Repositories\LocationRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DistrictController extends Controller
{
    public function __construct(protected LocationRepository $locations)
    {
    }

    public function index(Request $request)
    {
        return view('admin.districts.index', [
            'districts' => $this->locations->adminDistricts($request->only('q'))->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('admin.districts.form', ['district' => new District(['is_active' => true, 'delivery_charge' => 130])]);
    }

    public function store(Request $request)
    {
        District::create($this->validated($request));

        return redirect()->route('admin.districts.index')->with('success', 'District created.');
    }

    public function edit(string $district)
    {
        return view('admin.districts.form', ['district' => $this->locations->allDistricts()->firstWhere('unique_id', $district) ?? abort(404)]);
    }

    public function update(Request $request, string $district)
    {
        $data = $this->validated($request);

        // Inline charge edit from the list sends only the charge.
        if ($request->boolean('quick')) {
            District::findByUidOrFail($district)->update(['delivery_charge' => $data['delivery_charge']]);

            return response()->json(['ok' => true]);
        }

        District::findByUidOrFail($district)->update($data);

        return redirect()->route('admin.districts.index')->with('success', 'District updated.');
    }

    public function destroy(string $district)
    {
        // Soft delete the district with its thanas (orders keep showing them).
        $model = District::findByUidOrFail($district);
        DB::transaction(function () use ($model) {
            $model->thanas()->get()->each->delete();
            $model->delete();
        });

        return back()->with('success', 'District deleted.');
    }

    protected function validated(Request $request): array
    {
        if ($request->boolean('quick')) {
            return $request->validate(['delivery_charge' => 'required|numeric|min:0|max:9999']);
        }

        $data = $request->validate([
            'name' => 'required|string|min:2|max:100',
            'bn_name' => 'nullable|string|max:100',
            'delivery_charge' => 'required|numeric|min:0|max:9999',
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
