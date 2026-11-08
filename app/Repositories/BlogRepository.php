<?php

namespace App\Repositories;

use App\Models\Blog;
use App\Support\ModelCache;
use App\Support\RichText;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class BlogRepository
{
    /** Light columns for cards and lists (no description). */
    protected const CARD_COLUMNS = ['id', 'unique_id', 'title', 'slug', 'excerpt', 'image', 'thumb', 'is_active', 'published_at', 'updated_at'];

    /**
     * Published posts, newest first. The cached list includes scheduled posts and is
     * filtered by date on read, so a post appears on time without a cache refresh.
     */
    public function published(): Collection
    {
        return ModelCache::remember(ModelCache::BLOGS, 'published', fn () => Blog::query()
            ->select(self::CARD_COLUMNS)
            ->where('is_active', true)->whereNotNull('published_at')
            ->orderByDesc('published_at')->orderByDesc('id')
            ->get())
            ->filter(fn (Blog $b) => $b->published_at->lte(now()))
            ->values();
    }

    public function latest(int $limit): Collection
    {
        return $this->published()->take($limit);
    }

    public function paginate(int $perPage): LengthAwarePaginator
    {
        $all = $this->published();
        $page = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator($all->forPage($page, $perPage)->values(), $all->count(), $perPage, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
        ]);
    }

    /** Full post with its rendered content (blog links resolved against the live list). */
    public function findPublished(string $slug): ?Blog
    {
        if (! $this->published()->firstWhere('slug', $slug)) {
            return null;
        }

        return ModelCache::remember(ModelCache::BLOGS, "post:$slug", function () use ($slug) {
            $blog = Blog::where('slug', $slug)->first();
            $blog?->setAttribute('content_html', RichText::render($blog->description, fn ($s) => $this->linkTarget($s), $blog->title));

            return $blog;
        });
    }

    /** Sidebar "More to read": newest other posts. */
    public function related(Blog $blog, int $limit = 5): Collection
    {
        return $this->published()->where('id', '!=', $blog->id)->take($limit)->values();
    }

    /** For "related blog" cards inside content: title + URL of a published post, or null. */
    public function linkTarget(?string $slug): ?array
    {
        $post = $slug ? $this->published()->firstWhere('slug', $slug) : null;

        return $post ? ['title' => $post->title, 'url' => $post->url] : null;
    }

    public function sitemap(): Collection
    {
        return $this->published();
    }

    /* ---------------------------------------------------------------- Admin */

    public function adminPaginate(array $filters)
    {
        $key = 'admin:list:'.ModelCache::hash($filters + ['page' => request('page', 1)]);

        return ModelCache::rememberList(ModelCache::BLOGS, $key, fn () => Blog::query()
            ->select(self::CARD_COLUMNS)
            ->when($filters['q'] ?? null, fn ($q, $s) => $q->where('title', 'like', '%'.like_escape($s).'%'))
            ->when(($filters['status'] ?? '') === 'published', fn ($q) => $q->where('is_active', true)->where('published_at', '<=', now()))
            ->when(($filters['status'] ?? '') === 'scheduled', fn ($q) => $q->where('is_active', true)->where('published_at', '>', now()))
            ->when(($filters['status'] ?? '') === 'hidden', fn ($q) => $q->where('is_active', false))
            ->orderByDesc('published_at')->orderByDesc('id')
            ->paginate(config('shop.per_page.admin')));
    }

    public function adminFind(string $uid): ?Blog
    {
        return ModelCache::remember(ModelCache::BLOGS, "admin:$uid", fn () => Blog::where('unique_id', $uid)->first());
    }

    /** Editor "Link a blog" picker: any post (published or not), newest first. */
    public function pickerSearch(string $term, ?string $exceptUid = null): Collection
    {
        $all = ModelCache::remember(ModelCache::BLOGS, 'picker', fn () => Blog::query()
            ->select(['unique_id', 'title', 'slug', 'is_active', 'published_at'])
            ->orderByDesc('published_at')->orderByDesc('id')->get());
        $term = mb_strtolower(trim($term));

        return $all->reject(fn ($b) => $b->unique_id === $exceptUid)
            ->filter(fn ($b) => $term === '' || str_contains(mb_strtolower($b->title), $term) || str_contains($b->slug, $term))
            ->take(15)->values();
    }
}
