<?php

namespace App\Services;

use App\Models\Blog;
use App\Models\Product;
use App\Models\ShortLink;
use App\Repositories\BlogRepository;
use App\Repositories\ProductRepository;
use App\Support\ModelCache;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Short links (/s/{code}) for sharing where long or "/buy/" URLs get blocked.
 *  - one link per target (product page, direct order, blog post), created once and reused
 *  - the target is stored by id and resolved on every click: slug or domain changes never break a link
 *  - people are counted; link-preview bots (Facebook, WhatsApp, …) get a preview page and are not counted
 */
class ShortLinkService
{
    /** Easy to read and type: no 0/o, 1/l/i. */
    protected const ALPHABET = 'abcdefghjkmnpqrstuvwxyz23456789';

    public function __construct(protected ProductRepository $products, protected BlogRepository $blogs)
    {
    }

    /** Existing link for the target, or a new one. */
    public function ensure(string $type, Product|Blog $target): ShortLink
    {
        abort_unless(array_key_exists($type, ShortLink::TYPES), 404);

        $link = ShortLink::withTrashed()->where('type', $type)->where('target_id', $target->id)->first();
        if ($link) {
            if ($link->trashed()) {
                $link->restore();
            }

            return $link;
        }

        try {
            return ShortLink::create(['code' => $this->newCode(), 'type' => $type, 'target_id' => $target->id]);
        } catch (QueryException $e) {
            // Two admins at the same moment: the other request created it first.
            return ShortLink::where('type', $type)->where('target_id', $target->id)->firstOrFail();
        }
    }

    /** Code → [id, type, target_id] (cached), or null. */
    public function find(string $code): ?array
    {
        return ModelCache::remember(ModelCache::SHORTLINKS, "code:$code", fn () => ShortLink::where('code', $code)->first(['id', 'type', 'target_id'])?->only(['id', 'type', 'target_id']));
    }

    /** Already-created link for a target (never creates one). */
    public function existing(string $type, int $targetId): ?string
    {
        $code = ModelCache::remember(ModelCache::SHORTLINKS, "existing:$type:$targetId", fn () => ShortLink::where('type', $type)->where('target_id', $targetId)->value('code'));

        return $code ? route('short.go', $code) : null;
    }

    /**
     * Where a link points right now, with what a share preview needs; null when the target is gone or hidden.
     *
     * @return array{url: string, title: string, description: string, image: string, price: ?float, old_price: ?float}|null
     */
    public function target(string $type, int $id): ?array
    {
        if ($type === 'blog') {
            $blog = $this->blogs->published()->firstWhere('id', $id);

            return $blog ? [
                'url' => $blog->url, 'title' => $blog->title, 'description' => (string) $blog->excerpt,
                'image' => $blog->image_url, 'price' => null, 'old_price' => null,
            ] : null;
        }

        $product = $this->products->findActive($id);
        if (! $product) {
            return null;
        }
        $price = money($product->selling_price);

        return [
            'url' => $type === 'buy' ? route('buy-now', $product->slug) : $product->url,
            'title' => ($type === 'buy' ? 'Order now: ' : '').$product->name.' — '.$price,
            'description' => $price.($product->hasDiscount() ? ' (was '.money($product->unit_price).')' : '').' · Cash on delivery all over Bangladesh. '.Str::limit(strip_tags((string) ($product->short_description ?: $product->description)), 120),
            'image' => $product->image_url,
            'price' => $product->selling_price,
            'old_price' => $product->hasDiscount() ? $product->unit_price : null,
        ];
    }

    /** +1 click without model events, so the code lookups stay cached; the admin list refreshes. */
    public function recordClick(int $linkId): void
    {
        DB::table('short_links')->where('id', $linkId)->update(['clicks' => DB::raw('clicks + 1'), 'last_clicked_at' => now()]);
        ModelCache::bump(ModelCache::SHORTLINK_STATS);
    }

    /** Fresh numbers for the admin share menu. */
    public function stats(ShortLink $link): array
    {
        $row = DB::table('short_links')->where('id', $link->id)->first(['clicks', 'last_clicked_at']);

        return [
            'url' => $link->url,
            'clicks' => (int) $row->clicks,
            'last_clicked' => $row->last_clicked_at ? Carbon::parse($row->last_clicked_at)->diffForHumans() : null,
        ];
    }

    public function adminPaginate(array $filters)
    {
        $key = 'admin:list:'.ModelCache::hash($filters + ['page' => request('page', 1)]);

        return ModelCache::rememberList([ModelCache::SHORTLINK_STATS, ModelCache::PRODUCTS, ModelCache::BLOGS], $key, function () use ($filters) {
            $page = ShortLink::query()
                ->when($filters['type'] ?? null, fn ($q, $t) => $q->where('type', $t))
                ->when($filters['q'] ?? null, function ($q, $s) {
                    $like = '%'.like_escape($s).'%';
                    $q->where(fn ($w) => $w->where('code', 'like', $like)
                        ->orWhere(fn ($p) => $p->whereIn('type', ['product', 'buy'])->whereIn('target_id', Product::withTrashed()->where('name', 'like', $like)->orWhere('code', 'like', $like)->select('id')))
                        ->orWhere(fn ($b) => $b->where('type', 'blog')->whereIn('target_id', Blog::withTrashed()->where('title', 'like', $like)->select('id'))));
                })
                ->orderByDesc(($filters['sort'] ?? '') === 'clicks' ? 'clicks' : 'id')
                ->paginate(config('shop.per_page.admin'));

            // Target names in two queries (deleted targets included, so the list can say so).
            $products = Product::withTrashed()->whereIn('id', $page->whereIn('type', ['product', 'buy'])->pluck('target_id'))->get(['id', 'unique_id', 'name', 'code', 'slug', 'thumbnail', 'is_active', 'deleted_at'])->keyBy('id');
            $blogs = Blog::withTrashed()->whereIn('id', $page->where('type', 'blog')->pluck('target_id'))->get(['id', 'unique_id', 'title', 'slug', 'thumb', 'image', 'is_active', 'published_at', 'deleted_at'])->keyBy('id');
            $page->getCollection()->each(fn (ShortLink $l) => $l->setRelation('target', $l->type === 'blog' ? $blogs->get($l->target_id) : $products->get($l->target_id)));

            return $page;
        });
    }

    protected function newCode(): string
    {
        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
        } while (ShortLink::withTrashed()->where('code', $code)->exists());

        return $code;
    }
}
