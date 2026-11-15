<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Repositories\ProductRepository;
use App\Services\ReviewService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** POST /product/{slug}/reviews — a rating & review from a delivered order (rules in ReviewService). */
class ReviewController extends Controller
{
    public function store(Request $request, string $slug, ProductRepository $products, ReviewService $reviews)
    {
        $product = $products->findBySlug($slug) ?? abort(404);

        try {
            $data = $request->validate([
                'name' => 'required|string|min:2|max:80',
                'order_no' => 'required|string|max:20',
                'mobile' => ['required', 'regex:/^01[3-9]\d{8}$/'],
                'rating' => 'required|integer|between:1,5',
                'review' => 'required|string|min:10|max:2000',
                'images' => 'nullable|array|max:3',
                'images.*' => upload_rules('review'),
            ], [
                'rating.required' => 'Please choose a star rating.',
                'rating.between' => 'Please choose a star rating.',
                'mobile.regex' => 'Enter the 11 digit mobile used for the order (01XXXXXXXXX).',
                'review.min' => 'Please write at least a few words (10 characters).',
                'images.max' => 'You can add up to 3 photos.',
            ]);
            $reviews->submit($product, $data, $request->file('images', []));
        } catch (ValidationException $e) {
            // Back to the review form (its own error bag, so other forms on the page stay clean).
            $e->errorBag = 'review';
            throw $e->redirectTo($product->url.'#reviews');
        }

        return redirect()->to($product->url.'#reviews')->with('review_success', 'Thank you! Your review is now on this page.');
    }
}
