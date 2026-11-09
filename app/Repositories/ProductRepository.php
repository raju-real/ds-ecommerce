<?php

namespace App\Repositories;

use App\Models\OrderItem;
use App\Models\Product;
use App\Support\ModelCache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductRepository
{
    /** Effective selling price in SQL (discount only when it is a real discount). */
    public const PRICE_SQL = 'CASE WHEN discount_price > 0 AND discount_price < unit_price THEN discount_price ELSE unit_price END';

    /** Columns needed for product cards; skips the heavy description. */
    protected const CARD_COLUMNS = ['id', 'unique_id', 'category_id', 'subcategory_id', 'brand_id', 'name', 'code', 'slug', 'short_description',
        'unit_price', 'discount_price', 'thumbnail', 'in_stock', 'is_featured', 'created_at'];

    public const SORTS = [
        'latest' => 'Newest first',
        'price_asc' => 'Price: Low to High',
        'price_desc' => 'Price: High to Low',
        'discount' => 'Biggest discount',
        'name_asc' => 'Name: A to Z',
    ];

    public function newArrivals(int $limit): Collection
    {
        return ModelCache::remember(ModelCache::PRODUCTS, "home:new:$limit", fn () => $this->cardQuery()
            ->latest('id')->take($limit)->get());
    }

    /**
     * Home page featured products in the order set on the Featured Products page, one "Load more" step per page.
     * Never goes past shop.home.featured_max; each page is cached on its own.
     *
     * @return array{items: Collection, page: int, loaded: int, total: int, has_more: bool, reached_max: bool}
     */
    public function featuredPage(int $page): array
    {
        $per = (int) config('shop.home.featured');
        $max = (int) config('shop.home.featured_max');
        $page = max(1, $page);
        $offset = ($page - 1) * $per;
        $take = max(0, min($per, $max - $offset));

        $items = $take === 0 ? collect() : ModelCache::remember(ModelCache::PRODUCTS, "home:featured:v2:$per:$page", fn () => $this->cardQuery()
            ->where('is_featured', true)->orderByRaw('featured_order IS NULL')->orderBy('featured_order')->latest('id')->skip($offset)->take($take)->get());
        $total = ModelCache::remember(ModelCache::PRODUCTS, 'home:featured:count', fn () => $this->cardQuery()->where('is_featured', true)->count());
        $loaded = min($offset + $items->count(), $total);

        return [
            'items' => $items,
            'page' => $page,
            'loaded' => $loaded,
            'total' => $total,
            'has_more' => $loaded < min($total, $max),
            'reached_max' => $loaded >= $max,
        ];
    }

    /**
     * Storefront listing with filters: q, category_id, subcategory_id, brand_id, min, max, sort, stock.
     */
    public function search(array $filters)
    {
        $filters = array_intersect_key($filters, array_flip(['q', 'category_id', 'subcategory_id', 'brand_id', 'min', 'max', 'sort', 'stock', 'featured']));
        $key = 'list:'.ModelCache::hash($filters + ['page' => request('page', 1)]);

        return ModelCache::rememberList(ModelCache::PRODUCTS, $key, function () use ($filters) {
            $query = $this->cardQuery()
                ->when($filters['q'] ?? null, fn ($q, $s) => $this->matchText($q, $s))
                ->when($filters['category_id'] ?? null, fn ($q, $id) => $q->where('category_id', $id))
                ->when($filters['brand_id'] ?? null, fn ($q, $id) => $q->where('brand_id', $id))
                ->when($filters['subcategory_id'] ?? null, fn ($q, $id) => $q->where('subcategory_id', $id))
                ->when(is_numeric($filters['min'] ?? null), fn ($q) => $q->whereRaw(self::PRICE_SQL.' >= ?', [$filters['min']]))
                ->when(is_numeric($filters['max'] ?? null), fn ($q) => $q->whereRaw(self::PRICE_SQL.' <= ?', [$filters['max']]))
                ->when(($filters['stock'] ?? null) === 'in', fn ($q) => $q->where('in_stock', true))
                ->when($filters['featured'] ?? null, fn ($q) => $q->where('is_featured', true));

            match ($filters['sort'] ?? 'latest') {
                'price_asc' => $query->orderByRaw(self::PRICE_SQL.' asc'),
                'price_desc' => $query->orderByRaw(self::PRICE_SQL.' desc'),
                'name_asc' => $query->orderBy('name'),
                'discount' => $query->orderByRaw('CASE WHEN discount_price > 0 AND discount_price < unit_price THEN (unit_price - discount_price) / unit_price ELSE 0 END desc'),
                default => ! empty($filters['featured'])
                    ? $query->orderByRaw('featured_order IS NULL')->orderBy('featured_order')->latest('id')
                    : $query->latest('id'),
            };

            return $query->paginate(config('shop.per_page.frontend'));
        });
    }

    /** Full product for the detail page / quick view — never hits the DB once warm. */
    public function findBySlug(string $slug): ?Product
    {
        return ModelCache::remember([ModelCache::PRODUCTS], "slug:$slug", fn () => Product::query()
            ->where('is_active', true)->where('slug', $slug)
            ->with(['images', 'category:id,name,slug', 'subcategory:id,name,slug,category_id', 'brand:id,name,slug,logo'])
            ->first());
    }

    /** Live search dropdown: top matching products, cached per normalised term. */
    public function suggest(string $term, int $limit): Collection
    {
        $term = mb_strtolower(trim($term));

        return ModelCache::rememberList(ModelCache::PRODUCTS, 'suggest:'.md5($term).":$limit", fn () => $this->cardQuery()
            ->where(fn ($q) => $this->matchText($q, $term))
            // Rank: name starts with term, then a word in the name starts with it, then any match.
            ->orderByRaw('CASE WHEN name LIKE ? THEN 0 WHEN name LIKE ? THEN 1 ELSE 2 END',
                [like_escape($term).'%', '% '.like_escape($term).'%'])
            ->orderByDesc('in_stock')->latest('id')
            ->take($limit)->get());
    }

    protected function matchText($query, string $term)
    {
        $like = '%'.like_escape($term).'%';

        return $query->where(fn ($w) => $w->where('name', 'like', $like)
            ->orWhere('code', 'like', $like)->orWhere('short_description', 'like', $like)->orWhere('meta_keywords', 'like', $like));
    }

    /** Active product by id (cart / checkout). */
    public function findActive(int $id): ?Product
    {
        return ModelCache::remember(ModelCache::PRODUCTS, "id:$id", fn () => Product::query()
            ->where('is_active', true)->with('images')->find($id));
    }

    public function related(Product $product, int $limit = 4): Collection
    {
        return ModelCache::remember(ModelCache::PRODUCTS, "related:{$product->id}:$limit", fn () => $this->cardQuery()
            ->where('id', '!=', $product->id)
            ->where('category_id', $product->category_id)
            ->inRandomOrder()->take($limit)->get());
    }

    public function priceBounds(): array
    {
        return ModelCache::remember(ModelCache::PRODUCTS, 'price-bounds', function () {
            $row = DB::table('products')->whereNull('deleted_at')->where('is_active', true)
                ->selectRaw('MIN('.self::PRICE_SQL.') as min_price, MAX('.self::PRICE_SQL.') as max_price')->first();

            return ['min' => (int) floor($row->min_price ?? 0), 'max' => (int) ceil($row->max_price ?? 0)];
        });
    }

    public function sitemap(): Collection
    {
        return ModelCache::remember(ModelCache::PRODUCTS, 'sitemap', fn () => Product::query()
            ->where('is_active', true)->select('slug', 'updated_at')->latest('id')->get());
    }

    /* ---------------------------------------------------------------- Admin */

    public function adminPaginate(array $filters)
    {
        $key = 'admin:list:'.ModelCache::hash($filters + ['page' => request('page', 1)]);

        return ModelCache::rememberList([ModelCache::PRODUCTS], $key, fn () => Product::query()
            ->select(array_merge(self::CARD_COLUMNS, ['vendor_id', 'drop_shipping_price', 'is_active']))
            ->with(['category:id,name', 'subcategory:id,name', 'vendor:id,name', 'brand:id,name'])
            ->when($filters['q'] ?? null, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', '%'.like_escape($s).'%')->orWhere('code', 'like', '%'.like_escape($s).'%')))
            ->when($filters['category_id'] ?? null, fn ($q, $id) => $q->where('category_id', $id))
            ->when($filters['vendor_id'] ?? null, fn ($q, $id) => $q->where('vendor_id', $id))
            ->when($filters['brand_id'] ?? null, fn ($q, $id) => $q->where('brand_id', $id))
            ->when(($filters['status'] ?? '') !== '', fn ($q) => match ($filters['status']) {
                'active' => $q->where('is_active', true),
                'inactive' => $q->where('is_active', false),
                'featured' => $q->where('is_featured', true),
                'out' => $q->where('in_stock', false),
                default => $q,
            })
            ->latest('id')
            ->paginate(config('shop.per_page.admin')));
    }

    public function adminFind(string $uid): ?Product
    {
        return ModelCache::remember(ModelCache::PRODUCTS, "admin:$uid", fn () => Product::with(['images', 'category', 'subcategory', 'vendor', 'brand'])->where('unique_id', $uid)->first());
    }

    /**
     * Light snapshot of every product for admin pickers (featured manager):
     * plain arrays with names, prices and order counts, refreshed by the observers.
     */
    public function adminCatalog(): Collection
    {
        // "v2": rows now carry featured_order/listed, so copies cached before the update are never read.
        return ModelCache::remember([ModelCache::PRODUCTS, ModelCache::ORDERS, ModelCache::CATEGORIES, ModelCache::VENDORS, ModelCache::BRANDS], 'admin:catalog:v2', fn () => Product::query()
            ->select(['id', 'unique_id', 'name', 'code', 'slug', 'thumbnail', 'category_id', 'subcategory_id', 'vendor_id', 'brand_id',
                'unit_price', 'discount_price', 'is_featured', 'featured_order', 'is_active', 'in_stock', 'created_at'])
            ->addSelect(['orders_count' => OrderItem::query()->selectRaw('COUNT(DISTINCT order_id)')
                ->whereColumn('order_items.product_id', 'products.id')->whereHas('order')])
            ->with(['category:id,name', 'subcategory:id,name', 'vendor:id,name', 'brand:id,name'])
            ->latest('id')
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'uid' => $p->unique_id,
                'name' => $p->name,
                'code' => $p->code,
                'slug' => $p->slug,
                'thumbnail' => $p->thumbnail,
                'category' => $p->category?->name,
                'subcategory' => $p->subcategory?->name,
                'vendor' => $p->vendor?->name,
                'brand' => $p->brand?->name,
                'unit_price' => $p->unit_price,
                'discount_price' => $p->hasDiscount() ? $p->discount_price : null,
                'is_featured' => $p->is_featured,
                'featured_order' => $p->featured_order,
                'listed' => $p->featured_order !== null || $p->is_featured, // in the featured list (switched on or off)
                'is_active' => $p->is_active,
                'in_stock' => $p->in_stock,
                'orders' => (int) $p->orders_count,
                // Lower-cased text every search term is matched against.
                'haystack' => mb_strtolower(implode(' ', array_filter([$p->name, $p->code, $p->category?->name, $p->subcategory?->name, $p->vendor?->name, $p->brand?->name]))),
            ])
            ->values());
    }

    /**
     * The featured list in its manual order (drag & drop), switched-off products included.
     * home_position is the place on the home page (1-based, switched on + active only): the first 12 show
     * at once, up to featured_max via "Load more".
     */
    public function adminFeatured(): Collection
    {
        $position = 0;

        return $this->adminCatalog()->where('listed', true)
            ->sortBy(fn ($p) => [$p['featured_order'] === null ? 1 : 0, $p['featured_order'] ?? 0, -$p['id']])
            ->values()
            ->map(function ($p) use (&$position) {
                $p['home_position'] = $p['is_featured'] && $p['is_active'] ? ++$position : null;
                $p['on_home'] = $p['home_position'] !== null && $p['home_position'] <= config('shop.home.featured');

                return $p;
            });
    }

    /**
     * Search the cached catalogue: every word must match the name, code, category, subcategory, vendor or brand.
     * Empty query: best-selling products that are not featured yet.
     */
    public function adminCatalogSearch(string $term, int $limit = 20): array
    {
        $words = array_filter(preg_split('/\s+/', mb_strtolower(trim($term))));
        $all = $this->adminCatalog();

        $matches = $words
            ? $all->filter(fn ($p) => collect($words)->every(fn ($w) => str_contains($p['haystack'], $w)))
                // Exact code first, then name starts with the term, then most ordered.
                ->sortBy([
                    fn ($a, $b) => (mb_strtolower($b['code']) === implode(' ', $words)) <=> (mb_strtolower($a['code']) === implode(' ', $words)),
                    fn ($a, $b) => str_starts_with(mb_strtolower($b['name']), $words[0]) <=> str_starts_with(mb_strtolower($a['name']), $words[0]),
                    fn ($a, $b) => $b['orders'] <=> $a['orders'],
                ])
            : $all->where('listed', false)->sortByDesc('orders');

        return ['total' => $matches->count(), 'items' => $matches->take($limit)->values()];
    }

    /** Catalogue-wide numbers for the dashboard. */
    public function stats(): array
    {
        return ModelCache::remember(ModelCache::PRODUCTS, 'stats', function () {
            $row = DB::table('products')->whereNull('deleted_at')->selectRaw(
                'COUNT(*) as total, SUM(is_active) as active, SUM(is_featured) as featured, SUM(in_stock = 0) as out_of_stock,
                 AVG('.self::PRICE_SQL.' - drop_shipping_price) as avg_profit'
            )->first();

            return [
                'total' => (int) $row->total,
                'active' => (int) $row->active,
                'featured' => (int) $row->featured,
                'out_of_stock' => (int) $row->out_of_stock,
                'avg_profit' => round((float) $row->avg_profit, 2),
            ];
        });
    }

    protected function cardQuery()
    {
        return Product::query()->select(self::CARD_COLUMNS)->where('is_active', true);
    }
}
