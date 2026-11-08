<?php

namespace App\Repositories;

use App\Models\Brand;
use App\Support\ModelCache;
use Illuminate\Support\Collection;

class BrandRepository
{
    /** Active brands with active product counts (storefront filters, search). */
    public function active(): Collection
    {
        return ModelCache::remember([ModelCache::BRANDS, ModelCache::PRODUCTS], 'active', fn () => Brand::query()
            ->where('is_active', true)
            ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('sort_order')->orderBy('name')
            ->get());
    }

    public function findBySlug(string $slug): ?Brand
    {
        return $this->active()->firstWhere('slug', $slug);
    }

    /** Every brand incl. inactive, for admin dropdowns. */
    public function all(): Collection
    {
        return ModelCache::remember(ModelCache::BRANDS, 'all', fn () => Brand::orderBy('sort_order')->orderBy('name')->get());
    }

    public function find(string $uid): ?Brand
    {
        return $this->all()->firstWhere('unique_id', $uid);
    }

    public function adminPaginate(array $filters)
    {
        $key = 'admin:list:'.ModelCache::hash($filters + ['page' => request('page', 1)]);

        return ModelCache::rememberList([ModelCache::BRANDS, ModelCache::PRODUCTS], $key, fn () => Brand::query()
            ->withCount('products')
            ->when($filters['q'] ?? null, fn ($q, $s) => $q->where('name', 'like', '%'.like_escape($s).'%'))
            ->orderBy('sort_order')->orderBy('name')
            ->paginate(config('shop.per_page.admin')));
    }
}
