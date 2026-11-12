<?php

namespace App\Repositories;

use App\Models\Page;
use App\Support\ModelCache;
use App\Support\RichText;
use Illuminate\Support\Collection;

class PageRepository
{
    /** Light columns for the footer, sitemap and admin list (no content). */
    protected const LIST_COLUMNS = ['id', 'unique_id', 'name', 'slug', 'image', 'meta_image', 'is_active', 'sort_order', 'updated_at'];

    /** Every page in the order set on the admin list (sort order, then oldest first). */
    public function all(): Collection
    {
        return ModelCache::remember(ModelCache::PAGES, 'all', fn () => Page::query()
            ->select(self::LIST_COLUMNS)->orderBy('sort_order')->orderBy('id')->get());
    }

    /** Active pages for the footer and sitemap, in the admin order. */
    public function active(): Collection
    {
        return $this->all()->where('is_active', true)->values();
    }

    public function adminFind(string $uid): ?Page
    {
        return Page::where('unique_id', $uid)->first();
    }

    /** An active page with its content ready to print, or null (inactive / unknown → 404). */
    public function findActive(string $slug): ?Page
    {
        if (! $this->active()->firstWhere('slug', $slug)) {
            return null;
        }

        return ModelCache::remember(ModelCache::PAGES, "page:$slug", function () use ($slug) {
            $page = Page::where('slug', $slug)->where('is_active', true)->first();
            $page?->setAttribute('content_html', RichText::render($page->content, null, $page->name));

            return $page;
        });
    }
}
