<?php

namespace App\Repositories;

use App\Models\ProductReview;
use App\Support\ModelCache;
use Illuminate\Support\Facades\Schema;

class ReviewRepository
{
    /**
     * Visible reviews of a product (newest first, up to $limit) + count per star for the summary bars.
     *
     * @return array{reviews: \Illuminate\Support\Collection, count: int, average: float, stars: array<int,int>}
     */
    public function forProduct(int $productId, int $limit = 30): array
    {
        $empty = ['reviews' => collect(), 'count' => 0, 'average' => 0.0, 'stars' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0]];
        if (! self::ready()) {
            return $empty;
        }

        return ModelCache::remember(ModelCache::REVIEWS, "product:$productId:$limit", function () use ($productId, $limit, $empty) {
            $base = ProductReview::query()->where('product_id', $productId)->where('is_active', true);
            $stars = (clone $base)->selectRaw('rating, COUNT(*) as c')->groupBy('rating')->pluck('c', 'rating')->all();
            $count = array_sum($stars);
            if ($count === 0) {
                return $empty;
            }

            return [
                'reviews' => (clone $base)->latest('id')->take($limit)->get(['id', 'unique_id', 'name', 'rating', 'review', 'images', 'created_at']),
                'count' => $count,
                'average' => round(array_sum(array_map(fn ($r, $c) => $r * $c, array_keys($stars), $stars)) / $count, 2),
                'stars' => array_replace($empty['stars'], array_map('intval', $stars)),
            ];
        });
    }

    public function adminPaginate(array $filters)
    {
        return ProductReview::query()->with('product:id,name,slug,thumbnail', 'order:id,order_number')
            ->when($filters['q'] ?? null, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', '%'.like_escape($s).'%')->orWhere('review', 'like', '%'.like_escape($s).'%')
                ->orWhereHas('product', fn ($p) => $p->where('name', 'like', '%'.like_escape($s).'%'))))
            ->when(($filters['rating'] ?? '') !== '', fn ($q) => $q->where('rating', (int) $filters['rating']))
            ->when(($filters['status'] ?? '') !== '', fn ($q) => $q->where('is_active', $filters['status'] === 'visible'))
            ->latest('id')->paginate(20);
    }

    /** The reviews table exists (migration run). */
    public static function ready(): bool
    {
        static $ready = null;

        return $ready ??= Schema::hasTable('product_reviews');
    }
}
