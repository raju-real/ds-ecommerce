<?php

namespace App\Models;

use App\Models\Concerns\FlushesModelCache;
use App\Models\Concerns\HasUniqueId;
use App\Models\Concerns\HasUniqueSlug;
use App\Support\ModelCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Product extends Model
{
    use FlushesModelCache, HasUniqueSlug, HasUniqueId, SoftDeletes;

    protected static array $cacheGroups = [ModelCache::PRODUCTS];

    protected $fillable = [
        'category_id', 'subcategory_id', 'brand_id', 'vendor_id', 'name', 'code', 'slug', 'short_description',
        'description', 'unit_price', 'discount_price', 'drop_shipping_price', 'vendor_product_url',
        'thumbnail', 'thumbnail_large', 'in_stock', 'is_featured', 'is_active', 'meta_title', 'meta_description',
        'meta_keywords', 'meta_image', 'featured_order',
    ];

    // Never serialised to JSON / arrays that may reach the storefront.
    protected $hidden = ['drop_shipping_price', 'vendor_product_url', 'vendor_id'];

    protected $casts = [
        'unit_price' => 'float',
        'discount_price' => 'float',
        'drop_shipping_price' => 'float',
        'in_stock' => 'boolean',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'featured_order' => 'integer',
    ];

    protected static function booted(): void
    {
        // Featured list: featured_order is the place in the list (NULL = not listed); is_featured = shown on the home page.
        // Switching a product to featured (form, list toggle, featured page) puts it at the top of the list.
        static::saving(function (Product $p) {
            if ($p->is_featured && $p->featured_order === null) {
                $p->featured_order = (int) static::query()->whereNotNull('featured_order')->min('featured_order') - 1;
            }
        });
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    /** Price the customer pays. */
    public function getSellingPriceAttribute(): float
    {
        return $this->hasDiscount() ? $this->discount_price : $this->unit_price;
    }

    public function hasDiscount(): bool
    {
        return $this->discount_price !== null && $this->discount_price > 0 && $this->discount_price < $this->unit_price;
    }

    public function getDiscountPercentAttribute(): int
    {
        return $this->hasDiscount() ? (int) round(100 - ($this->discount_price / $this->unit_price * 100)) : 0;
    }

    /** Admin only: margin per unit after paying the vendor. */
    public function getProfitAttribute(): float
    {
        return round($this->selling_price - $this->drop_shipping_price, 2);
    }

    public function getProfitMarginAttribute(): float
    {
        return $this->selling_price > 0 ? round($this->profit / $this->selling_price * 100, 1) : 0;
    }

    public function getUrlAttribute(): string
    {
        return route('product.show', $this->slug);
    }

    public function getThumbUrlAttribute(): string
    {
        return img_url($this->thumbnail);
    }

    public function getImageUrlAttribute(): string
    {
        return img_url($this->thumbnail_large ?: $this->thumbnail);
    }

    /** Facebook/WhatsApp/Google share image: the SEO "Share image" (1200×630) when set, else the main photo. */
    public function getShareImageUrlAttribute(): string
    {
        return $this->meta_image ? img_url($this->meta_image) : $this->image_url;
    }

    /** [width, height] of share_image_url, for og:image:width/height. */
    public function shareImageSize(): array
    {
        return $this->meta_image ? config('shop.images.share') : [800, 800];
    }

    /** Thumbnail first, then the optional gallery images — each item has path (large) and thumb. */
    public function gallery(): Collection
    {
        $items = $this->images->map(fn ($i) => (object) ['path' => $i->path, 'thumb' => $i->thumb]);

        if ($this->thumbnail) {
            $items->prepend((object) ['path' => $this->thumbnail_large ?: $this->thumbnail, 'thumb' => $this->thumbnail]);
        }

        return $items->isNotEmpty() ? $items->values() : collect([(object) ['path' => null, 'thumb' => null]]);
    }
}
