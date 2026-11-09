<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Repositories\BrandRepository;
use App\Services\ImageService;
use App\Support\Seo;
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
        return view('admin.brands.form', ['brand' => new Brand(['is_active' => true, 'sort_order' => Brand::nextSortOrder()])]);
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
        $data = $this->validated($request, $model);
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

    protected function validated(Request $request, ?Brand $model = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|min:2|max:120',
            'slug' => 'nullable|alpha_dash|max:150',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'logo' => upload_rules('brand'),
            ...Seo::rules(),
        ], Seo::messages());
        unset($data['logo']);
        $data['sort_order'] ??= $model?->sort_order ?? Brand::nextSortOrder(); // empty: keep it / add at the end
        $data['is_active'] = $request->boolean('is_active');

        return Seo::apply($request, $data, $model, 'brands');
    }
}
