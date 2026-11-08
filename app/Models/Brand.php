<?php

namespace App\Models;

use App\Models\Concerns\FlushesModelCache;
use App\Models\Concerns\HasUniqueId;
use App\Models\Concerns\HasUniqueSlug;
use App\Support\ModelCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{
    use FlushesModelCache, HasUniqueSlug, HasUniqueId, SoftDeletes;

    protected static array $cacheGroups = [ModelCache::BRANDS, ModelCache::PRODUCTS];

    protected $fillable = ['name', 'slug', 'logo', 'sort_order', 'is_active', 'meta_title', 'meta_description'];

    protected $casts = ['is_active' => 'boolean'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function getUrlAttribute(): string
    {
        return route('brand.show', $this->slug);
    }
}
