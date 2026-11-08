<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Repositories\ProductRepository;
use App\Services\CartService;
use App\Support\Bots;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(protected CartService $cart)
    {
    }

    public function summary()
    {
        return response()->json($this->cart->summary());
    }

    public function add(Request $request, ProductRepository $products)
    {
        $data = $request->validate([
            'product_id' => 'required|integer',
            'qty' => 'nullable|integer|min:1|max:'.CartService::MAX_QTY,
        ]);

        $product = $products->findActive($data['product_id']);
        if (! $product || ! $product->in_stock) {
            return $this->respond($request, false, 'Sorry, this product is not available right now.');
        }

        $this->cart->add($product->id, $data['qty'] ?? 1);

        if ($request->boolean('buy_now')) {
            return $request->expectsJson()
                ? response()->json(['ok' => true, 'redirect' => route('checkout')])
                : redirect()->route('checkout');
        }

        return $this->respond($request, true, "“{$product->name}” added to cart.");
    }

    /**
     * Direct-order link for social posts: /buy/{slug}[?qty=2].
     * People land on checkout with exactly that product and quantity in the cart;
     * link-preview crawlers get a page with the product's title, price and image instead.
     */
    public function buyNow(Request $request, string $slug, ProductRepository $products)
    {
        $product = $products->findBySlug($slug);
        if (! $product) {
            return redirect()->route('products.index')->with('error', 'Sorry, this product is no longer available.');
        }

        if (Bots::isLinkPreview($request->userAgent())) {
            return response()->view('frontend.buy-now', compact('product'))->header('X-Robots-Tag', 'noindex');
        }

        if (! $product->in_stock) {
            return redirect($product->url)->with('error', 'Sorry, this product is out of stock right now.');
        }

        // Set (not add) the quantity, so opening the link twice does not double the order.
        $qty = max(1, min(CartService::MAX_QTY, (int) $request->query('qty', 1)));
        $this->cart->set($product->id, $qty);

        return redirect()->route('checkout')
            ->with('success', "“{$product->name}” is ready to order.")
            ->header('X-Robots-Tag', 'noindex');
    }

    public function update(Request $request)
    {
        $data = $request->validate(['product_id' => 'required|integer', 'qty' => 'required|integer|min:0|max:'.CartService::MAX_QTY]);
        $this->cart->set($data['product_id'], $data['qty']);

        return $this->respond($request, true);
    }

    public function remove(Request $request)
    {
        $this->cart->remove((int) $request->input('product_id'));

        return $this->respond($request, true, 'Item removed.');
    }

    protected function respond(Request $request, bool $ok, ?string $message = null)
    {
        if ($request->expectsJson()) {
            return response()->json(['ok' => $ok, 'message' => $message] + $this->cart->summary(), $ok ? 200 : 422);
        }

        return back()->with($ok ? 'success' : 'error', $message);
    }
}
