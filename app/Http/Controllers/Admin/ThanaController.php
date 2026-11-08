<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Thana;
use App\Repositories\LocationRepository;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ThanaController extends Controller
{
    public function __construct(protected LocationRepository $locations)
    {
    }

    public function index(Request $request)
    {
        return view('admin.thanas.index', [
            'thanas' => $this->locations->adminThanas($request->only('q', 'district_id'))->withQueryString(),
            'districts' => $this->locations->allDistricts(),
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.thanas.form', [
            'thana' => new Thana(['is_active' => true, 'district_id' => $request->integer('district_id') ?: null]),
            'districts' => $this->locations->allDistricts(),
        ]);
    }

    public function store(Request $request)
    {
        Thana::create($this->validated($request));

        return redirect()->route('admin.thanas.index', ['district_id' => $request->input('district_id')])->with('success', 'Thana created.');
    }

    public function edit(string $thana)
    {
        return view('admin.thanas.form', [
            'thana' => $this->locations->findThana($thana) ?? abort(404),
            'districts' => $this->locations->allDistricts(),
        ]);
    }

    public function update(Request $request, string $thana)
    {
        Thana::findByUidOrFail($thana)->update($this->validated($request));

        return redirect()->route('admin.thanas.index', ['district_id' => $request->input('district_id')])->with('success', 'Thana updated.');
    }

    public function destroy(string $thana)
    {
        Thana::findByUidOrFail($thana)->delete();

        return back()->with('success', 'Thana deleted.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'district_id' => ['required', Rule::exists('districts', 'id')->whereNull('deleted_at')],
            'name' => 'required|string|min:2|max:100',
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
