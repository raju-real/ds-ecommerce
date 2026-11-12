<?php

namespace App\Models;

use App\Models\Concerns\FlushesModelCache;
use App\Models\Concerns\HasSortOrder;
use App\Models\Concerns\HasUniqueId;
use App\Models\Concerns\HasUniqueSlug;
use App\Support\ModelCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A page built in Admin → Website → Pages, shown at /{slug} (e.g. /privacy-policy) and linked from the footer. */
class Page extends Model
{
    use FlushesModelCache, HasSortOrder, HasUniqueId, HasUniqueSlug, SoftDeletes;

    protected static array $cacheGroups = [ModelCache::PAGES];

    protected $fillable = ['name', 'slug', 'content', 'image', 'is_active', 'sort_order', 'meta_title', 'meta_description', 'meta_keywords', 'meta_image'];

    protected $casts = ['is_active' => 'boolean', 'sort_order' => 'integer'];

    /**
     * First parts of every shop/admin address (products, blog, checkout, admin, sitemap.xml …) and the public folders.
     * Pages live at /{slug}, so a page may never use one of these — it would hide that part of the shop.
     */
    public static function reservedSlugs(): array
    {
        static $reserved = null;
        if ($reserved === null) {
            $fromRoutes = collect(\Illuminate\Support\Facades\Route::getRoutes()->getRoutes())
                ->map(fn ($r) => strtolower(explode('/', trim($r->uri(), '/'))[0]))
                ->reject(fn ($seg) => $seg === '' || str_contains($seg, '{'));
            $reserved = $fromRoutes->merge(['page', 'uploads', 'css', 'js', 'images', 'errors', 'storage', 'build', 'vendor', 'index.php', 'api', 'login', 'logout', 'home'])
                ->unique()->values()->all();
        }

        return $reserved;
    }

    /** Like other models, plus: never a reserved name ("blog" → "blog-2"). */
    public static function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = \Illuminate\Support\Str::slug($value) ?: \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(8));
        $reserved = static::reservedSlugs();
        $slug = $base;
        $i = 2;
        while (in_array($slug, $reserved, true)
            || static::withTrashed()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    public function getUrlAttribute(): string
    {
        return route('page.show', $this->slug);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? img_url($this->image) : null;
    }

    /** Share image: the SEO "Share image", else the page image, else null (the site share image is used). */
    public function getShareImageUrlAttribute(): ?string
    {
        return $this->meta_image ? img_url($this->meta_image) : $this->image_url;
    }

    /** Plain-text summary of the content (Google description when no meta description is set). */
    public function summary(int $limit = 158): string
    {
        return Blog::summarize($this->content, $limit);
    }
}
