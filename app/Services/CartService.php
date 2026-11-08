<?php

namespace App\Services;

use App\Repositories\ProductRepository;
use Illuminate\Support\Collection;

/**
 * Session cart holding only [product_id => qty]. Prices are always re-read
 * from the (cached) product so the admin's latest price applies.
 */
class CartService
{
    protected const KEY = 'cart';
    public const MAX_QTY = 20;

    public function __construct(protected ProductRepository $products)
    {
    }

    public function add(int $productId, int $qty = 1): void
    {
        $cart = session(self::KEY, []);
        $cart[$productId] = min(self::MAX_QTY, ($cart[$productId] ?? 0) + max(1, $qty));
        session([self::KEY => $cart]);
    }

    public function set(int $productId, int $qty): void
    {
        $cart = session(self::KEY, []);
        if ($qty < 1) {
            unset($cart[$productId]);
        } else {
            $cart[$productId] = min(self::MAX_QTY, $qty);
        }
        session([self::KEY => $cart]);
    }

    public function remove(int $productId): void
    {
        $this->set($productId, 0);
    }

    public function clear(): void
    {
        session()->forget(self::KEY);
    }

    public function count(): int
    {
        return array_sum(session(self::KEY, []));
    }

    /** Cart lines with live product data; silently drops unavailable products. */
    public function items(): Collection
    {
        return collect(session(self::KEY, []))
            ->map(function ($qty, $id) {
                $product = $this->products->findActive((int) $id);

                return $product && $product->in_stock ? (object) [
                    'product' => $product,
                    'qty' => (int) $qty,
                    'price' => $product->selling_price,
                    'total' => $product->selling_price * $qty,
                ] : null;
            })
            ->filter()
            ->values();
    }

    public function subtotal(): float
    {
        return $this->items()->sum('total');
    }

    public function summary(): array
    {
        $items = $this->items();

        return [
            'count' => $items->sum('qty'),
            'subtotal' => $items->sum('total'),
            'items' => $items->map(fn ($i) => [
                'id' => $i->product->id,
                'name' => $i->product->name,
                'url' => $i->product->url,
                'image' => $i->product->thumb_url,
                'qty' => $i->qty,
                'price' => money($i->price),
                'total' => money($i->total),
            ])->all(),
            'subtotal_formatted' => money($items->sum('total')),
        ];
    }
}
