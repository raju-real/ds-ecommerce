<?php

namespace App\Models;

use App\Models\Concerns\FlushesModelCache;
use App\Models\Concerns\HasUniqueId;
use App\Support\ModelCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A customer's rating & review of a product, from a delivered order (one per order). */
class ProductReview extends Model
{
    use FlushesModelCache, HasUniqueId, SoftDeletes;

    protected static array $cacheGroups = [ModelCache::REVIEWS, ModelCache::PRODUCTS];

    protected $fillable = ['product_id', 'order_id', 'name', 'mobile', 'rating', 'review', 'images', 'is_active'];

    protected $casts = ['rating' => 'integer', 'images' => 'array', 'is_active' => 'boolean'];

    protected $hidden = ['mobile'];

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function order()
    {
        return $this->belongsTo(Order::class)->withTrashed();
    }

    /** "01712345678" → "017****5678" (shown in the admin list only). */
    public function getMaskedMobileAttribute(): string
    {
        return substr($this->mobile, 0, 3).'****'.substr($this->mobile, -4);
    }

    /** Keeps the product's average and count (used by the stars on cards) in step with its visible reviews. */
    public static function refreshProductRating(int $productId): void
    {
        $row = static::query()->where('product_id', $productId)->where('is_active', true)
            ->selectRaw('COUNT(*) as c, COALESCE(AVG(rating),0) as a')->toBase()->first();
        Product::withTrashed()->whereKey($productId)->toBase()->update(['rating_count' => (int) $row->c, 'rating_avg' => round((float) $row->a, 2)]);
        ModelCache::bump(ModelCache::PRODUCTS, ModelCache::REVIEWS);
    }

    protected static function booted(): void
    {
        $refresh = fn (ProductReview $r) => static::refreshProductRating($r->product_id);
        static::saved($refresh);
        static::deleted($refresh);
        static::restored($refresh);
    }
}
