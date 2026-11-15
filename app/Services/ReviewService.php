<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

/**
 * Review rules: the order must exist, its mobile must match, it must be DELIVERED, it must contain this product,
 * and each order can be reviewed once. Reviews are shown right away.
 */
class ReviewService
{
    public function __construct(protected ImageService $images)
    {
    }

    /** @param  UploadedFile[]  $files  up to 3 photos */
    public function submit(Product $product, array $data, array $files = []): ProductReview
    {
        $order = Order::where('order_number', Order::normalizeNumber($data['order_no']))->first();
        $mobile = preg_replace('/\D/', '', $data['mobile']);

        // Same message for "no such order" and "mobile does not match", so order numbers can't be probed.
        if (! $order || $order->mobile !== $mobile) {
            throw ValidationException::withMessages(['order_no' => 'We could not find this order with this mobile number. Check the order number (e.g. NT-00012) and the mobile used for the order.']);
        }
        if ($order->status !== 'delivered') {
            throw ValidationException::withMessages(['order_no' => 'You can review once your order has been delivered. This order is now: '.$order->status_label.'.']);
        }
        if (! $order->items()->where('product_id', $product->id)->exists()) {
            throw ValidationException::withMessages(['order_no' => 'This product is not in that order. Please review it from the page of a product you received.']);
        }
        if (ProductReview::withTrashed()->where('order_id', $order->id)->exists()) {
            throw ValidationException::withMessages(['order_no' => 'A review has already been submitted for this order. Thank you!']);
        }

        $paths = [];
        foreach (array_slice($files, 0, 3) as $file) {
            $paths[] = $this->images->store($file, 'reviews', config('shop.images.review'), 'max', 'webp');
        }

        try {
            return ProductReview::create([
                'product_id' => $product->id,
                'order_id' => $order->id,
                'name' => trim($data['name']),
                'mobile' => $mobile,
                'rating' => (int) $data['rating'],
                'review' => trim($data['review']),
                'images' => $paths ?: null,
                'is_active' => true,
            ]);
        } catch (QueryException $e) {
            $this->images->delete(...$paths);
            throw ValidationException::withMessages(['order_no' => 'A review has already been submitted for this order. Thank you!']); // same order sent twice at once
        }
    }
}
