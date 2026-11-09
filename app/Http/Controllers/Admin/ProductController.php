<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductImage;
use App\Repositories\BrandRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductRepository;
use App\Repositories\VendorRepository;
use App\Services\ImageService;
use App\Support\RichText;
use App\Support\Seo;
use App\Support\UniqueId;
use App\Support\ModelCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function __construct(
        protected ProductRepository $products,
        protected CategoryRepository $categories,
        protected VendorRepository $vendors,
        protected BrandRepository $brands,
        protected ImageService $images,
    ) {
    }

    public function index(Request $request)
    {
        $filters = $request->only('q', 'category_id', 'vendor_id', 'brand_id', 'status');

        return view('admin.products.index', [
            'products' => $this->products->adminPaginate($filters)->withQueryString(),
            'categories' => $this->categories->all(),
            'vendors' => $this->vendors->all(),
            'brands' => $this->brands->all(),
            'stats' => $this->products->stats(),
        ]);
    }

    public function create()
    {
        return $this->form(new Product(['is_active' => true, 'in_stock' => true]));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request) + $this->storeThumbnail($request);
        $data['slug'] = Product::uniqueSlug(($data['slug'] ?? null) ?: $data['name']);

        $product = DB::transaction(function () use ($data, $request) {
            $product = Product::create($data);
            $this->storeImages($product, $request);

            return $product;
        });

        return redirect()->route($request->input('after') === 'new' ? 'admin.products.create' : 'admin.products.index')
            ->with('success', "Product “{$product->name}” created.");
    }

    public function show(string $product)
    {
        $product = $this->products->adminFind($product) ?? abort(404);

        $sales = ModelCache::remember([ModelCache::ORDERS], "product-sales:{$product->id}", fn () => OrderItem::query()
            ->where('product_id', $product->id)
            ->whereHas('order', fn ($q) => $q->whereNotIn('status', ['cancelled', 'returned']))
            ->selectRaw('COUNT(DISTINCT order_id) as orders, COALESCE(SUM(quantity),0) as qty, COALESCE(SUM(line_total),0) as revenue, COALESCE(SUM(line_total - line_cost),0) as profit')
            ->first()->toArray());

        return view('admin.products.show', compact('product', 'sales'));
    }

    public function edit(string $product)
    {
        return $this->form($this->products->adminFind($product) ?? abort(404));
    }

    public function update(Request $request, string $product)
    {
        $model = Product::findByUidOrFail($product);
        $data = $this->validated($request, $model);
        $data['slug'] = Product::uniqueSlug(($data['slug'] ?? null) ?: $data['name'], $model->id);
        if ($thumb = $this->storeThumbnail($request)) {
            $this->images->delete($model->thumbnail, $model->thumbnail_large);
            $data += $thumb;
        }

        DB::transaction(function () use ($model, $data, $request) {
            $model->update($data);
            $this->storeImages($model, $request);
        });

        return redirect()->route('admin.products.index')->with('success', "Product “{$model->name}” updated.");
    }

    public function destroy(string $product)
    {
        // Soft delete: images and their files are kept so the product can be restored; past orders keep their snapshot.
        Product::findByUidOrFail($product)->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product deleted.');
    }

    public function toggle(string $product, string $field)
    {
        abort_unless(in_array($field, ['is_active', 'is_featured', 'in_stock'], true), 404);

        $model = Product::findByUidOrFail($product);
        $model->update([$field => ! $model->{$field}]);

        return response()->json(['ok' => true, 'value' => $model->{$field}]);
    }

    /** "Does this product code exist?" box on the Add product form: exact match + similar codes (cached catalogue). */
    public function checkCode(Request $request)
    {
        $code = trim((string) $request->validate(['code' => 'required|string|max:60'])['code']);
        $needle = mb_strtolower($code);
        $catalog = $this->products->adminCatalog();
        $row = fn (array $p) => [
            'code' => $p['code'], 'name' => $p['name'], 'thumb' => img_url($p['thumbnail']), 'active' => $p['is_active'],
            'edit_url' => route('admin.products.edit', $p['uid']), 'view_url' => route('admin.products.show', $p['uid']),
        ];
        $exact = $catalog->first(fn ($p) => mb_strtolower((string) $p['code']) === $needle);
        $similar = $catalog->filter(fn ($p) => $p['uid'] !== ($exact['uid'] ?? null) && str_contains(mb_strtolower((string) $p['code']), $needle))->take(5);

        return response()->json([
            'code' => $code,
            'exists' => (bool) $exact,
            'product' => $exact ? $row($exact) : null,
            'similar' => $similar->map($row)->values(),
        ]);
    }

    /* ---------------------------------------------------------------- Featured products manager
     * featured_order = place in the list (NULL = not listed); is_featured = shown on the home page.
     * A listed product can be switched off (stays in the list, hidden from home) and back on. */

    public function featured()
    {
        return view('admin.products.featured', ['featured' => $this->products->adminFeatured()]);
    }

    /** Drag & drop order: {ids: [uid, …]} → featured_order 1, 2, 3 … (only products in the list). */
    public function featuredSort(Request $request)
    {
        $ids = $request->validate(['ids' => 'required|array|max:5000', 'ids.*' => ['string', 'regex:/^'.UniqueId::PATTERN.'$/']])['ids'];

        DB::transaction(function () use ($ids) {
            foreach (array_values(array_unique($ids)) as $i => $uid) {
                DB::table('products')->where('unique_id', $uid)->whereNotNull('featured_order')->update(['featured_order' => $i + 1]);
            }
        });
        ModelCache::bump(ModelCache::PRODUCTS); // one refresh for the whole new order (home page, lists)

        return $this->featuredResponse('Order saved. The home page shows the new order.');
    }

    /** Switch: show on the home page or not, keeping the product (and its place) in the list. */
    public function featuredToggle(string $product)
    {
        $model = Product::findByUidOrFail($product);
        abort_if($model->featured_order === null, 404);
        $model->update(['is_featured' => ! $model->is_featured]);

        return $this->featuredResponse($model->is_featured ? "“{$model->name}” is shown on the home page again." : "“{$model->name}” is switched off — hidden from the home page, kept in this list.");
    }

    /** Picker results from the cached catalogue (name, code, category, subcategory, vendor, brand). */
    public function featuredSearch(Request $request)
    {
        $q = (string) ($request->validate(['q' => 'nullable|string|max:100'])['q'] ?? '');
        $result = $this->products->adminCatalogSearch($q, 20);

        return response()->json([
            'total' => $result['total'],
            'items' => $result['items']->map(fn ($p) => $this->featuredJson($p)),
        ]);
    }

    /** Idempotent: adding an already featured product is a no-op. */
    public function feature(string $product)
    {
        return $this->setFeatured($product, true);
    }

    public function unfeature(string $product)
    {
        return $this->setFeatured($product, false);
    }

    /** Add: listed at the top and shown on home (no-op if already shown). Remove: off the list entirely. */
    protected function setFeatured(string $uid, bool $value)
    {
        $model = Product::findByUidOrFail($uid);
        if ($value && ! $model->is_featured) {
            $model->update(['is_featured' => true]); // Product::saving puts it at the top when not listed yet
        } elseif (! $value && ($model->is_featured || $model->featured_order !== null)) {
            $model->update(['is_featured' => false, 'featured_order' => null]);
        }

        return $this->featuredResponse($value ? "“{$model->name}” added to featured." : "“{$model->name}” removed from featured.");
    }

    /** The whole list is re-rendered: order and "on home" badges of other rows can shift. */
    protected function featuredResponse(string $message)
    {
        $featured = $this->products->adminFeatured();

        return response()->json([
            'ok' => true,
            'message' => $message,
            'count' => $featured->count(),
            'live' => $featured->where('is_featured', true)->count(),
            'rows' => view('admin.products.partials.featured-rows', compact('featured'))->render(),
        ]);
    }

    protected function featuredJson(array $p): array
    {
        return [
            'uid' => $p['uid'],
            'name' => $p['name'],
            'code' => $p['code'],
            'thumb' => img_url($p['thumbnail']),
            'category' => $p['category'],
            'subcategory' => $p['subcategory'],
            'vendor' => $p['vendor'],
            'brand' => $p['brand'],
            'unit_price' => money($p['unit_price']),
            'discount_price' => $p['discount_price'] !== null ? money($p['discount_price']) : null,
            'orders' => $p['orders'],
            'is_featured' => $p['is_featured'],
            'is_active' => $p['is_active'],
            'in_stock' => $p['in_stock'],
            'edit_url' => route('admin.products.edit', $p['uid']),
        ];
    }

    public function destroyImage(string $product, string $image)
    {
        $owner = Product::findByUidOrFail($product);
        ProductImage::where('product_id', $owner->id)->where('unique_id', $image)->firstOrFail()->delete();

        return response()->json(['ok' => true]);
    }

    public function sortImages(Request $request, string $product)
    {
        $ids = $request->validate(['ids' => 'required|array|max:200', 'ids.*' => ['string', 'regex:/^'.UniqueId::PATTERN.'$/']])['ids'];
        $owner = Product::findByUidOrFail($product);

        DB::transaction(function () use ($ids, $owner) {
            foreach ($ids as $i => $uid) {
                ProductImage::where('product_id', $owner->id)->where('unique_id', $uid)->update(['sort_order' => $i]);
            }
        });

        return response()->json(['ok' => true]);
    }

    protected function form(Product $product)
    {
        return view('admin.products.form', [
            'product' => $product,
            'categories' => $this->categories->all(),
            'vendors' => $this->vendors->all(),
            'brands' => $this->brands->all(),
        ]);
    }

    protected function storeImages(Product $product, Request $request): void
    {
        $start = (int) $product->images()->max('sort_order') + 1;

        foreach ((array) $request->file('images', []) as $i => $file) {
            $stored = $this->images->storeProduct($file);
            $product->images()->create($stored + ['sort_order' => $start + $i]);
        }

    }

    /** The required thumbnail: 800px for the product page, 400px for cards, search, cart and orders. */
    protected function storeThumbnail(Request $request): array
    {
        if (! $request->hasFile('thumbnail')) {
            return [];
        }
        $stored = $this->images->storeProduct($request->file('thumbnail'));

        return ['thumbnail' => $stored['thumb'], 'thumbnail_large' => $stored['path']];
    }

    protected function validated(Request $request, ?Product $product = null): array
    {
        $id = $product?->id;

        $data = $request->validate([
            'name' => 'required|string|min:3|max:200',
            'code' => ['required', 'alpha_dash', 'max:60', Rule::unique('products', 'code')->ignore($id)->whereNull('deleted_at')],
            'slug' => 'nullable|alpha_dash|max:200',
            'category_id' => ['required', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'subcategory_id' => ['nullable', Rule::exists('subcategories', 'id')->where('category_id', $request->input('category_id'))->whereNull('deleted_at')],
            'brand_id' => ['nullable', Rule::exists('brands', 'id')->whereNull('deleted_at')],
            'vendor_id' => ['nullable', Rule::exists('vendors', 'id')->whereNull('deleted_at')],
            'short_description' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:65000',
            'unit_price' => 'required|numeric|min:1|max:9999999',
            'discount_price' => 'nullable|numeric|min:1|lt:unit_price',
            'drop_shipping_price' => 'required|numeric|min:0|max:9999999',
            'vendor_product_url' => 'nullable|url|max:255',
            ...Seo::rules(),
            'thumbnail' => upload_rules('thumbnail', ! $product?->thumbnail),
            'images' => 'nullable|array|max:'.config('shop.uploads.product_files'),
            'images.*' => upload_rules('product', true),
        ], [
            'code.alpha_dash' => 'Product code may only contain letters, numbers, dashes and underscores.',
            'thumbnail.required' => 'Please choose a thumbnail image.',
            'thumbnail.max' => 'Thumbnail must be smaller than '.round(config('shop.uploads.thumbnail') / 1024, 1).'MB.',
            'thumbnail.mimes' => 'Thumbnail must be JPG, PNG or WebP.',
            'thumbnail.dimensions' => 'Thumbnail must be at least '.implode('×', config('shop.uploads.min.thumbnail')).' pixels.',
            'images.max' => 'You can upload at most :max images at a time.',
            'images.*.max' => 'Each image must be smaller than '.round(config('shop.uploads.product') / 1024, 1).'MB.',
            'images.*.mimes' => 'Images must be JPG, PNG or WebP.',
            'discount_price.lt' => 'Discount price must be lower than the regular price.',
            'subcategory_id.exists' => 'The subcategory does not belong to the selected category.',
            ...Seo::messages(),
        ]);

        unset($data['images'], $data['thumbnail']);
        $data = Seo::apply($request, $data, $product, 'products');
        $data['description'] = isset($data['description']) ? RichText::sanitize($data['description']) : null;
        foreach (['is_active', 'is_featured', 'in_stock'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }

        return $data;
    }
}
