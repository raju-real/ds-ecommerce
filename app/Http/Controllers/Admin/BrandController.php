<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Repositories\BrandRepository;
use App\Services\ImageService;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function __construct(protected BrandRepository $brands, protected ImageService $images)
    {
    }

    public function index(Request $request)
    {
        return view('admin.brands.index', [
            'brands' => $this->brands->adminPaginate($request->only('q'))->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('admin.brands.form', ['brand' => new Brand(['is_active' => true])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = Brand::uniqueSlug(($data['slug'] ?? null) ?: $data['name']);
        if ($request->hasFile('logo')) {
            $data['logo'] = $this->images->store($request->file('logo'), 'brands', config('shop.images.category'), 'max');
        }

        Brand::create($data);

        return redirect()->route('admin.brands.index')->with('success', 'Brand created.');
    }

    public function edit(string $brand)
    {
        return view('admin.brands.form', ['brand' => $this->brands->find($brand) ?? abort(404)]);
    }

    public function update(Request $request, string $brand)
    {
        $model = Brand::findByUidOrFail($brand);
        $data = $this->validated($request);
        $data['slug'] = Brand::uniqueSlug(($data['slug'] ?? null) ?: $data['name'], $model->id);

        if ($request->hasFile('logo')) {
            $this->images->delete($model->logo);
            $data['logo'] = $this->images->store($request->file('logo'), 'brands', config('shop.images.category'), 'max');
        }

        $model->update($data);

        return redirect()->route('admin.brands.index')->with('success', 'Brand updated.');
    }

    public function destroy(string $brand)
    {
        // Soft delete: the logo file is kept so the brand can be restored.
        Brand::findByUidOrFail($brand)->delete();

        return back()->with('success', 'Brand deleted. Its products are now without a brand.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|min:2|max:120',
            'slug' => 'nullable|alpha_dash|max:150',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'logo' => upload_rules('brand'),
            'meta_title' => 'nullable|string|max:70',
            'meta_description' => 'nullable|string|max:160',
        ]);
        unset($data['logo']);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
