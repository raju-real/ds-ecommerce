<?php

namespace App\Models;

use App\Models\Concerns\FlushesModelCache;
use App\Models\Concerns\HasUniqueId;
use App\Support\ModelCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductImage extends Model
{
    use FlushesModelCache, HasUniqueId, SoftDeletes;

    protected static array $cacheGroups = [ModelCache::PRODUCTS];

    protected $fillable = ['product_id', 'path', 'thumb', 'sort_order'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
