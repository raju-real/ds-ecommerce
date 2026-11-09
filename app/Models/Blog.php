<?php

namespace App\Models;

use App\Models\Concerns\FlushesModelCache;
use App\Models\Concerns\HasUniqueId;
use App\Models\Concerns\HasUniqueSlug;
use App\Support\ModelCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Blog extends Model
{
    use FlushesModelCache, HasUniqueId, HasUniqueSlug, SoftDeletes;

    protected static array $cacheGroups = [ModelCache::BLOGS];

    protected $fillable = ['title', 'slug', 'description', 'excerpt', 'image', 'thumb', 'is_active', 'published_at', 'meta_title', 'meta_description', 'meta_keywords', 'meta_image'];

    protected $casts = [
        'is_active' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // The card/SEO summary always follows the text of the description.
        static::saving(function (Blog $blog) {
            if ($blog->isDirty('description') || ! $blog->excerpt) {
                $blog->excerpt = static::summarize($blog->description);
            }
        });
    }

    public static function summarize(?string $html, int $limit = 200): string
    {
        $text = html_entity_decode(strip_tags(preg_replace('/<(br|\/p|\/h\d|\/li|\/div)>/i', ' ', (string) $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return Str::limit(trim(preg_replace('/\s+/u', ' ', $text)), $limit);
    }

    /** Visible on the site: active and published (a future date schedules the post). */
    public function isPublished(): bool
    {
        return $this->is_active && $this->published_at && $this->published_at->lte(now());
    }

    public function getUrlAttribute(): string
    {
        return route('blog.show', $this->slug);
    }

    public function getImageUrlAttribute(): string
    {
        return img_url($this->image);
    }

    public function getThumbUrlAttribute(): string
    {
        return img_url($this->thumb ?: $this->image);
    }

    /** Rough reading time for the article header. */
    public function getReadingMinutesAttribute(): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags((string) $this->description)) / 200));
    }
}
