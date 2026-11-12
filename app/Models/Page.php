<?php

namespace App\Models;

use App\Models\Concerns\FlushesModelCache;
use App\Models\Concerns\HasSortOrder;
use App\Models\Concerns\HasUniqueId;
use App\Models\Concerns\HasUniqueSlug;
use App\Support\ModelCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A page built in Admin → Website → Pages, shown at /page/{slug} and linked from the footer. */
class Page extends Model
{
    use FlushesModelCache, HasSortOrder, HasUniqueId, HasUniqueSlug, SoftDeletes;

    protected static array $cacheGroups = [ModelCache::PAGES];

    protected $fillable = ['name', 'slug', 'content', 'image', 'is_active', 'sort_order', 'meta_title', 'meta_description', 'meta_keywords', 'meta_image'];

    protected $casts = ['is_active' => 'boolean', 'sort_order' => 'integer'];

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
