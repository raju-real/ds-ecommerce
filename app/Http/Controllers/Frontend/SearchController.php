<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Repositories\BrandRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductRepository;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * JSON for the header search dropdown: products, categories (incl. subcategories) and brands.
     * Products come from a per-term cache; categories and brands are filtered from cached lists.
     */
    public function suggest(Request $request, ProductRepository $products, CategoryRepository $categories, BrandRepository $brands)
    {
        $cfg = config('shop.search');
        $term = trim((string) $request->validate([
            'q' => 'required|string|min:'.$cfg['min_chars'].'|max:'.$cfg['max_chars'],
        ])['q']);
        $needle = mb_strtolower($term);
        $matches = fn (string $text) => str_contains(mb_strtolower($text), $needle);

        $productRows = $products->suggest($term, $cfg['suggest_limit'])->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'code' => $p->code,
            'url' => $p->url,
            'image' => $p->thumb_url,
            'price' => money($p->selling_price),
            'old_price' => $p->hasDiscount() ? money($p->unit_price) : null,
            'discount' => $p->discount_percent,
            'in_stock' => $p->in_stock,
        ])->values();

        $categoryRows = collect();
        foreach ($categories->menu() as $cat) {
            if ($matches($cat->name)) {
                $categoryRows->push([
                    'name' => $cat->name,
                    'url' => route('category.show', $cat->slug),
                    'image' => img_url($cat->image),
                    'meta' => $cat->products_count.' '.str('product')->plural($cat->products_count),
                ]);
            }
            foreach ($cat->subcategories as $sub) {
                if ($matches($sub->name)) {
                    $categoryRows->push([
                        'name' => $sub->name,
                        'url' => route('subcategory.show', [$cat->slug, $sub->slug]),
                        'image' => img_url($cat->image),
                        'meta' => 'in '.$cat->name,
                    ]);
                }
            }
        }

        $brandRows = $brands->active()->filter(fn ($b) => $matches($b->name))->map(fn ($b) => [
            'name' => $b->name,
            'url' => route('brand.show', $b->slug),
            'image' => img_url($b->logo),
            'meta' => $b->products_count.' '.str('product')->plural($b->products_count),
        ])->values();

        return response()->json([
            'query' => $term,
            'all_url' => route('products.index', ['q' => $term]),
            'products' => $productRows,
            'categories' => $categoryRows->take($cfg['suggest_limit'])->values(),
            'brands' => $brandRows->take($cfg['suggest_limit'])->values(),
        ]);
    }
}
