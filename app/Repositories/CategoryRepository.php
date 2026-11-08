<?php

namespace App\Repositories;

use App\Models\Category;
use App\Models\Subcategory;
use App\Support\ModelCache;
use Illuminate\Support\Collection;

class CategoryRepository
{
    /** Active categories with their active subcategories (storefront menu, filters). */
    public function menu(): Collection
    {
        return ModelCache::remember(ModelCache::CATEGORIES, 'menu', fn () => Category::query()
            ->where('is_active', true)
            ->with(['subcategories' => fn ($q) => $q->where('is_active', true)])
            ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('sort_order')->orderBy('name')
            ->get());
    }

    public function findBySlug(string $slug): ?Category
    {
        return $this->menu()->firstWhere('slug', $slug);
    }

    /** Every category incl. inactive, for admin dropdowns. */
    public function all(): Collection
    {
        return ModelCache::remember(ModelCache::CATEGORIES, 'all', fn () => Category::query()
            ->with('subcategories')
            ->orderBy('sort_order')->orderBy('name')
            ->get());
    }

    public function find(string $uid): ?Category
    {
        return $this->all()->firstWhere('unique_id', $uid);
    }

    public function adminPaginate(array $filters)
    {
        $key = 'admin:list:'.ModelCache::hash($filters + ['page' => request('page', 1)]);

        return ModelCache::rememberList([ModelCache::CATEGORIES, ModelCache::PRODUCTS], $key, fn () => Category::query()
            ->withCount(['subcategories', 'products'])
            ->when($filters['q'] ?? null, fn ($q, $s) => $q->where('name', 'like', '%'.like_escape($s).'%'))
            ->orderBy('sort_order')->orderBy('name')
            ->paginate(config('shop.per_page.admin')));
    }

    public function findSubcategory(string $uid): ?Subcategory
    {
        return ModelCache::remember(ModelCache::CATEGORIES, "sub:$uid", fn () => Subcategory::with('category')->where('unique_id', $uid)->first());
    }

    public function subcategoriesPaginate(array $filters)
    {
        $key = 'admin:subs:'.ModelCache::hash($filters + ['page' => request('page', 1)]);

        return ModelCache::rememberList([ModelCache::CATEGORIES, ModelCache::PRODUCTS], $key, fn () => Subcategory::query()
            ->with('category:id,name')
            ->withCount('products')
            ->when($filters['category_id'] ?? null, fn ($q, $id) => $q->where('category_id', $id))
            ->when($filters['q'] ?? null, fn ($q, $s) => $q->where('name', 'like', '%'.like_escape($s).'%'))
            ->orderBy('category_id')->orderBy('sort_order')->orderBy('name')
            ->paginate(config('shop.per_page.admin')));
    }
}
