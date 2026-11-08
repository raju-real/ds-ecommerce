<?php

namespace App\Models;

use App\Models\Concerns\FlushesModelCache;
use App\Models\Concerns\HasUniqueId;
use App\Models\Concerns\HasUniqueSlug;
use App\Support\ModelCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use FlushesModelCache, HasUniqueSlug, HasUniqueId, SoftDeletes;

    // Products embed their category, so both groups go stale together.
    protected static array $cacheGroups = [ModelCache::CATEGORIES, ModelCache::PRODUCTS];

    protected $fillable = ['name', 'slug', 'image', 'sort_order', 'is_active', 'meta_title', 'meta_description'];

    protected $casts = ['is_active' => 'boolean'];

    public function subcategories()
    {
        return $this->hasMany(Subcategory::class)->orderBy('sort_order')->orderBy('name');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function getUrlAttribute(): string
    {
        return route('category.show', $this->slug);
    }
}
