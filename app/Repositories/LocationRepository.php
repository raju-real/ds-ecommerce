<?php

namespace App\Repositories;

use App\Models\District;
use App\Models\Thana;
use App\Support\ModelCache;
use Illuminate\Support\Collection;

class LocationRepository
{
    /** Active districts, keyed for checkout select. */
    public function districts(): Collection
    {
        return ModelCache::remember(ModelCache::LOCATIONS, 'districts', fn () => District::query()
            ->where('is_active', true)->orderBy('name')
            ->get(['id', 'name', 'bn_name', 'delivery_charge']));
    }

    public function district(int $id): ?District
    {
        return $this->districts()->firstWhere('id', $id);
    }

    public function thanas(int $districtId): Collection
    {
        return ModelCache::remember(ModelCache::LOCATIONS, "thanas:$districtId", fn () => Thana::query()
            ->where('district_id', $districtId)->where('is_active', true)->orderBy('name')
            ->get(['id', 'district_id', 'name']));
    }

    public function thana(int $districtId, int $thanaId): ?Thana
    {
        return $this->thanas($districtId)->firstWhere('id', $thanaId);
    }

    public function findThana(string $uid): ?Thana
    {
        return ModelCache::remember(ModelCache::LOCATIONS, "thana:$uid", fn () => Thana::where('unique_id', $uid)->first());
    }

    public function allDistricts(): Collection
    {
        return ModelCache::remember(ModelCache::LOCATIONS, 'districts:all', fn () => District::orderBy('name')->get());
    }

    public function adminDistricts(array $filters)
    {
        $key = 'admin:districts:'.ModelCache::hash($filters + ['page' => request('page', 1)]);

        return ModelCache::rememberList(ModelCache::LOCATIONS, $key, fn () => District::query()
            ->withCount('thanas')
            ->when($filters['q'] ?? null, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', '%'.like_escape($s).'%')->orWhere('bn_name', 'like', '%'.like_escape($s).'%')))
            ->orderBy('name')
            ->paginate(config('shop.per_page.admin')));
    }

    public function adminThanas(array $filters)
    {
        $key = 'admin:thanas:'.ModelCache::hash($filters + ['page' => request('page', 1)]);

        return ModelCache::rememberList(ModelCache::LOCATIONS, $key, fn () => Thana::query()
            ->with('district:id,name')
            ->when($filters['district_id'] ?? null, fn ($q, $id) => $q->where('district_id', $id))
            ->when($filters['q'] ?? null, fn ($q, $s) => $q->where('name', 'like', '%'.like_escape($s).'%'))
            ->orderBy('district_id')->orderBy('name')
            ->paginate(config('shop.per_page.admin')));
    }
}
