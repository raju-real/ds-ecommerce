<?php

namespace App\Support;

use App\Repositories\BlogRepository;
use App\Repositories\BrandRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductRepository;
use Illuminate\Support\Carbon;

/**
 * Everything /sitemap.xml lists, grouped, so the XML and the admin summary (Settings → SEO) always match.
 * Only pages Google should index: active items, and categories/brands that have active products
 * (empty ones would be "thin" pages). Search, cart, checkout, order and buy-now pages are never listed.
 */
class Sitemap
{
    public function __construct(
        protected ProductRepository $products,
        protected CategoryRepository $categories,
        protected BrandRepository $brands,
        protected BlogRepository $blogs,
    ) {
    }

    /**
     * @return array<string, list<array{loc: string, lastmod: ?Carbon, images?: list<array{loc: string, title: string}>}>>
     */
    public function groups(): array
    {
        $products = $this->products->sitemap();
        $blogs = $this->blogs->sitemap();
        $categories = $this->categories->menu()->filter(fn ($c) => $c->products_count > 0);

        $groups = [
            'pages' => array_values(array_filter([
                ['loc' => route('home'), 'lastmod' => $products->max('updated_at')],
                ['loc' => route('products.index'), 'lastmod' => $products->max('updated_at')],
                $blogs->isNotEmpty() ? ['loc' => route('blog.index'), 'lastmod' => $blogs->max('updated_at')] : null,
            ])),
            'categories' => [],
            'brands' => $this->brands->active()->filter(fn ($b) => $b->products_count > 0)
                ->map(fn ($b) => ['loc' => route('brand.show', $b->slug), 'lastmod' => $b->updated_at])->values()->all(),
            'products' => $products->map(fn ($p) => [
                'loc' => route('product.show', $p->slug),
                'lastmod' => $p->updated_at,
                // Google Images: the main photo (and the share image when set) with the product name.
                'images' => array_values(array_filter([
                    ($p->thumbnail_large || $p->thumbnail) ? ['loc' => $p->image_url, 'title' => $p->name] : null,
                    $p->meta_image ? ['loc' => img_url($p->meta_image), 'title' => $p->name] : null,
                ])),
            ])->values()->all(),
            'blog' => $blogs->map(fn ($b) => [
                'loc' => $b->url,
                'lastmod' => $b->updated_at,
                'images' => $b->image ? [['loc' => $b->image_url, 'title' => $b->title]] : [],
            ])->values()->all(),
        ];

        foreach ($categories as $cat) {
            $groups['categories'][] = ['loc' => route('category.show', $cat->slug), 'lastmod' => $cat->updated_at];
            foreach ($cat->subcategories as $sub) {
                $groups['categories'][] = ['loc' => route('subcategory.show', [$cat->slug, $sub->slug]), 'lastmod' => $sub->updated_at];
            }
        }

        return $groups;
    }

    /** For the admin: number of links per group, total, and when the newest one changed. */
    public function summary(): array
    {
        $groups = $this->groups();
        $all = collect($groups)->flatten(1);

        return [
            'counts' => array_map('count', $groups),
            'total' => $all->count(),
            'images' => $all->sum(fn ($u) => count($u['images'] ?? [])),
            'updated' => $all->pluck('lastmod')->filter()->max(),
        ];
    }
}
