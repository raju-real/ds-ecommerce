<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subcategory;
use App\Repositories\CategoryRepository;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubcategoryController extends Controller
{
    public function __construct(protected CategoryRepository $categories)
    {
    }

    public function index(Request $request)
    {
        return view('admin.subcategories.index', [
            'subcategories' => $this->categories->subcategoriesPaginate($request->only('q', 'category_id'))->withQueryString(),
            'categories' => $this->categories->all(),
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.subcategories.form', [
            'subcategory' => new Subcategory(['is_active' => true, 'category_id' => $cat = ($request->integer('category_id') ?: null),
                'sort_order' => $cat ? Subcategory::nextSortOrder(['category_id' => $cat]) : 1]),
            'categories' => $this->categories->all(),
            // Next free position per category: the form fills it in when the parent category is picked.
            'nextSort' => Subcategory::query()->selectRaw('category_id, MAX(sort_order) + 1 as next')->groupBy('category_id')->pluck('next', 'category_id'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = Subcategory::uniqueSlug(($data['slug'] ?? null) ?: $data['name']);
        Subcategory::create($data);

        return redirect()->route('admin.subcategories.index')->with('success', 'Subcategory created.');
    }

    public function edit(string $subcategory)
    {
        return view('admin.subcategories.form', [
            'subcategory' => $this->categories->findSubcategory($subcategory) ?? abort(404),
            'categories' => $this->categories->all(),
        ]);
    }

    public function update(Request $request, string $subcategory)
    {
        $model = Subcategory::findByUidOrFail($subcategory);
        $data = $this->validated($request);
        $data['slug'] = Subcategory::uniqueSlug(($data['slug'] ?? null) ?: $data['name'], $model->id);
        $model->update($data);

        return redirect()->route('admin.subcategories.index')->with('success', 'Subcategory updated.');
    }

    public function destroy(string $subcategory)
    {
        Subcategory::findByUidOrFail($subcategory)->delete();

        return back()->with('success', 'Subcategory deleted. Its products stay in the parent category.');
    }

    /** JSON for the dependent dropdown on the product form (served from cache). */
    public function byCategory(string $category)
    {
        $cat = $this->categories->find($category);

        return response()->json($cat ? $cat->subcategories->map(fn ($s) => ['id' => $s->id, 'text' => $s->name])->values() : []);
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'category_id' => ['required', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'name' => 'required|string|min:2|max:120',
            'slug' => 'nullable|alpha_dash|max:150',
            'sort_order' => 'nullable|integer|min:0|max:9999',
        ]);
        // Empty: keep the current place, or add at the end of the chosen category.
        $data['sort_order'] ??= ($uid = $request->route('subcategory')) ? Subcategory::where('unique_id', $uid)->value('sort_order')
            : Subcategory::nextSortOrder(['category_id' => $data['category_id']]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
