<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Repositories\BrandRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductRepository;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        protected ProductRepository $products,
        protected CategoryRepository $categories,
        protected BrandRepository $brands,
    ) {
    }

    public function index(Request $request)
    {
        return $this->listing($request);
    }

    public function category(Request $request, string $slug)
    {
        $category = $this->categories->findBySlug($slug) ?? abort(404);

        return $this->listing($request, $category);
    }

    public function subcategory(Request $request, string $slug, string $sub)
    {
        $category = $this->categories->findBySlug($slug) ?? abort(404);
        $subcategory = $category->subcategories->firstWhere('slug', $sub) ?? abort(404);

        return $this->listing($request, $category, $subcategory);
    }

    public function brand(Request $request, string $slug)
    {
        $brand = $this->brands->findBySlug($slug) ?? abort(404);

        return $this->listing($request, null, null, $brand);
    }

    public function show(string $slug)
    {
        $product = $this->products->findBySlug($slug) ?? abort(404);

        return view('frontend.products.show', [
            'product' => $product,
            'related' => $this->products->related($product),
        ]);
    }

    public function quickView(string $slug)
    {
        $product = $this->products->findBySlug($slug) ?? abort(404);

        return view('frontend.products.quick-view', compact('product'));
    }

    protected function listing(Request $request, $category = null, $subcategory = null, $brand = null)
    {
        // Public listing: invalid filter values are dropped instead of erroring.
        $min = $request->query('min');
        $max = $request->query('max');
        $sort = $request->query('sort');

        $filters = [
            'q' => mb_substr(trim((string) $request->query('q', '')), 0, config('shop.search.max_chars')) ?: null,
            'min' => is_numeric($min) && $min >= 0 ? (int) $min : null,
            'max' => is_numeric($max) && $max >= 0 ? (int) $max : null,
            'sort' => is_string($sort) && array_key_exists($sort, ProductRepository::SORTS) ? $sort : null,
            'stock' => $request->query('stock') === 'in' ? 'in' : null,
            'featured' => $request->query('featured') === '1' ? 1 : null,
            'category_id' => $category?->id,
            'subcategory_id' => $subcategory?->id,
            'brand_id' => $brand?->id,
        ];

        $products = $this->products->search($filters)->withQueryString();

        if ($products->currentPage() > 1 && $products->isEmpty()) {
            abort(404);
        }

        return view('frontend.products.index', [
            'products' => $products,
            'category' => $category,
            'subcategory' => $subcategory,
            'brand' => $brand,
            'brands' => $this->brands->active(),
            'filters' => $filters,
            'sorts' => ProductRepository::SORTS,
            'bounds' => $this->products->priceBounds(),
        ]);
    }
}
