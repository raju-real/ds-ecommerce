<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Repositories\VendorRepository;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function __construct(protected VendorRepository $vendors)
    {
    }

    public function index(Request $request)
    {
        return view('admin.vendors.index', [
            'vendors' => $this->vendors->adminPaginate($request->only('q'))->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('admin.vendors.form', ['vendor' => new Vendor(['is_active' => true])]);
    }

    public function store(Request $request)
    {
        Vendor::create($this->validated($request));

        return redirect()->route('admin.vendors.index')->with('success', 'Vendor created.');
    }

    public function edit(string $vendor)
    {
        return view('admin.vendors.form', ['vendor' => $this->vendors->find($vendor) ?? abort(404)]);
    }

    public function update(Request $request, string $vendor)
    {
        Vendor::findByUidOrFail($vendor)->update($this->validated($request));

        return redirect()->route('admin.vendors.index')->with('success', 'Vendor updated.');
    }

    public function destroy(string $vendor)
    {
        Vendor::findByUidOrFail($vendor)->delete();

        return back()->with('success', 'Vendor deleted. Its products are now unassigned.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|min:2|max:150',
            'contact_person' => 'nullable|string|max:120',
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9 \-]{6,20}$/'],
            'email' => 'nullable|email|max:150',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string|max:500',
            'note' => 'nullable|string|max:2000',
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
