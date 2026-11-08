<?php

namespace App\Repositories;

use App\Models\Vendor;
use App\Support\ModelCache;
use Illuminate\Support\Collection;

class VendorRepository
{
    public function all(): Collection
    {
        return ModelCache::remember(ModelCache::VENDORS, 'all', fn () => Vendor::orderBy('name')->get());
    }

    public function find(string $uid): ?Vendor
    {
        return $this->all()->firstWhere('unique_id', $uid);
    }

    public function adminPaginate(array $filters)
    {
        $key = 'admin:list:'.ModelCache::hash($filters + ['page' => request('page', 1)]);

        return ModelCache::rememberList([ModelCache::VENDORS, ModelCache::PRODUCTS], $key, fn () => Vendor::query()
            ->withCount('products')
            ->when($filters['q'] ?? null, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.like_escape($s).'%')->orWhere('phone', 'like', '%'.like_escape($s).'%')->orWhere('contact_person', 'like', '%'.like_escape($s).'%')))
            ->orderBy('name')
            ->paginate(config('shop.per_page.admin')));
    }
}
