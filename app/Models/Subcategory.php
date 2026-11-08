<?php

namespace App\Models;

use App\Models\Concerns\FlushesModelCache;
use App\Models\Concerns\HasUniqueId;
use App\Models\Concerns\HasUniqueSlug;
use App\Support\ModelCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subcategory extends Model
{
    use FlushesModelCache, HasUniqueSlug, HasUniqueId, SoftDeletes;

    protected static array $cacheGroups = [ModelCache::CATEGORIES, ModelCache::PRODUCTS];

    protected $fillable = ['category_id', 'name', 'slug', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
