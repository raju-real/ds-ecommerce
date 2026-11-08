<?php

namespace App\Repositories;

use App\Models\Slider;
use App\Support\ModelCache;
use Illuminate\Support\Collection;

class SliderRepository
{
    public function active(): Collection
    {
        return $this->all()->where('is_active', true)->values();
    }

    public function all(): Collection
    {
        return ModelCache::remember(ModelCache::SLIDERS, 'all', fn () => Slider::orderBy('sort_order')->latest('id')->get());
    }

    public function find(string $uid): ?Slider
    {
        return $this->all()->firstWhere('unique_id', $uid);
    }
}
