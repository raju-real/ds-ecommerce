<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Repositories\CategoryRepository;
use App\Services\ImageService;
use App\Support\ModelCache;
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    public function __construct(protected CategoryRepository $categories, protected ImageService $images)
    {
    }

    /** Drag & drop order for all categories and their subcategories (one page, no pagination). */
    public function sort()
    {
        return view('admin.categories.sort', ['categories' => $this->categories->all()]);
    }

    /** {categories: [uid, …], subcategories: {categoryUid: [uid, …]}} → sort_order 0, 1, 2 … */
    public function saveSort(Request $request)
    {
        $data = $request->validate([
            'categories' => 'required|array|max:1000',
            'categories.*' => ['string', 'regex:/^[a-z]{10}$/'],
            'subcategories' => 'nullable|array|max:1000',
            'subcategories.*' => 'array|max:1000',
            'subcategories.*.*' => ['string', 'regex:/^[a-z]{10}$/'],
        ]);

        DB::transaction(function () use ($data) {
            foreach (array_values($data['categories']) as $i => $uid) {
                DB::table('categories')->where('unique_id', $uid)->update(['sort_order' => $i, 'updated_at' => now()]);
            }
            foreach ($data['subcategories'] ?? [] as $categoryUid => $uids) {
                $categoryId = DB::table('categories')->where('unique_id', $categoryUid)->value('id');
                foreach (array_values($uids) as $i => $uid) {
                    // Only subcategories that belong to that category are moved.
                    DB::table('subcategories')->where('unique_id', $uid)->where('category_id', $categoryId)->update(['sort_order' => $i, 'updated_at' => now()]);
                }
            }
        });
        // One cache refresh for the whole new order (menu, lists, product forms).
        ModelCache::bump(ModelCache::CATEGORIES, ModelCache::PRODUCTS);

        return response()->json(['ok' => true, 'message' => 'Order saved.']);
    }

    public function index(Request $request)
    {
        return view('admin.categories.index', [
            'categories' => $this->categories->adminPaginate($request->only('q'))->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('admin.categories.form', ['category' => new Category(['is_active' => true, 'sort_order' => Category::nextSortOrder()])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = Category::uniqueSlug(($data['slug'] ?? null) ?: $data['name']);
        if ($request->hasFile('image')) {
            $data['image'] = $this->images->store($request->file('image'), 'categories', config('shop.images.category'));
        }

        Category::create($data);

        return redirect()->route('admin.categories.index')->with('success', 'Category created.');
    }

    public function edit(string $category)
    {
        return view('admin.categories.form', ['category' => $this->categories->find($category) ?? abort(404)]);
    }

    public function update(Request $request, string $category)
    {
        $model = Category::findByUidOrFail($category);
        $data = $this->validated($request, $model);
        $data['slug'] = Category::uniqueSlug(($data['slug'] ?? null) ?: $data['name'], $model->id);

        if ($request->hasFile('image')) {
            $this->images->delete($model->image);
            $data['image'] = $this->images->store($request->file('image'), 'categories', config('shop.images.category'));
        }

        $model->update($data);

        return redirect()->route('admin.categories.index')->with('success', 'Category updated.');
    }

    public function destroy(string $category)
    {
        $model = Category::withCount('products')->where('unique_id', $category)->firstOrFail();

        if ($model->products_count > 0) {
            return back()->with('error', "Cannot delete: {$model->products_count} product(s) use this category.");
        }

        // Soft delete the category with its subcategories; the image file is kept.
        DB::transaction(function () use ($model) {
            $model->subcategories()->get()->each->delete();
            $model->delete();
        });

        return back()->with('success', 'Category deleted.');
    }

    protected function validated(Request $request, ?Category $model = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|min:2|max:120',
            'slug' => 'nullable|alpha_dash|max:150',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'image' => upload_rules('category'),
            ...Seo::rules(),
        ], Seo::messages());
        unset($data['image']);
        $data['sort_order'] ??= $model?->sort_order ?? Category::nextSortOrder(); // empty: keep it / add at the end
        $data['is_active'] = $request->boolean('is_active');

        return Seo::apply($request, $data, $model, 'categories');
    }
}
